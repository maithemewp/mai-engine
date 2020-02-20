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

add_action( 'genesis_meta', 'mai_page_header_setup' );
/**
 * Sets up page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_setup() {
	if ( ! mai_is_page_header_active() ) {
		return;
	}

	if ( genesis_entry_header_hidden_on_current_page() ) {
		return;
	}

	if ( mai_is_type_single() ) {
		remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
	}

	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_open', 5 );
	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_close', 15 );
	remove_action( 'genesis_before_loop', 'genesis_do_posts_page_heading' );
	remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_open', 5 );
	remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_close', 15 );
	remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_intro_text', 12 );
	remove_action( 'genesis_before_loop', 'genesis_do_date_archive_title' );
	remove_action( 'genesis_before_loop', 'genesis_do_blog_template_heading' );
	remove_action( 'genesis_before_loop', 'genesis_do_taxonomy_title_description', 15 );
	remove_action( 'genesis_before_loop', 'genesis_do_author_title_description', 15 );
	remove_action( 'genesis_before_loop', 'genesis_do_cpt_archive_title_description' );
	remove_action( 'genesis_before_loop', 'genesis_do_search_title' );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

	remove_filter( 'genesis_term_intro_text_output', 'wpautop' );
	remove_filter( 'genesis_author_intro_text_output', 'wpautop' );
	remove_filter( 'genesis_cpt_archive_intro_text_output', 'wpautop' );

	add_filter( 'woocommerce_show_page_title', '__return_null' );
	add_filter( 'genesis_search_title_output', '__return_false' );
	add_filter( 'genesis_attr_archive-title', 'mai_page_header_archive_title_attr' );
	add_filter( 'genesis_attr_entry', 'mai_page_header_entry_attr' );
	add_filter( 'body_class', 'mai_page_header_body_class' );

	add_action( 'mai_page_header', 'genesis_do_posts_page_heading' );
	add_action( 'mai_page_header', 'genesis_do_date_archive_title' );
	add_action( 'mai_page_header', 'genesis_do_taxonomy_title_description' );
	add_action( 'mai_page_header', 'genesis_do_author_title_description' );
	add_action( 'mai_page_header', 'genesis_do_cpt_archive_title_description' );
	add_action( 'genesis_archive_title_descriptions', 'mai_do_archive_headings_intro_text', 12, 3 );
	add_action( 'mai_page_header', 'mai_page_header_title', 10 );
	add_action( 'mai_page_header', 'mai_page_header_excerpt', 20 );
	add_action( 'be_title_toggle_remove', 'mai_page_header_title_toggle' );
	add_action( 'genesis_before_content', 'mai_page_header_remove_404_title' );
	add_action( 'genesis_before_content_sidebar_wrap', 'mai_page_header_display' );

	if ( ! is_customize_preview() && is_front_page() ) {
		add_action( 'genesis_before_page-header_wrap', 'the_custom_header_markup' );
	}
}

/**
 * Adds page-header utility class to body element.
 *
 * @since 0.1.0
 *
 * @param array $classes List of body classes.
 *
 * @return array
 */
function mai_page_header_body_class( $classes ) {
	$classes   = array_diff( $classes, [ 'no-page-header' ] );
	$classes[] = 'has-page-header';

	return $classes;
}

