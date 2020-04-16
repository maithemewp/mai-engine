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

	$config     = mai_get_config( 'page-header' );
	$post_types = get_post_types( [ 'public' => true ], 'names' );
	unset( $post_types['attachment'] );
	$taxonomies = get_taxonomies( [ 'public' => true ], 'names' );
	unset( $taxonomies['post_format'] );
	unset( $taxonomies['product_shipping_class'] );
	unset( $taxonomies['yst_prominent_words'] );

	$page_types = [
		'single'  => array_keys( $post_types ),
		'archive' => array_merge( array_keys( $taxonomies ), [ 'author' ] ),
	];

	vd( $page_types );

	foreach ( $page_types as $page_type => $content_types ) {
		foreach ( $content_types as $content_type ) {
			$enable = false;
			$param  = 'single' === $page_type ? 'post_type' : 'taxonomy';
			$param  = 'author' === $content_type ? 'user_form' : $param;
			$param  = in_array( $content_type, [ '404', 'date', 'search' ], true ) ? $param = 'page' : $param;

			if ( '*' === $config ) {
				$enable = true;
			}

			if ( isset( $config[ $page_type ] ) && '*' === $config[ $page_type ] ) {
				$enable = true;
			}

			if ( isset( $config[ $page_type ] ) && is_array( $config[ $page_type ] ) && in_array( $content_type, $config[ $page_type ], true ) ) {
				$enable = true;
			}

			if ( $enable ) {
				$locations[] = [
					[
						'param'    => $param,
						'operator' => '==',
						'value'    => $content_type,
					],
				];
			}
		}
	}

	$field_data = [
		'key'                   => 'page_header',
		'title'                 => esc_html__( 'Page Header', 'mai-engine' ),
		'location'              => isset( $locations ) ? $locations : false,
		'fields'                => [
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

	acf_add_local_field_group( $field_data );
}
