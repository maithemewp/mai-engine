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
	// add_filter( 'genesis_attr_archive-title', 'mai_page_header_archive_title_attr' );
	add_filter( 'genesis_attr_entry', 'mai_page_header_entry_attr' );
	add_filter( 'body_class', 'mai_page_header_body_class' );

	// add_action( 'mai_page_header', 'genesis_do_posts_page_heading' );
	// add_action( 'mai_page_header', 'genesis_do_date_archive_title' );
	// add_action( 'mai_page_header', 'genesis_do_taxonomy_title_description' );
	// add_action( 'mai_page_header', 'genesis_do_author_title_description' );
	// add_action( 'mai_page_header', 'genesis_do_cpt_archive_title_description' );
	// add_action( 'genesis_archive_title_descriptions', 'mai_do_archive_headings_intro_text', 12, 3 );

	add_action( 'mai_page_header', 'mai_do_page_header_title', 10 );
	add_action( 'mai_page_header', 'mai_do_page_header_description', 20 );
	add_action( 'be_title_toggle_remove', 'mai_page_header_title_toggle' );
	add_action( 'genesis_before_content', 'mai_page_header_remove_404_title' );
	add_action( 'genesis_before_content_sidebar_wrap', 'mai_do_page_header' );
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
 * Remove page header title if using Genesis Title Toggle plugin.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_page_header_title_toggle() {
	remove_action( 'mai_page_header', 'mai_do_page_header_title', 10 );
}

/**
 * Remove default title of 404 pages.
 *
 * @todo  is this needed with the new grid output stuff?
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
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_page_header_image() {
	$header = get_option( 'mai_page_header' );

	if ( ! $header || ! isset( $header['image'] ) || empty( $header['image'] ) ) {
		return;
	}

	echo mai_get_cover_image_html( $header['image'], [ 'class' => 'page-header-image' ] );
}

/**
 * Display title in page header.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_title() {
	$title = '';

	if ( is_singular() ) {
		$title = get_the_title();
	} elseif ( is_home() ) {
		if ( is_front_page() ) {
			$title = apply_filters( 'genesis_latest_posts_title', esc_html__( 'Latest Posts', 'mai-engine' ) );
		} else {
			$title = get_the_title( get_option( 'page_for_posts' ) );
		}
	} elseif ( is_post_type_archive() ) {
		if ( class_exists( 'WooCommerce' ) && is_shop() ) {
			$title = get_the_title( wc_get_page_id( 'shop' ) );
		} else {
			if ( genesis_has_post_type_archive_support( mai_get_post_type() ) ) {
				$title = genesis_get_cpt_option( 'headline' );
			}
			if ( ! $title ) {
				$title = post_type_archive_title( '', false );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		global $wp_query;
		$term = is_tax() ? get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ) : $wp_query->get_queried_object();
		if ( $term ) {
			$title = get_term_meta( $term->term_id, 'headline', true );
			if ( ! $title ) {
				$title = $term->name;
			}
		}
	} elseif ( is_search() ) {
		$title = apply_filters( 'genesis_search_title_text', esc_html__( 'Search results for: ', 'mai-engine' ) . get_search_query() );
	} elseif ( is_author() ) {
		$title = get_the_author_meta( 'headline', (int) get_query_var( 'author' ) );
		if ( ! $title ) {
			$title = get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) );
		}
	} elseif ( is_date() ) {
		if ( is_day() ) {
			$title = __( 'Archives for ', 'mai-theme-engine' ) . get_the_date();
		} elseif ( is_month() ) {
			$title = __( 'Archives for ', 'mai-theme-engine' ) . single_month_title( ' ', false );
		} elseif ( is_year() ) {
			$title = __( 'Archives for ', 'mai-theme-engine' ) . get_query_var( 'year' );
		}
	} elseif ( is_404() ) {
		$title = apply_filters( 'genesis_404_entry_title', esc_html__( 'Not found, error 404', 'mai-engine' ) );
	}

	$title = apply_filters( 'mai_page_header_title', $title );

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

/**
 * Display page description.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_page_header_description() {
	$description = '';

	if ( is_singular() ) {
		$description = get_post_meta( get_the_ID(), 'page_header_description', true );
	} elseif ( is_home() ) {
		if ( is_front_page() ) {
			$description = '';
		} else {
			$description = get_post_meta( get_option( 'page_for_posts' ), 'page_header_description', true );
		}
	} elseif ( is_post_type_archive() ) {
		if ( class_exists( 'WooCommerce' ) && is_shop() ) {
			$description = get_post_meta( wc_get_page_id( 'shop' ), 'page_header_description', true );
		} else {
			if ( genesis_has_post_type_archive_support( mai_get_post_type() ) ) {
				$description = genesis_get_cpt_option( 'intro_text' );
				$description = apply_filters( 'genesis_cpt_archive_intro_text_output', $description ? $description : '' );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		global $wp_query;
		$term = is_tax() ? get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ) : $wp_query->get_queried_object();
		if ( $term ) {
			$description = get_term_meta( $term->term_id, 'page_header_description', true );
			$description = apply_filters( 'genesis_term_intro_text_output', $description ? $description : '' );
		}
	} elseif ( is_search() ) {
		$description = apply_filters( 'genesis_search_title_text', esc_html__( 'Search results for: ', 'mai-engine' ) . get_search_query() );
	} elseif ( is_author() ) {
		$description = get_the_author_meta( 'headline', (int) get_query_var( 'author' ) );
		$description = apply_filters( 'genesis_author_intro_text_output', $description ? $description : '' );
		if ( ! $description ) {
			$description = get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) );
		}
	} elseif ( is_date() ) {
		$description = '';
	} elseif ( is_404() ) {
		$description = '';
	}

	$description = apply_filters( 'mai_page_header_description', $description );

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
				'context' => 'page-header-description',
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
	if ( is_singular() && ! did_action( 'genesis_entry_content' ) ) {
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
function mai_do_page_header() {
	genesis_markup(
		[
			'open'    => '<section %s role="banner">',
			'context' => 'page-header',
		]
	);

	mai_do_page_header_image();

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
