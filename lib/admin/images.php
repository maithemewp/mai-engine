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
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	global $_wp_additional_image_sizes;

	if ( ! isset( $_wp_additional_image_sizes ) || 0 === count( $_wp_additional_image_sizes ) ) {
		$choices = $sizes;
		return $choices;
	}

	$keepers = [
		'landscape-sm' => __( 'Landscape (Small)', 'mai-engine' ),
		'landscape-md' => __( 'Landscape (Medium)', 'mai-engine' ),
		'landscape-lg' => __( 'Landscape (Large)', 'mai-engine' ),
		'portrait-sm'  => __( 'Portrait (Small)', 'mai-engine' ),
		'portrait-md'  => __( 'Portrait (Medium)', 'mai-engine' ),
		'portrait-lg'  => __( 'Portrait (Large)', 'mai-engine' ),
		'square-sm'    => __( 'Square (Small)', 'mai-engine' ),
		'square-md'    => __( 'Square (Medium)', 'mai-engine' ),
		'square-lg'    => __( 'Square (Large)', 'mai-engine' ),
		'tiny'         => __( 'Tiny', 'mai-engine' ),
		'cover'        => __( 'Cover', 'mai-engine' ),
	];

	foreach ( $_wp_additional_image_sizes as $name => $sizes_array ) {
		if ( ! isset( $keepers[ $name ] ) ) {
			continue;
		}
		$sizes[ $name ] = $keepers[ $name ];
	}

	$choices = $sizes;

	return $choices;
}
