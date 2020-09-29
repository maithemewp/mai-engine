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

add_action( 'init', 'mai_add_page_header_metabox' );
/**
 * Add page header metabox.
 * This needs to be on init so custom post types and taxonomies are available.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_page_header_metabox() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$locations = [];

	foreach ( mai_get_page_header_types( 'single' ) as $type ) {
		$locations[] = [
			[
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $type,
			],
		];
	}

	foreach ( mai_get_page_header_types( 'archive' ) as $type ) {
		$locations[] = [
			[
				'param'    => 'author' === $type ? 'user_form' : 'taxonomy',
				'operator' => '==',
				'value'    => $type,
			],
		];
	}

	$field_group_data = [
		'key'      => 'page_header_field_group',
		'title'    => esc_html__( 'Page Header', 'mai-engine' ),
		'location' => $locations ?: false,
		'position' => 'side',
		'fields'   => [
			[
				'key'           => 'page_header_image',
				'label'         => esc_html__( 'Image', 'mai-engine' ),
				'name'          => 'page_header_image',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'landscape-sm',
				'library'       => 'all',
			],
			[
				'key'   => 'page_header_description',
				'label' => esc_html__( 'Description', 'mai-engine' ),
				'name'  => 'page_header_description',
				'type'  => 'textarea',
				'rows'  => '3',
			],
		],
	];

	acf_add_local_field_group( $field_group_data );
}