/**
 * Remove default title of 404 pages.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_remove_404_title() {
	if ( is_404() ) {
		add_filter( 'genesis_markup_entry-title_open', '__return_false' );
		add_filter( 'genesis_markup_entry-title_content', '__return_false' );
		add_filter( 'genesis_markup_entry-title_close', '__return_false' );
	}
}

/**
 * Display title in page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_title() {
	if ( class_exists( 'WooCommerce' ) && is_shop() ) {
		$title = get_the_title( wc_get_page_id( 'shop' ) );

	} elseif ( is_home() && 'posts' === get_option( 'show_on_front' ) ) {
		$title = apply_filters( 'genesis_latest_posts_title', esc_html__( 'Latest Posts', 'mai-engine' ) );

	} elseif ( is_404() ) {
		$title = apply_filters( 'genesis_404_entry_title', esc_html__( 'Not found, error 404', 'mai-engine' ) );

	} elseif ( is_search() ) {
		$title = apply_filters( 'genesis_search_title_text', esc_html__( 'Search results for: ', 'mai-engine' ) . get_search_query() );

	} elseif ( is_singular() ) {
		$title = get_the_title();
	}

	if ( isset( $title ) && $title ) {
		genesis_markup(
			[
				'open'    => '<h1 %s itemprop="headline">',
				'close'   => '</h1>',
				'content' => $title,
				'context' => 'page-header-title',
			]
		);
	}
}

/**
 * Display page excerpt.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_excerpt() {
	$excerpt = '';
	$id      = '';

	if ( class_exists( 'WooCommerce' ) && is_shop() ) {
		ob_start();
		woocommerce_result_count();
		$excerpt = ob_get_clean();

	} elseif ( is_home() && 'posts' === get_option( 'show_on_front' ) ) {
		$excerpt = apply_filters( 'genesis_latest_posts_subtitle', esc_html__( 'Showing the latest posts', 'mai-engine' ) );

	} elseif ( is_home() ) {
		$id = get_option( 'page_for_posts' );

	} elseif ( is_search() ) {

		// phpcs:ignore WordPress.VIP.RestrictedFunctions.get_page_by_path_get_page_by_path
		$id = get_page_by_path( 'search' );

	} elseif ( is_404() ) {

		// phpcs:ignore WordPress.VIP.RestrictedFunctions.get_page_by_path_get_page_by_path
		$id = get_page_by_path( 'error-404' );

	} elseif ( ( is_singular() ) && ! is_singular( 'product' ) ) {
		$id = get_the_ID();
	}

	if ( $id ) {
		$excerpt = has_excerpt( $id ) ? do_shortcode( get_the_excerpt( $id ) ) : '';
	}

	if ( $excerpt ) {
		genesis_markup(
			[
				'open'    => '<p %s itemprop="description">',
				'close'   => '</p>',
				'content' => $excerpt,
				'context' => 'page-header-subtitle',
			]
		);
	}
}

/**
 * Add intro text for archive headings to archive pages.
 *
 * @since 0.1.0
 *
 * @param string $heading    Optional. Archive heading, default is empty string.
 * @param string $intro_text Optional. Archive intro text, default is empty string.
 * @param string $context    Optional. Archive context, default is empty string.
 *
 * @return void
 */
function mai_do_archive_headings_intro_text( $heading = '', $intro_text = '', $context = '' ) {

	if ( $context && $intro_text ) {
		genesis_markup(
			[
				'open'    => '<p %s itemprop="description">',
				'close'   => '</p>',
				'content' => $intro_text,
				'context' => 'page-header-subtitle',
			]
		);
	}
}

/**
 * Adds attributes to page-header archive title markup.
 *
 * @since 0.1.0
 *
 * @param array $atts Page Header title attributes.
 *
 * @return array
 */
function mai_page_header_archive_title_attr( $atts ) {
	$atts['class']    = 'page-header-title';
	$atts['itemprop'] = 'headline';

	return $atts;
}

/**
 * Adds attributes to page header markup.
 *
 * @since 0.1.0
 *
 * @param array $atts Page Header entry attributes.
 *
 * @return array
 */
function mai_page_header_entry_attr( $atts ) {
	if ( is_singular() ) {
		$atts['itemref'] = 'page-header';
	}

	return $atts;
}

/**
 * Display the page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_display() {
	genesis_markup(
		[
			'open'    => '<section %s role="banner">',
			'context' => 'page-header',
		]
	);

	genesis_structural_wrap( 'page-header', 'open' );

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'page-header-inner',
		]
	);

	do_action( 'mai_page_header' );

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'page-header-inner',
		]
	);

	genesis_structural_wrap( 'page-header', 'close' );

	genesis_markup(
		[
			'close'   => '</section>',
			'context' => 'page-header',
		]
	);
}
