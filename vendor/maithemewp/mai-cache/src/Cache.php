<?php
/**
 * Mai\Cache\Cache - remember()-pattern cache over a pluggable Store.
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Transient- or object-cache-backed cache with a Laravel-style remember()
 * pattern. Auto-bypasses caching when SCRIPT_DEBUG is true, and (in object
 * mode) when there is no persistent object cache.
 *
 * @since 0.1.0
 */
class Cache {

	/**
	 * Memoized instances keyed by "mode:prefix".
	 *
	 * @var array<string,self>
	 */
	private static array $instances = [];

	/**
	 * Memoized version tokens keyed by scope (used from 0.2.0).
	 *
	 * @var array<string,string>
	 */
	private static array $tokens = [];

	/**
	 * Replacement clock for tests, or null to use time().
	 *
	 * @since 0.5.0
	 */
	private static ?\Closure $clock = null;

	/**
	 * Storage schema version, present in every key (from 0.4.0).
	 *
	 * Bumped whenever the shape of a stored value changes -- s2, s3 and so on,
	 * never a different letter -- so a newer version never reads an older
	 * version's entries and vice versa. Without it, a downgrade (or a lower
	 * bundled copy winning the bootstrap race) would read an envelope as if it
	 * were the value inside it.
	 *
	 * @since 0.4.0
	 */
	private const SCHEMA = 's1';

	/**
	 * The only shape new_token() has produced: 12 lowercase hex characters. flush()
	 * deletes rows by token prefix, so it deletes only when every token in the prefix
	 * has this shape.
	 *
	 * @since 0.5.0
	 */
	private const TOKEN_PATTERN = '/^[0-9a-f]{12}\z/';

	private string $prefix;
	private Store $store;
	private string $group = '';

	/**
	 * @param string     $prefix Prefix prepended to all keys. Default 'mai'.
	 * @param Store|null  $store  Storage backend. Defaults to TransientStore.
	 *
	 * @since 0.1.0
	 */
	public function __construct( string $prefix = 'mai', ?Store $store = null ) {
		$this->prefix = trim( $prefix, '_' );
		$this->store  = $store ?? new TransientStore();
	}

	/**
	 * Transient-backed instance (Redis when present, DB fallback).
	 *
	 * @since 0.1.0
	 */
	public static function for( string $prefix = 'mai' ): self {
		return self::instance( 'transient', $prefix );
	}

	/**
	 * Object-cache-only instance (wp_cache_*, no DB fallback). A no-op when
	 * there is no persistent object cache.
	 *
	 * @since 0.2.0
	 */
	public static function object( string $prefix = 'mai' ): self {
		return self::instance( 'object', $prefix );
	}

	/**
	 * Scope a finer namespace within this prefix. Returns a configured clone;
	 * the base instance and its grouped views share the same prefix and store.
	 *
	 * @since 0.2.0
	 */
	public function group( string $group ): self {
		$clone        = clone $this;
		$clone->group = trim( $group, '_' );
		return $clone;
	}

	private static function instance( string $mode, string $prefix ): self {
		$prefix = trim( $prefix, '_' );
		$id     = $mode . ':' . $prefix;

		if ( ! isset( self::$instances[ $id ] ) ) {
			$store = 'object' === $mode ? new ObjectCacheStore() : new TransientStore();
			self::$instances[ $id ] = new self( $prefix, $store );
		}

		return self::$instances[ $id ];
	}

	/**
	 * Get a cached value, or compute + cache it. A WP_Error result is not cached.
	 *
	 * @since 0.1.0
	 */
	public function remember( string $key, callable $callback, int $expire ): mixed {
		$hit = $this->fetch( $key );

		if ( null !== $hit ) {
			return $hit['value'];
		}

		$value = $callback();

		if ( ! is_wp_error( $value ) ) {
			$this->set( $key, $value, $expire );
		}

		return $value;
	}

	/**
	 * Get a cached value, deleting it on hit (read-once / consume).
	 * Renamed from 0.1.0's forget() to match Laravel's pull() semantics.
	 *
	 * @since 0.2.0
	 */
	public function pull( string $key, mixed $default = null ): mixed {
		$hit = $this->fetch( $key );

		if ( null !== $hit ) {
			$this->delete( $key );
			return $hit['value'];
		}

		return $default;
	}

