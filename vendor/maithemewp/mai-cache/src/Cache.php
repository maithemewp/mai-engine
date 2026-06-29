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
		$cached = $this->get( $key );

		if ( false !== $cached ) {
			return $cached;
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
		$cached = $this->get( $key );

		if ( false !== $cached ) {
			$this->delete( $key );
			return $cached;
		}

		return $default;
	}

	/**
	 * Get a cached value. Returns false on miss or when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function get( string $key ): mixed {
		if ( ! $this->can_cache() ) {
			return false;
		}

		return $this->store->read( $this->key( $key ) );
	}

	/**
	 * Set a cached value. Returns false when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function set( string $key, mixed $value, int $expire ): bool {
		if ( ! $this->can_cache() ) {
			return false;
		}

		return $this->store->write( $this->key( $key ), $value, max( 0, $expire ) );
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
	 * prefix when ungrouped, or just this group when grouped. Orphaned entries
	 * become unreachable and age out by TTL.
	 *
	 * Intentionally not gated by can_cache() -- same rationale as delete().
	 *
	 * @since 0.2.0
	 */
	public function flush(): bool {
		$scope = $this->scope();
		$token = self::new_token();

		self::$tokens[ $scope ] = $token;

		return $this->store->write( $this->token_key( $scope ), $token, 0 );
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
	 * @since 0.3.0
	 *
	 * @param string $scope Scope key.
	 *
	 * @return bool
	 */
	public function bump( string $scope ): bool {
		return $this->store->write( $this->key( '__v_' . $scope ), self::new_token(), 0 );
	}

	/**
	 * Read a versioned value. Null when cold; otherwise the value plus whether the stored
	 * version stamp matches the supplied current version (fresh) or not (stale).
	 *
	 * @since 0.3.0
	 *
	 * @param string $key     Cache key.
	 * @param string $version Current composite version (from version()).
	 *
	 * @return array{value:mixed,fresh:bool}|null
	 */
	public function read_swr( string $key, string $version ): ?array {
		$envelope = $this->get( $key );

		if ( ! is_array( $envelope ) || ! array_key_exists( '_v', $envelope ) ) {
			return null;
		}

		return [
			'value' => $envelope['value'] ?? null,
			'fresh' => hash_equals( (string) $envelope['_v'], $version ),
		];
	}

	/**
	 * Store a value with the current version stamped into the envelope.
	 *
	 * @since 0.3.0
	 *
	 * @param string $key     Cache key.
	 * @param mixed  $value   Value.
	 * @param string $version Current composite version.
	 * @param int    $ttl     TTL in seconds.
	 *
	 * @return bool
	 */
	public function write_swr( string $key, mixed $value, string $version, int $ttl ): bool {
		return $this->set( $key, [ '_v' => $version, 'value' => $value ], $ttl );
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
	 * Build the fully-namespaced key: prefix, prefix version token, optional
	 * group + its version token, then the user key. Rotating a token (flush)
	 * changes every key under that scope, so old entries become unreachable.
	 *
	 * @since 0.1.0
	 */
	public function key( string $key ): string {
		$parts = [ $this->prefix, $this->token( $this->prefix ) ];

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
	 * Reset memoized instances and version tokens. For tests and long-running
	 * processes (e.g. WP-CLI) that must not hold stale state across boundaries.
	 *
	 * @since 0.2.0
	 */
	public static function reset_runtime(): void {
		self::$instances = [];
		self::$tokens    = [];
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
	 * Generate a fresh unique version token (12 lowercase hex chars). Unique
	 * per generation, so a regenerated token never collides with old keys.
	 *
	 * @since 0.2.0
	 */
	private static function new_token(): string {
		return bin2hex( random_bytes( 6 ) );
	}
}
