<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

// Disable Genesis Footer Widgets toggle setting.
add_filter( 'genesis_footer_widgets_toggle_enabled', '__return_false' );

// Remove Genesis footer output.
remove_action( 'genesis_footer', 'genesis_do_footer' );

add_filter( 'genesis_attr_site-footer', 'mai_add_site_footer_id' );
/**
 * Add ID to site footer to be used as skip link anchor.
 *
 * @since 2.1.0
 *
 * @param array $attr Site footer attributes.
 *
 * @return mixed
 */
function mai_add_site_footer_id( $attr ) {
	$attr['id'] = 'site-footer';

	return $attr;
}

add_filter( 'genesis_skip_links_output', 'mai_add_site_footer_skip_link', 99 );
/**
 * Adds the site footer skip link.
 *
 * @since 2.1.1
 *
 * @param array $links Skip links.
 *
 * @return array
 */
function mai_add_site_footer_skip_link( $links ) {
	if ( ! mai_is_element_hidden( 'site-footer' ) ) {
		$links['site-footer'] = __( 'Skip to site footer', 'mai-engine' );
	}

	return $links;
}