	/**
	 * Whether a value is stored, whatever that value is -- including false.
	 *
	 * @since 0.4.0
	 */
	public function has( string $key ): bool {
		return null !== $this->fetch( $key );
	}

	/**
	 * Get a cached value. Returns false on miss or when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function get( string $key ): mixed {
		$hit = $this->fetch( $key );

		return null === $hit ? false : $hit['value'];
	}

	/**
	 * Set a cached value. Returns false when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function set( string $key, mixed $value, int $expire ): bool {
		return $this->put( $key, $value, null, $expire );
	}

	/**
	 * Read the stored envelope for a key, or null on a miss.
	 *
	 * Every value is stored wrapped, so a hit is always an array and a miss is
	 * anything else. That is what lets a stored `false` be a hit: the store's
	 * own miss sentinel is `false`, and before 0.4.0 the two were the same
	 * thing, so remember() re-ran its callback on every request for any value
	 * that happened to be false.
	 *
	 * A raw pre-0.4.0 value is not an envelope and reads as a miss. It is then
	 * recomputed, rewritten wrapped, and the old entry ages out by TTL. The
	 * SCHEMA key segment means that case only arises for entries written by a
	 * consumer bypassing key(), which nothing shipped does.
	 *
	 * @since 0.4.0
	 * @since 0.5.0 The envelope may also carry 'w' (written time) and 's' (soft deadline).
	 *
	 * @return array{_v: ?string, value: mixed, w?: int, s?: int}|null
	 */
	private function fetch( string $key ): ?array {
		if ( ! $this->can_cache() ) {
			return null;
		}

		$raw = $this->store->read( $this->key( $key ) );

		if ( ! is_array( $raw ) || ! array_key_exists( 'value', $raw ) || ! array_key_exists( '_v', $raw ) ) {
			return null;
		}

		return $raw;
	}

	/**
	 * Write a value in its envelope. `_v` is null for a plain entry and the
	 * scope version for a stale-while-revalidate one; read_swr() uses that to
	 * tell them apart.
	 *
	 * @since 0.4.0
	 * @since 0.5.0 Added $soft_ttl.
	 *
	 * @param string   $key      Cache key.
	 * @param mixed    $value    Value.
	 * @param ?string  $version  Scope version, or null for a plain entry.
	 * @param int      $expire   Store expiry in seconds. 0 never expires.
	 * @param int|null $soft_ttl Soft lifetime in seconds. See envelope().
	 *
	 * @return bool
	 */
	private function put( string $key, mixed $value, ?string $version, int $expire, ?int $soft_ttl = null ): bool {
		if ( ! $this->can_cache() ) {
			return false;
		}

		return $this->store->write( $this->key( $key ), self::envelope( $value, $version, $soft_ttl ), max( 0, $expire ) );
	}

	/**
	 * Build the envelope stored for a value.
	 *
	 * With $soft_ttl null the envelope has only `_v` and `value`, which is all a plain
	 * entry needs. Any int adds `w`, the time it was written. An int above 0 also adds
	 * `s`, the soft deadline: read_swr() reports the entry as age-stale from then on. 0
	 * adds no `s`, so a 0 lifetime never reads as age-stale.
	 *
	 * @since 0.5.0
	 *
	 * @param mixed    $value    Value.
	 * @param ?string  $version  Scope version, or null for a plain entry.
	 * @param int|null $soft_ttl Soft lifetime in seconds, or null for no timestamps.
	 *
	 * @return array{_v: ?string, value: mixed, w?: int, s?: int}
	 */
	private static function envelope( mixed $value, ?string $version, ?int $soft_ttl = null ): array {
		$envelope = [ '_v' => $version, 'value' => $value ];

		if ( null === $soft_ttl ) {
			return $envelope;
		}

		$now           = self::now();
		$envelope['w'] = $now;

		if ( $soft_ttl > 0 ) {
			$envelope['s'] = $now + $soft_ttl;
		}

		return $envelope;
	}

	/**
	 * Delete a cached value.
	 *
	 * Intentionally not gated by can_cache() -- invalidation is best-effort and
	 * simply no-ops when the store is unavailable, so cleanup always attempts.
	 *
	 * @since 0.1.0
	 */
	public function delete( string $key ): bool {
		return $this->store->remove( $this->key( $key ) );
	}

