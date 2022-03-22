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

add_action( 'init', 'mai_add_featured_image_metabox', 99 );
/**
 * Add page header metabox.
 * This needs to be on init so custom post types and taxonomies are available.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_featured_image_metabox() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$locations = [];

	$taxonomies = get_taxonomies( [ 'public' => 'true' ], 'names' );
	if ( class_exists( 'WooCommerce' ) ) {
		unset( $taxonomies['product_cat'] );
	}
	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$locations[] = [
				[
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => $taxonomy,
				],
			];
		}
	}

	$field_data = [
		'key'      => 'featured_image_field_group',
		'title'    => esc_html__( 'Featured Image', 'mai-engine' ),
		'location' => $locations ?: false,
		'fields'   => [
			[
				'key'           => 'featured_image',
				'label'         => esc_html__( 'Image', 'mai-engine' ),
				'name'          => 'featured_image',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'landscape-sm',
				'library'       => 'all',
			],
		],
	];

	acf_add_local_field_group( $field_data );
}

add_filter( 'acf/load_field/key=featured_image', 'mai_load_featured_image_field' );
/**
 * Changes field label on Add New screen.
 *
 * @since 2.19.0
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_load_featured_image_field( $field ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

	if ( ! ( $screen && 'edit-tags' === $screen->base ) ) {
		return $field;
	}

	$field['label'] = __( 'Featured Image', 'mai-engine' );

	return $field;
}
