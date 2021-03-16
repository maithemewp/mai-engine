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

	$fields         = [];
	$locations      = [];
	$singles        = mai_get_page_header_types( 'single' );
	$archives       = mai_get_page_header_types( 'archive' );
	$current_id     = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
	$page_for_posts = get_option( 'page_for_posts' );
	$has_blog       = in_array( 'post', $archives ) && $page_for_posts;

	foreach ( $singles as $type ) {
		$locations[] = [
			[
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $type,
			],
		];
	}

	foreach ( $archives as $type ) {
		$param = false;

		if ( 'author' === $type ) {
			$param = 'user_form';
		} elseif ( taxonomy_exists( $type ) ) {
			$param = 'taxonomy';
		}

		/**
		 * Bail if no param.
		 * The $type could be a post_type, which would be set in customizer instead.
		 */
		if ( ! $param ) {
			continue;
		}

		$locations[] = [
			[
				'param'    =>  $param,
				'operator' => '==',
				'value'    => $type,
			],
		];
	}

	if ( $has_blog ) {
		$locations[] = [
			[
				'param'    => 'page_type',
				'operator' => '==',
				'value'    => 'posts_page',
			],
		];
	}

	// Only show page header image field if not blog archive.
	if ( $has_blog && ( $current_id !== $page_for_posts ) ) {
		$fields[] = [
			'key'           => 'page_header_image',
			'label'         => esc_html__( 'Image', 'mai-engine' ),
			'name'          => 'page_header_image',
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'landscape-sm',
			'library'       => 'all',
		];
	}

	$fields[] = [
		'key'   => 'page_header_description',
		'label' => esc_html__( 'Description', 'mai-engine' ),
		'name'  => 'page_header_description',
		'type'  => 'textarea',
		'rows'  => '3',
	];

	$field_group_data = [
		'key'      => 'page_header_field_group',
		'title'    => esc_html__( 'Page Header', 'mai-engine' ),
		'location' => $locations ?: false,
		'position' => 'side',
		'fields'   => $fields,
	];

	acf_add_local_field_group( $field_group_data );
}
