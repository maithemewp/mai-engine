<?php
/**
 * Mai\Cache\TransientStore - transient-backed Store (Redis when present, DB otherwise).
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Stores values with the WordPress transient API, which routes to the object
 * cache when one is present and to wp_options otherwise. Always available.
 *
 * @since 0.2.0
 */
class TransientStore implements Store {
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
}
