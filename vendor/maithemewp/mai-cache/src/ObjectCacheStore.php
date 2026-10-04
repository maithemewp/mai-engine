<?php
/**
 * Mai\Cache\ObjectCacheStore - object-cache-only Store (no DB fallback).
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;


/**
 * Stores values with the WordPress object cache (wp_cache_*). It deliberately
 * has no DB fallback: when there is no persistent object cache, available()
 * is false and Cache treats this store as a no-op, so it never writes to the
 * database. Keys already carry the Cache prefix, so a single constant group
 * is enough.
 *
 * @since 0.2.0
 */
class ObjectCacheStore implements Store, PrefixDelete {
	private const GROUP = 'mai_cache';

	public function read( string $key ): mixed {
		return wp_cache_get( $key, self::GROUP );
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		return (bool) wp_cache_set( $key, $value, self::GROUP, $expire );
	}

	public function remove( string $key ): bool {
		return (bool) wp_cache_delete( $key, self::GROUP );
	}

	public function available(): bool {
		return (bool) wp_using_ext_object_cache();
	}

	/**
	 * Does nothing and returns 0. The object cache expires its own keys, so a flush
	 * leaves nothing here to clean up.
	 *
	 * @since 0.5.0
	 *
	 * @param string $prefix Key prefix. Unused.
	 *
	 * @return int Always 0.
	 */
	public function delete_prefix( string $prefix ): int {
		return 0;
	}
}