	/**
	 * Invalidate the current scope by rotating its version token: the whole
	 * prefix when ungrouped, or just this group when grouped. The old entries
	 * become unreachable, and a store that implements PrefixDelete then deletes
	 * them, version rows included. A store without it leaves them until their
	 * own expiry, as before. Rows written without an expiry, such as version
	 * rows, stay for good.
	 *
	 * The old prefix is the key prefix up to and including the token being
	 * rotated. For a group that is "{prefix}_s1_{root token}_{group}_{old group token}_".
	 * For the whole prefix it is "{prefix}_s1_{old root token}_", which covers
	 * every group. Rows are deleted only when each token in that prefix is the
	 * 12 lowercase hex characters new_token() makes. A stored token of any other
	 * shape could stand for a broader prefix than one retired token, so cleanup is
	 * skipped. The old rows stay until their own expiry, and rows written without
	 * an expiry, such as version rows, stay for good. The token still rotates.
	 *
	 * Intentionally not gated by can_cache() -- same rationale as delete().
	 *
	 * @since 0.2.0
	 * @since 0.5.0 Deletes the old token's rows from a store that implements PrefixDelete.
	 */
	public function flush(): bool {
		$scope = $this->scope();

		// Read the old prefix before the rotation, and only when it will be used: reading
		// a token mints one that is missing.
		$old_prefix = $this->store instanceof PrefixDelete ? $this->retired_prefix() : null;

		$token = self::new_token();

		self::$tokens[ $scope ] = $token;

		$written = $this->store->write( $this->token_key( $scope ), $token, 0 );

		if ( null !== $old_prefix ) {
			$this->store->delete_prefix( $old_prefix );
		}

		return $written;
	}

	/**
	 * Object-cache group for single-flight locks (mai-cache owned, mai-branded).
	 *
	 * @since 0.3.0
	 */
	private const LOCK_GROUP = 'mai_cache_lock';

	/**
	 * Composite current version token for one or more consumer-defined scopes.
	 *
	 * Each scope's token is stored as a value (minted lazily), so rotating it does NOT
	 * change the keys of cached results: the prior value stays readable for
	 * stale-while-revalidate. Scope strings are the consumer's domain (e.g. post types).
	 *
	 * @since 0.3.0
	 *
	 * @param string[] $scopes Scope keys.
	 *
	 * @return string
	 */
	public function version( array $scopes ): string {
		$scopes = $scopes ? $scopes : [ '' ];
		sort( $scopes );

		$parts = [];
		foreach ( $scopes as $scope ) {
			$parts[] = $this->scope_version( (string) $scope );
		}

		return implode( '.', $parts );
	}

	/**
	 * Read (and lazily mint) one scope's stored version token.
	 *
	 * @since 0.3.0
	 *
	 * @param string $scope Scope key.
	 *
	 * @return string
	 */
	private function scope_version( string $scope ): string {
		$key   = '__v_' . $scope;
		$token = $this->get( $key );

		if ( ! is_string( $token ) || '' === $token ) {
			$token = self::new_token();
			$this->set( $key, $token, 0 );
		}

		return $token;
	}

	/**
	 * Rotate one scope's version token. Cached results keep their stable keys and become
	 * stale (still readable) rather than orphaned.
	 *
	 * Intentionally not gated by can_cache() -- same rationale as delete()/flush(): invalidation
	 * must always attempt. If a request that cannot cache (a conditional can_cache filter,
	 * SCRIPT_DEBUG) silently skipped the rotation, stale content could stay readable as fresh by
	 * requests that can cache until the entry's TTL expired.
	 *
	 * The token is written in the same envelope scope_version() reads back through get(), so
	 * the next version() call finds it instead of minting a second token over it. The write goes
	 * through the store directly, not through put(), because put() is gated by can_cache(). The
	 * 'w' key records when the token was written.
	 *
	 * @since 0.3.0
	 * @since 0.5.0 Writes the token in an envelope, with a write time.
	 *
	 * @param string $scope Scope key.
	 *
	 * @return bool
	 */
	public function bump( string $scope ): bool {
		return $this->store->write(
			$this->key( '__v_' . $scope ),
			self::envelope( self::new_token(), null, 0 ),
			0
		);
	}

