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

add_filter( 'image_size_names_choose', 'mai_get_media_chooser_sizes' );
/**
 * Add our image sizes to the media chooser.
 *
 * @since 0.1.0
 *
 * @param array $sizes The size options.
 *
 * @return array  Modified size options.
 */
function mai_get_media_chooser_sizes( $sizes ) {
	global $_wp_additional_image_sizes;

	if ( ! isset( $_wp_additional_image_sizes ) || 0 === count( $_wp_additional_image_sizes ) ) {
		return $sizes;
	}

	$additional = $_wp_additional_image_sizes;

	unset( $additional['1536x1536'] );
	unset( $additional['2048x2048'] );

	if ( count( $additional ) ) {
		$additional = array_keys( $additional );
		foreach ( $additional as $name ) {
			$name           = str_replace( '-sm', ' (sm)', $name );
			$name           = str_replace( '-md', ' (md)', $name );
			$name           = str_replace( '-lg', ' (lg)', $name );
			$sizes[ $name ] = ucfirst( $name );
		}
	}

	return $sizes;
}
