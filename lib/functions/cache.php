<?php
/**
 * Mai Engine cache helper.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

if ( ! function_exists( 'mai_cache' ) ) {
	/**
	 * Returns mai-engine's cache instance, optionally scoped to an area group.
	 *
	 * Binds the `mai` prefix once. Pass an area group (e.g. 'css',
	 * 'template-parts') to scope reads, writes, and flushes to that area.
	 * Transient-backed: uses a persistent object cache (Redis) when present
	 * and the database otherwise.
	 *
	 * @since 2.40.0
	 *
	 * @param string $group Optional cache area group.
	 *
	 * @return \Mai\Cache\Cache
	 */
	function mai_cache( string $group = '' ): \Mai\Cache\Cache {
		$cache = \Mai\Cache\Cache::for( 'mai' );

		return $group ? $cache->group( $group ) : $cache;
	}
}