	/**
	 * Read a versioned value. Null when cold; otherwise the value plus how current it is.
	 *
	 * `stale` says why the entry is not fresh. It is 'version' when the stored version no
	 * longer matches the supplied one, and 'age' when the version matches but the entry is
	 * past its soft lifetime. Version wins when both apply. It is null when neither does.
	 * `fresh` is true only when `stale` is null. An entry without a soft deadline, as 2.40
	 * and beta.4 wrote them, is never age-stale.
	 *
	 * @since 0.3.0
	 * @since 0.5.0 Adds `stale` and `written`, and age-staleness.
	 *
	 * @param string $key     Cache key.
	 * @param string $version Current composite version (from version()).
	 *
	 * @return array{value:mixed,fresh:bool,stale:'version'|'age'|null,written:int|null}|null
	 */
	public function read_swr( string $key, string $version ): ?array {
		$hit = $this->fetch( $key );

		// A plain set() entry carries no version. It is not this reader's to
		// serve -- reporting it as stale would hand back a value nobody ever
		// stamped, so it reads as cold instead.
		if ( null === $hit || null === $hit['_v'] ) {
			return null;
		}

		$stale = null;

		if ( ! hash_equals( (string) $hit['_v'], $version ) ) {
			$stale = 'version';
		} elseif ( isset( $hit['s'] ) && self::now() >= (int) $hit['s'] ) {
			$stale = 'age';
		}

		return [
			'value'   => $hit['value'],
			'fresh'   => null === $stale,
			'stale'   => $stale,
			'written' => isset( $hit['w'] ) ? (int) $hit['w'] : null,
		];
	}

	/**
	 * Store a value with the current version stamped into the envelope.
	 *
	 * `$ttl` is the soft lifetime: after it the entry reads as age-stale, but is still
	 * served. The store's own expiry is the hard lifetime, after which the entry is gone.
	 * With `$hard_ttl` null the hard lifetime equals the soft one. A `$hard_ttl` below
	 * `$ttl` is raised to it. A `$ttl` of 0 or less sets no soft deadline.
	 *
	 * @since 0.3.0
	 * @since 0.5.0 Added $hard_ttl, and the soft deadline.
	 *
	 * @param string   $key      Cache key.
	 * @param mixed    $value    Value.
	 * @param string   $version  Current composite version.
	 * @param int      $ttl      Soft lifetime in seconds.
	 * @param int|null $hard_ttl Hard lifetime in seconds. Default: same as $ttl.
	 *
	 * @return bool
	 */
	public function write_swr( string $key, mixed $value, string $version, int $ttl, ?int $hard_ttl = null ): bool {
		return $this->put( $key, $value, $version, max( $ttl, $hard_ttl ?? $ttl ), $ttl );
	}

	/**
	 * Single-flight lock: true for the one caller that should recompute a stale/cold key.
	 * Atomic only with a persistent object cache; degrades to per-request otherwise
	 * (acceptable, since stampedes only matter on high-traffic Redis sites).
	 *
	 * @since 0.3.0
	 *
	 * @param string $key Lock key (typically the cache key).
	 * @param int    $ttl Lock TTL in seconds.
	 *
	 * @return bool
	 */
	public function lock( string $key, int $ttl = 30 ): bool {
		return wp_cache_add( $this->key( 'lock_' . $key ), 1, self::LOCK_GROUP, $ttl );
	}

	/**
	 * Release a lock taken with lock(), so the next caller can take it.
	 *
	 * Release a lock only if you still hold it. A lock that has already expired may now
	 * belong to another request, and this deletes it too. Like lock(), this does not check
	 * can_cache().
	 *
	 * @since 0.5.0
	 *
	 * @param string $key Lock key, the same one passed to lock().
	 *
	 * @return bool True if a lock was removed, false if there was none.
	 */
	public function unlock( string $key ): bool {
		return wp_cache_delete( $this->key( 'lock_' . $key ), self::LOCK_GROUP );
	}

	/**
	 * Build the fully-namespaced key: prefix, storage schema, prefix version
	 * token, optional group + its version token, then the user key. Rotating a
	 * token (flush) changes every key under that scope, so old entries become
	 * unreachable. The schema segment does the same across versions of this
	 * package -- see SCHEMA.
	 *
	 * @since 0.1.0
	 */
	public function key( string $key ): string {
		$parts = [ $this->prefix, self::SCHEMA, $this->token( $this->prefix ) ];

		if ( '' !== $this->group ) {
			$parts[] = $this->group;
			$parts[] = $this->token( $this->prefix . '_' . $this->group );
		}

		$parts[] = ltrim( $key, '_' );

		return implode( '_', $parts );
	}

