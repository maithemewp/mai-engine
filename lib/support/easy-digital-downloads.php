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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_filter( 'edd_download_supports', 'mai_edd_download_supports' );
/**
 * Adds support for mai settings in Easy Digital Downloads.
 *
 * @since TBD
 *
 * @param array $supports The existing post type supports.
 *
 * @return array
 */
function mai_edd_download_supports( $supports ) {
	$new      = [ 'genesis-cpt-archives-settings', 'genesis-layouts', 'mai-archive-settings', 'mai-single-settings' ];
	$supports = array_unique( array_merge( $supports, $new ) );

	return $supports;
}

add_filter( 'edd_purchase_link_defaults', 'mai_edd_purchase_link_defaults' );
/**
 * Sets purchase link defaults.
 *
 * @since TBD
 *
 * @param array $args The purchase link args.
 *
 * @return array
 */
function mai_edd_purchase_link_defaults( $defaults ) {
	$defaults['color'] = 'inherit';

	return $defaults;
}

add_filter( 'edd_purchase_link_args', 'mai_edd_purchase_link_args' );
/**
 * Adds custom class to buttons if color is set to default.
 * This is so we can force mai custom properities.
 *
 * @since TBD
 *
 * @param array $args The purchase link args.
 *
 * @return array
 */
function mai_edd_purchase_link_args( $args ) {
	// If color is inherit, EDD removes the value.
	if ( '' === $args['color'] ) {
		$args['class'] .= ' edd-default';
	}

	return $args;
}
