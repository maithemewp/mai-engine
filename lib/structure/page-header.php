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

add_action( 'genesis_meta', 'mai_page_header_setup' );
/**
 * Sets up page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_setup() {
	if ( ! mai_has_page_header() ) {
		return;
	}

	if ( mai_is_type_single() ) {
		remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
	}

	if ( is_404() ) {
		add_filter( 'genesis_markup_entry-title', '__return_empty_string' );
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
	add_filter( 'genesis_attr_entry', 'mai_page_header_entry_attr' );

	add_action( 'genesis_before_content_sidebar_wrap', 'mai_do_page_header' );
}

add_action( 'mai_before_page-header_wrap', 'mai_do_page_header_image' );
/**
 * Display the page header image.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_image() {
	$image_id = mai_get_page_header_image_id();

	if ( $image_id ) {
		echo mai_get_cover_image_html( $image_id, [ 'class' => 'page-header-image' ] );
	}
}

add_action( 'mai_before_page-header_wrap', 'mai_do_page_header_overlay' );
/**
 * Display the page header overlay if there is a page header image.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_overlay() {
	if ( ! mai_get_page_header_image_id() ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'page-header-overlay',
		]
	);
}

add_action( 'mai_page_header', 'mai_do_page_header_title' );
/**
 * Display title in page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_title() {
	if ( mai_is_element_hidden( 'entry_title' ) ) {
		return;
	}

	$title = mai_get_page_header_title();

	if ( $title ) {
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

add_action( 'mai_page_header', 'mai_do_page_header_description' );
/**
 * Display page description.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_description() {
	if ( mai_is_element_hidden( 'entry_excerpt' ) ) {
		return;
	}

	$description = mai_get_page_header_description();

	if ( $description ) {
		genesis_markup(
			[
				'open'    => '<div %s itemprop="description">',
				'close'   => '</div>',
				'content' => wpautop( $description ),
				'context' => 'page-header-description',
			]
		);
	}
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
	if ( is_singular() && ! did_action( 'genesis_entry_content' ) ) {
		$atts['itemref'] = 'page-header';
	}

	return $atts;
}

add_filter( 'genesis_structural_wrap-page-header', 'mai_page_header_divider', 10, 2 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param $output
 * @param $original_output
 *
 * @return string
 */
function mai_page_header_divider( $output, $original_output ) {
	$style = mai_get_option( 'page-header-divider', mai_get_config( 'page-header')['divider'] );

	if ( ! $style ) {
		return $output;
	}

	if ( 'close' === $original_output ) {
		$args = [
			'style'            => $style,
			'color'            => mai_get_option( 'page-header-divider-color', mai_get_color( 'lightest' ) ),
			'flip_horizontal'  => mai_get_option( 'page-header-divider-flip-horizontal', mai_get_config( 'page-header')['divider-flip-horizontal'] ),
			'flip_vertical'    => mai_get_option( 'page-header-divider-flip-vertical', mai_get_config( 'page-header')['divider-flip-vertical'] ),
			'height'           => 'md',
			'class'            => 'page-header-divider',
		];

		$output .= mai_get_divider( $args );
	}

	return $output;
}

/**
 * Display the page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header() {
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