	/**
	 * Whether caching is currently allowed.
	 *
	 * Disabled when SCRIPT_DEBUG is true, when the store cannot persist
	 * (object mode without a persistent object cache), or when the
	 * "{prefix}_can_cache" filter returns false.
	 *
	 * @since 0.1.0
	 */
	public function can_cache(): bool {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return false;
		}

		if ( ! $this->store->available() ) {
			return false;
		}

		return (bool) apply_filters( $this->prefix . '_can_cache', true, $this->prefix );
	}

	/**
	 * Get the prefix used by this instance.
	 *
	 * @since 0.1.0
	 */
	public function prefix(): string {
		return $this->prefix;
	}

	/**
	 * Whether a persistent object cache (e.g. Redis) is in use.
	 *
	 * @since 0.2.0
	 */
	public static function has_persistent_object_cache(): bool {
		return (bool) wp_using_ext_object_cache();
	}

	/**
	 * Reset memoized instances, version tokens and the test clock. For tests and long-running
	 * processes (e.g. WP-CLI) that must not hold stale state across boundaries.
	 *
	 * @since 0.2.0
	 */
	public static function reset_runtime(): void {
		self::$instances = [];
		self::$tokens    = [];
		self::$clock     = null;
	}

	/**
	 * Current time as a Unix timestamp. Code in this package that needs the
	 * time reads it here, so a test can control it with set_clock().
	 *
	 * @since 0.5.0
	 */
	public static function now(): int {
		return null === self::$clock ? time() : (int) ( self::$clock )();
	}

	/**
	 * Replace the clock that now() reads. Pass null to go back to time().
	 * For tests; reset_runtime() also restores it.
	 *
	 * @since 0.5.0
	 *
	 * @param \Closure|null $clock Returns a Unix timestamp.
	 */
	public static function set_clock( ?\Closure $clock ): void {
		self::$clock = $clock;
	}

	/**
	 * Current invalidation scope: "{prefix}" or "{prefix}_{group}".
	 *
	 * @since 0.2.0
	 */
	private function scope(): string {
		return '' !== $this->group ? $this->prefix . '_' . $this->group : $this->prefix;
	}

	/**
	 * Read (or lazily create + persist) the version token for a scope.
	 * Memoized per scope for the request.
	 *
	 * @since 0.2.0
	 */
	private function token( string $scope ): string {
		if ( isset( self::$tokens[ $scope ] ) ) {
			return self::$tokens[ $scope ];
		}

		$stored = $this->store->read( $this->token_key( $scope ) );

		if ( ! is_string( $stored ) || '' === $stored ) {
			$stored = self::new_token();
			$this->store->write( $this->token_key( $scope ), $stored, 0 );
		}

		return self::$tokens[ $scope ] = $stored;
	}

	/**
	 * Storage key that holds a scope's version token (not itself versioned).
	 *
	 * @since 0.2.0
	 */
	private function token_key( string $scope ): string {
		return $scope . '__token';
	}

	/**
	 * The key prefix that rotating the current scope's token retires, or null when
	 * it is not safe to delete by it.
	 *
	 * key() with an empty user key ends in the joining underscore, which makes it
	 * exactly the prefix of every key under the current tokens. Its tokens are read
	 * here the way key() reads them, through token(): the root token, and for a group
	 * the group token. A token that is not 12 lowercase hex characters gives null.
	 *
	 * @since 0.5.0
	 *
	 * @return string|null
	 */
	private function retired_prefix(): ?string {
		$tokens = [ $this->token( $this->prefix ) ];

		if ( '' !== $this->group ) {
			$tokens[] = $this->token( $this->scope() );
		}

		foreach ( $tokens as $token ) {
			if ( 1 !== preg_match( self::TOKEN_PATTERN, $token ) ) {
				return null;
			}
		}

		return $this->key( '' );
	}

	/**
	 * Generate a fresh unique version token (12 lowercase hex chars). Unique
	 * per generation, so a regenerated token never collides with old keys.
	 *
	 * @since 0.2.0
	 */
	private static function new_token(): string {
		return bin2hex( random_bytes( 6 ) );
	}
}
