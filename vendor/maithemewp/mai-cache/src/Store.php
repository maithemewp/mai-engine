<?php
/**
 * Mai\Cache\Store - raw storage contract behind the Cache facade.
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * A storage backend for Cache. Implementations decide where values live and
 * whether they can persist right now.
 *
 * @since 0.2.0
 */
interface Store {
	/**
	 * Read a raw value. Returns false on miss.
	 *
	 * @since 0.2.0
	 */
	public function read( string $key ): mixed;

	/**
	 * Write a raw value with a TTL in seconds (0 = no expiry).
	 *
	 * @since 0.2.0
	 */
	public function write( string $key, mixed $value, int $expire ): bool;

	/**
	 * Remove a raw value.
	 *
	 * @since 0.2.0
	 */
	public function remove( string $key ): bool;

	/**
	 * Whether this backend can persist across requests right now.
	 *
	 * @since 0.2.0
	 */
	public function available(): bool;
}
