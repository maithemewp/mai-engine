<?php
declare(strict_types=1);

/**
 * Mai\Cache\PrefixDelete - optional contract for a Store that can delete by key prefix.
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * A Store that can delete every row whose key starts with a prefix. Cache::flush()
 * uses it to remove the rows a rotated token left behind.
 *
 * This is a separate interface, not a method on Store, because Store has been
 * public since 0.2.0 and a method added to it would break every custom store.
 * A Store that does not implement this is skipped on flush: the token still
 * rotates, and its old rows age out by TTL as before.
 *
 * @since 0.5.0
 */
interface PrefixDelete {
	/**
	 * Delete every row whose key starts with $prefix.
	 *
	 * $prefix is a key prefix as Cache::key() builds it, such as "mai_s1_<token>_".
	 * It is not an escaped LIKE pattern, so an implementation that matches with SQL
	 * LIKE must escape it.
	 *
	 * @since 0.5.0
	 *
	 * @param string $prefix Key prefix.
	 *
	 * @return int Number of rows deleted.
	 */
	public function delete_prefix( string $prefix ): int;
}
