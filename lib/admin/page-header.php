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

add_action( 'init', 'mai_add_page_header_metabox', 99 );
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
	$is_blog        = $has_blog && $page_for_posts && ( $current_id === $page_for_posts );

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
				'param'    => $param,
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
	if ( ! $is_blog ) {
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

add_filter( 'acf/load_field/key=page_header_image', 'mai_load_page_header_image_field' );
/**
 * Changes field label on Add New screen.
 *
 * @since TBD
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_load_page_header_image_field( $field ) {
	$screen = get_current_screen();

	if ( ! $screen || 'edit-tags' !== $screen->base ) {
		return $field;
	}

	$field['label'] = __( 'Page Header Image', 'mai-engine' );

	return $field;
}

add_filter( 'acf/load_field/key=page_header_description', 'mai_load_page_header_description_field' );
/**
 * Changes field label on Add New screen.
 *
 * @since TBD
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_load_page_header_description_field( $field ) {
	$screen = get_current_screen();

	if ( ! $screen || 'edit-tags' !== $screen->base ) {
		return $field;
	}

	$field['label'] = __( 'Page Header Description', 'mai-engine' );

	return $field;
}
