<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'after_setup_theme', 'mai_add_page_header_metabox' );
/**
 * Description of expected behavior.
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
	$page_types = [
		'single'  => array_merge( array_keys( get_post_types() ), [
			'404',
		] ),
		'archive' => array_merge( array_keys( get_taxonomies() ), [
			'author',
			'date',
			'search',
		] ),
	];

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
		'title'                 => 'Page Header',
		'location'              => isset( $locations ) ? $locations : false,
		'menu_order'            => 0,
		'position'              => 'side',
		'style'                 => 'seamless',
		'label_placement'       => 'left',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
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
