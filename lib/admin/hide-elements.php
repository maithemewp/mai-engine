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

add_action( 'init', 'mai_add_hide_elements_metabox', 20 );
/**
 * Register field group for the hide elements metabox.
 * This can't be on 'after_setup_theme' or 'acf/init' hook because it's too early,
 * and get_post_types() doesn't get all custom post types.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_add_hide_elements_metabox() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$post_types = array_keys( get_post_types() );
	$post_types = get_post_types( [ 'public' => true ] );
	unset( $post_types['attachment'] );
	$locations  = [];

	foreach ( $post_types as $post_type ) {
		$locations[] = [
			[
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $post_type,
			],
		];
	}

	$choices           = [];
	$page_header       = mai_get_config( 'page-header' );
	$breadcrumb_single = genesis_get_option( 'breadcrumb_single' );
	$breadcrumb_page   = genesis_get_option( 'breadcrumb_page' );
	$widget_areas      = wp_get_sidebars_widgets();
	$footer_widgets    = (int) mai_get_option( 'footer-widgets-widget-areas', get_theme_support( 'genesis-footer-widgets' )[0] );
	$menus             = get_theme_support( 'genesis-menus' )[0];

	if ( isset( $widget_areas['before-header'] ) ) {
		$choices['before_header'] = __( 'Before Header', 'mai-engine' );
	}

	$choices['site_header'] = __( 'Site Header', 'mai-engine' );

	if ( mai_get_option( 'site-header-sticky', current_theme_supports( 'sticky-header' ) ) ) {
		$choices['sticky_header'] = __( 'Sticky Header', 'mai-engine' );
	}

	if ( mai_get_option( 'site-header-transparent', current_theme_supports( 'transparent-header' ) ) ) {
		$choices['transparent_header'] = __( 'Transparent Header', 'mai-engine' );
	}

	if ( array_key_exists( 'after-header', $menus ) ) {
		$choices['after_header'] = __( 'After Header Menu', 'mai-engine' );
	}

	if ( '*' === $page_header || ( isset( $page_header['single'] ) && ! empty( $page_header['single'] ) ) ) {
		$choices['page_header'] = __( 'Page Header', 'mai-engine' );
	}

	if ( $breadcrumb_single || $breadcrumb_page ) {
		$choices['breadcrumbs'] = __( 'Breadcrumbs', 'mai-engine' );
	}

	$choices['entry_title']    = __( 'Entry Title', 'mai-engine' );
	$choices['entry_excerpt']  = __( 'Entry Excerpt', 'mai-engine' );
	$choices['featured_image'] = __( 'Featured Image', 'mai-engine' );

	if ( isset( $widget_areas['before-footer'] ) ) {
		$choices['before_footer'] = __( 'Before Footer', 'mai-engine' );
	}

	for ( $i = 1; $i <= $footer_widgets; $i++ ) {
		if ( isset( $widget_areas[ 'footer-' . $i ] ) ) {
			$choices['footer_widgets'] = __( 'Footer Widgets', 'mai-engine' );
		}
	}

	if ( mai_get_option( 'footer-credits-text', mai_default_footer_credits() ) ) {
		$choices['footer_credits'] = __( 'Footer Credits', 'mai-engine' );
	}

	acf_add_local_field_group(
		[
			'key'                   => 'hide_elements',
			'title'                 => __( 'Hide Elements', 'mai-engine' ),
			'menu_order'            => 10,
			'position'              => 'side',
			'label_placement'       => 'left',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'location'              => $locations,
			'fields'                => [
				[
					'key'               => 'hide_elements',
					'name'              => 'hide_elements',
					'type'              => 'checkbox',
					'instructions'      => __( 'Select elements to hide on this page.', 'mai-engine' ),
					'required'          => 0,
					'conditional_logic' => 0,
					'allow_custom'      => 0,
					'default_value'     => [],
					'layout'            => 'vertical',
					'toggle'            => 0,
					'return_format'     => 'value',
					'save_custom'       => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'choices'           => $choices,
				],
			],
		]
	);
}
