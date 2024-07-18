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

	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_open', 5 );
	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_close', 15 );

	if ( mai_has_title_in_page_header() ) {
		if ( mai_is_type_single() ) {
			remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
		}

		if ( is_404() ) {
			add_filter( 'genesis_markup_entry-title', '__return_empty_string' );
		}

		remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_headline', 10, 3 );
		remove_action( 'genesis_before_loop', 'genesis_do_posts_page_heading' );
		remove_action( 'genesis_before_loop', 'genesis_do_date_archive_title' );
		remove_action( 'genesis_before_loop', 'genesis_do_blog_template_heading' );
		remove_action( 'genesis_before_loop', 'genesis_do_author_title_description', 15 );
		remove_action( 'genesis_before_loop', 'genesis_do_cpt_archive_title_description' );
		remove_action( 'genesis_before_loop', 'genesis_do_search_title' );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		add_filter( 'woocommerce_show_page_title', '__return_null' );
		add_filter( 'genesis_search_title_output', '__return_false' );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$description = get_term_meta( get_queried_object_id(), 'page_header_description', true );
		$intro_text  = get_term_meta( get_queried_object_id(), 'intro_text', true );
		$intro_text  = apply_filters( 'genesis_term_intro_text_output', $intro_text ?: '' );

		if ( ! $description && $intro_text ) {
			// Remove archive-description wrap and intro text if intro text is used in page header.
			remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_open', 5 );
			remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_intro_text', 12 );
			remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_close', 15 );
		} elseif ( ! $intro_text ) {
			// Remove archive-description wrap if no intro text is displayed.
			remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_open', 5 );
			// remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_intro_text', 12 );
			remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_close', 15 );
		}
	}

	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	add_filter( 'genesis_attr_entry', 'mai_page_header_entry_attr' );

	// Do page header.
	add_action( 'genesis_before_content_sidebar_wrap', 'mai_do_page_header' );
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
			'open'    => '<section %s>',
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
		$image_size = mai_get_page_header_image_size();
		$filter     = function() { return false; };
		add_filter( 'wp_lazy_loading_enabled', $filter );
		echo wp_get_attachment_image( $image_id, $image_size, false,
			[
				'class' => 'page-header-image',
				'sizes' => '100vw',
			]
		);
		remove_filter( 'wp_lazy_loading_enabled', $filter );
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

	if ( $title && mai_has_title_in_page_header() ) {
		genesis_markup(
			[
				'open'    => '<h1 %s itemprop="headline">',
				'close'   => '</h1>',
				'content' => $title, // TODO: Sanitize this?
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
	$description = mai_get_page_header_description();

	if ( $description ) {
		genesis_markup(
			[
				'open'    => '<div %s itemprop="description">',
				'close'   => '</div>',
				'content' => mai_get_processed_content( $description ),
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

add_filter( 'genesis_attr_page-header-overlay', 'mai_page_header_divider_class', 10, 1 );
/**
 * The setting is for text color, so the class is the reverse.
 * If text is light, that means the background is dark.
 *
 * @since 0.3.0
 *
 * @param array $attr Divider attributes.
 *
 * @return array
 */
function mai_page_header_divider_class( $attr ) {
	$option = mai_get_option( 'page-header-divider', 'none' );

	if ( $option ) {
		$attr['class'] .= " has-$option-divider";
	}

	return $attr;
}

add_filter( 'genesis_structural_wrap-page-header', 'mai_page_header_wrap_class', 10, 2 );
/**
 * Adds page-header-wrap class.
 *
 * @since 1/4/21
 *
 * @param string $output          HTML output.
 * @param string $original_output Original HTML.
 *
 * @return string
 */
function mai_page_header_wrap_class( $output, $original_output ) {
	if ( 'open' === $original_output ) {
		$output = str_replace( '"wrap"', '"page-header-wrap wrap"', $output );
	}

	return $output;
}

add_filter( 'genesis_structural_wrap-page-header', 'mai_page_header_divider', 10, 2 );
/**
 * Display the page header divider.
 *
 * @since 0.1.0
 *
 * @param string $output          HTML output.
 * @param string $original_output Original HTML.
 *
 * @return string
 */
function mai_page_header_divider( $output, $original_output ) {
	$config = mai_get_config( 'settings' )['page-header'];
	$style  = mai_get_option( 'page-header-divider', $config['divider'] );

	if ( $style && 'close' === $original_output ) {
		$args = [
			'style'           => $style,
			'color'           => mai_get_option( 'page-header-divider-color', mai_get_color_value( $config['divider-color'] ) ),
			'flip_horizontal' => mai_get_option( 'page-header-divider-flip-horizontal', $config['divider-flip-horizontal'] ),
			'flip_vertical'   => mai_get_option( 'page-header-divider-flip-vertical', $config['divider-flip-vertical'] ),
			'height'          => mai_get_option( 'page-header-divider-height', $config['divider-height'] ),
			'class'           => 'page-header-divider',
			'align'           => 'full',
		];

		$output .= mai_get_divider( $args );
	}

	return $output;
}

add_filter( 'genesis_attr_page-header', 'mai_add_page_header_attributes' );
/**
 * Add page header attributes.
 *
 * @since 2.0.0
 * @since 2.26.0 Remove role="banner" as this should stay reserved for <header> element.
 *
 * @param array $attributes Page header element attributes.
 *
 * @return mixed
 */
function mai_add_page_header_attributes( $attributes ) {
	$attributes['id'] = 'page-header';

	if ( mai_get_page_header_image_id() ) {
		$attributes['class'] .= ' has-page-header-image';
	}

	if ( ! mai_has_light_page_header() ) {
		$attributes['class'] .= ' has-dark-background';
	}

	$default = mai_get_config( 'settings' )['page-header']['divider'];
	$divider = mai_get_option( 'page-header-divider', $default );

	if ( $divider ) {
		$attributes['class'] .= ' has-divider';
	}

	$attributes['class'] .= ' is-alignfull-first';

	return $attributes;
}

/**
 * Get the page header image ID.
 *
 * @since 0.3.0
 *
 * @return mixed
 */
function mai_get_page_header_image_id() {
	static $image_id = null;

	if ( ! is_null( $image_id ) ) {
		return $image_id;
	}

	$post_id = false;

	if ( is_singular() ) {
		$post_id  = get_the_ID();
		$image_id = get_post_meta( $post_id, 'page_header_image', true );

	} elseif ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
		$post_id = get_option( 'page_on_front' );
		if ( $post_id ) {
			$image_id = get_post_meta( $post_id, 'page_header_image', true );
		}
	}
	// elseif ( is_home() && 'page' === get_option( 'show_on_front' ) ) {
	// 	$post_id = get_option( 'page_for_posts' );
	// 	if ( $post_id ) {
	// 		$image_id = get_post_meta( $post_id, 'page_header_image', true );
	// 	}
	// }
	elseif ( mai_is_type_archive() ) {
		if ( is_category() || is_tag() || is_tax() ) {

			/**
			 * Query.
			 *
			 * @var WP_Query $wp_query Query object.
			 */
			global $wp_query;

			$term = is_tax() ? get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ) : $wp_query->get_queried_object();

			if ( $term ) {
				$image_id = get_term_meta( $term->term_id, 'page_header_image', true );
			}
		}
	}

	if ( ! $image_id ) {
		$args = mai_get_template_args();

		if ( isset( $args['page-header-image'] ) && ! empty( $args['page-header-image'] ) ) {
			$image_id = $args['page-header-image'];
		}
	}

	// We can't use is_home() to check for featured image because the args return archive settings.
	if ( ! $image_id && $post_id && ( is_singular() || is_front_page() ) ) {
		$args = mai_get_template_args();

		if ( isset( $args['page-header-featured'] ) && $args['page-header-featured'] ) {
			$image_id = get_post_thumbnail_id( $post_id );
		}
	}

	if ( ! $image_id && mai_get_option( 'page-header-image' ) ) {
		$image_id = mai_get_option( 'page-header-image' );
	}

	if ( ! $image_id && mai_get_config( 'settings' )['page-header']['image'] ) {
		$image_id = mai_get_config( 'settings' )['page-header']['image'];
	}

	$image_id = apply_filters( 'mai_page_header_image', $image_id );

	return $image_id;
}

/**
 * Get the page header title.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_get_page_header_title() {
	static $title = null;

	if ( ! is_null( $title ) ) {
		return $title;
	}

	if ( is_singular() ) {
		$title = get_the_title();

	} elseif ( is_front_page() ) {
		// This would only run if front page is not a static page, since is_singular() is first.
		$title = apply_filters( 'genesis_latest_posts_title', esc_html__( 'Latest Posts', 'mai-engine' ) );

	} elseif ( is_home() ) {
		// This would only run if front page and blog page are static pages, since is_front_page() is first.
		$title = get_the_title( get_option( 'page_for_posts' ) );

	} elseif ( class_exists( 'WooCommerce' ) && is_shop() ) {
		$title = get_the_title( wc_get_page_id( 'shop' ) );

	} elseif ( is_post_type_archive() && genesis_has_post_type_archive_support( mai_get_post_type() ) ) {
		$title = genesis_get_cpt_option( 'headline' );

		if ( ! $title ) {
			$title = post_type_archive_title( '', false );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {

		/**
		 * WP Query.
		 *
		 * @var WP_Query $wp_query WP Query object.
		 */
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
		$title = __( 'Archives for ', 'mai-engine' );

		if ( is_day() ) {
			$title .= get_the_date();

		} elseif ( is_month() ) {
			$title .= single_month_title( ' ', false );

		} elseif ( is_year() ) {
			$title .= get_query_var( 'year' );
		}
	} elseif ( is_404() ) {
		$title = apply_filters( 'genesis_404_entry_title', esc_html__( 'Not found, error 404', 'mai-engine' ) );
	}

	$title = apply_filters( 'mai_page_header_title', $title );

	return $title;
}

/**
 * Get the page header description.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_get_page_header_description() {
	static $description = null;

	if ( ! is_null( $description ) ) {
		return $description;
	}

	if ( is_front_page() ) {
		$static_home = get_option( 'page_on_front' );
		$description = $static_home ? get_post_meta( $static_home, 'page_header_description', true ) : '';

	} elseif ( is_singular() ) {
		$description = get_post_meta( get_the_ID(), 'page_header_description', true );

	} elseif ( is_home() ) {
		$static_blog = get_option( 'page_for_posts' );
		$description = $static_blog ? get_post_meta( $static_blog, 'page_header_description', true ) : '';

	} elseif ( class_exists( 'WooCommerce' ) && is_shop() ) {
		$description = get_post_meta( wc_get_page_id( 'shop' ), 'page_header_description', true );

	} elseif ( is_post_type_archive() && genesis_has_post_type_archive_support( mai_get_post_type() ) ) {
		$description = genesis_get_cpt_option( 'intro_text' );
		$description = apply_filters( 'genesis_cpt_archive_intro_text_output', $description ? $description : '' );

	} elseif ( is_category() || is_tag() || is_tax() ) {

		/**
		 * WP Query.
		 *
		 * @var WP_Query $wp_query WP Query object.
		 */
		global $wp_query;

		$term = is_tax() ? get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ) : $wp_query->get_queried_object();

		if ( $term && ! mai_is_element_hidden( 'entry_excerpt' ) ) {
			$description = get_term_meta( $term->term_id, 'page_header_description', true );
			$description = $description ? $description : get_term_meta( $term->term_id, 'intro_text', true ); // Fallback to intro text.
			$description = apply_filters( 'genesis_term_intro_text_output', $description ? $description : '' );
		}
	} elseif ( is_search() ) {
		$description = '';

	} elseif ( is_author() ) {
		$description = get_the_author_meta( 'intro_text', (int) get_query_var( 'author' ) );
		$description = apply_filters( 'genesis_author_intro_text_output', $description ? $description : '' );

	} elseif ( is_date() ) {

		$description = '';
	} elseif ( is_404() ) {
		$description = '';
	}

	$description = apply_filters( 'mai_page_header_description', $description );

	return $description;
}
