<?php
/**
 * Mai\Cache\TransientStore - transient-backed Store (Redis when present, DB otherwise).
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;


/**
 * Stores values with the WordPress transient API, which routes to the object
 * cache when one is present and to wp_options otherwise. Always available.
 *
 * @since 0.2.0
 */
class TransientStore implements Store, PrefixDelete {
	public function read( string $key ): mixed {
		return get_transient( $key );
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		return (bool) set_transient( $key, $value, $expire );
	}

	public function remove( string $key ): bool {
		return (bool) delete_transient( $key );
	}

	public function available(): bool {
		return true;
	}

	/**
	 * Delete every transient whose key starts with $prefix.
	 *
	 * Each transient is two rows, "_transient_{key}" and "_transient_timeout_{key}",
	 * so one statement matches both. It deletes 1000 rows at a time and repeats until
	 * a pass deletes nothing. Returns 0 without touching the database when a persistent
	 * object cache is in use, because the transients live in the object cache then and
	 * expire there on their own.
	 *
	 * The whole literal is escaped, "_transient_" included, so the pattern is exact and
	 * MySQL can use the option_name index. An empty prefix, or one that does not end in
	 * "_", returns 0 without touching the database: every key ends its prefix with the
	 * joining underscore, and anything else could match more than one token's rows.
	 *
	 * The options table is per-site on multisite, so this cleans the current site only.
	 *
	 * @since 0.5.0
	 *
	 * @param string $prefix Key prefix, as Cache::key() builds it.
	 *
	 * @return int Number of rows deleted.
	 */
	public function delete_prefix( string $prefix ): int {
		if ( '' === $prefix || ! str_ends_with( $prefix, '_' ) ) {
			return 0;
		}

		if ( wp_using_ext_object_cache() ) {
			return 0;
		}

		global $wpdb;

		$value_like   = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$timeout_like = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
		$total        = 0;

		do {
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s LIMIT 1000",
					$value_like,
					$timeout_like
				)
			);
			$total  += $deleted;
		} while ( $deleted > 0 );

		// Version rows are written without an expiry, so WordPress autoloads them. Drop the
		// cached alloptions so a deleted row is not served from it.
		wp_cache_delete( 'alloptions', 'options' );

		return $total;
	}
}
