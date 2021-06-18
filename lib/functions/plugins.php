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

add_action( 'wp_enqueue_scripts', 'mai_remove_simple_social_icons_css', 15 );
/**
 * Remove Simple Social Icons CSS.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_simple_social_icons_css() {
	mai_deregister_asset( 'simple-social-icons-font' );
}

add_filter( 'wpforms_settings_defaults', 'mai_wpforms_default_css' );
/**
 * Set the default WP Forms styling to "Base styling only".
 * This still requires the user to actually save the settings before it applies.
 *
 * @since 0.1.0
 *
 * @param array $defaults The settings defaults.
 *
 * @return array
 */
function mai_wpforms_default_css( $defaults ) {
	if ( isset( $defaults['general']['disable-css']['default'] ) ) {
		$defaults['general']['disable-css']['default'] = 2;
	}

	return $defaults;
}

add_filter( 'wpforms_frontend_form_data', 'mai_wpforms_default_button_class' );
/**
 * Add default button class to WP Forms.
 *
 * @since 0.1.0
 *
 * @param array $data The form data.
 *
 * @return array
 */
function mai_wpforms_default_button_class( $data ) {
	if ( isset( $data['settings']['submit_class'] ) && ! mai_has_string( 'button', $data['settings']['submit_class'] ) ) {
		$data['settings']['submit_class'] .= ' button';
		$data['settings']['submit_class']  = trim( $data['settings']['submit_class'] );
	}

	return $data;
}

add_filter( 'woocommerce_enqueue_styles', 'mai_dequeue_woocommerce_styles' );
/**
 * Disable WooCommerce styles.
 *
 * @since 0.1.0
 *
 * @param array $enqueue_styles Woo styles.
 *
 * @return mixed
 */
function mai_dequeue_woocommerce_styles( $enqueue_styles ) {
	$styles = [
		'general',
	];

	foreach ( $styles as $style ) {
		unset( $enqueue_styles[ "woocommerce-$style" ] );
	}

	return $enqueue_styles;
}

add_filter( 'woocommerce_style_smallscreen_breakpoint', 'mai_woocommerce_breakpoint' );
/**
 * Modifies the WooCommerce breakpoints.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_woocommerce_breakpoint() {
	$breakpoint      = 'md';
	$current         = mai_site_layout();
	$sidebar_layouts = [
		'content-sidebar',
		'sidebar-content',
	];

	if ( in_array( $current, $sidebar_layouts, true ) ) {
		$breakpoint = 'lg';
	}

	return mai_get_unit_value( mai_get_breakpoint( $breakpoint ), 'px' );
}

add_filter( 'woocommerce_product_loop_start', 'mai_product_loop_start_columns' );
/**
 * Adds column count as inline custom properties.
 *
 * @since 1/4/21
 *
 * @param array $html The existing loop start HTML.
 *
 * @return string
 */
function mai_product_loop_start_columns( $html ) {
	$count = wc_get_loop_prop( 'columns' );

	if ( ! is_numeric( $count ) ) {
		return $html;
	}

	$dom   = mai_get_dom_document( $html );
	$first = mai_get_dom_first_child( $dom );

	if ( ! $first ) {
		return $html;
	}

	// Get the columns breakpoint array.
	$columns = mai_get_breakpoint_columns(
		[
			'columns' => $count,
		]
	);

	$style  = $first->getAttribute( 'style' );
	$style .= sprintf( '--columns-xs:%s;', $columns['xs'] );
	$style .= sprintf( '--columns-sm:%s;', $columns['sm'] );
	$style .= sprintf( '--columns-md:%s;', $columns['md'] );
	$style .= sprintf( '--columns-lg:%s;', $columns['lg'] );

	$first->setAttribute( 'style', $style );

	return str_replace( '</ul>', '', $dom->saveHTML() );
}

add_filter( 'woocommerce_pagination_args', 'mai_woocommerce_pagination_previous_next_text' );
/**
 * Changes the adjacent entry previous and next link text.
 *
 * @since 1/4/21
 *
 * @param array $args The pagination args.
 *
 * @return array
 */
function mai_woocommerce_pagination_previous_next_text( $args ) {
	$args['prev_text'] = esc_html__( '← Previous', 'mai-engine' );
	$args['next_text'] = esc_html__( 'Next →', 'mai-engine' );

	return $args;
}

/**
 * Filter single product post_class.
 * Make sure it's only run on the main product entry wrap.
 *
 * @since 2.4.3
 *
 * @param array $classes The existing classes.
 *
 * @return array
 */
add_action( 'woocommerce_before_single_product', function() {
	add_filter( 'post_class', 'mai_woocommerce_product_single_class' );
});
add_action( 'woocommerce_before_single_product_summary', function() {
	remove_filter( 'post_class', 'mai_woocommerce_product_single_class' );
});

/**
 * Adds product single class.
 *
 * @since 2.4.3
 *
 * @param array $classes The existing classes.
 *
 * @return array
 */
function mai_woocommerce_product_single_class( $classes ) {
	$classes[] = 'product-single';
	return $classes;
}

/**
 * Trim zeros in price decimals.
 *
 * @since 0.1.0
 *
 * @return bool
 */
add_filter( 'woocommerce_price_trim_zeros', '__return_true', 8 );

add_filter( 'woocommerce_cart_item_remove_link', 'mai_woocommerce_cart_item_remove_icon' );
/**
 * Replaces cart item remove link x with an svg.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_woocommerce_cart_item_remove_icon( $link ) {
	$svg = mai_get_svg_icon( 'times', 'light' );
	return str_replace( '&times;', $svg, $link );
}

add_filter( 'facetwp_shortcode_html', 'mai_facetwp_page_wrap', 10, 2 );
/**
 * Add the wrap class to facetwp pager element.
 *
 * @since 0.3.11
 *
 * @param string $html The shortcode HTML.
 * @param array  $atts The shortcode attributes.
 *
 * @return string
 */
function mai_facetwp_page_wrap( $html, $atts ) {
	if ( ! ( isset( $atts['pager'] ) && $atts['pager'] ) ) {
		return $html;
	}

	$html = str_replace( 'facetwp-pager', 'wrap facetwp-pager', $html );

	return $html;
}

add_filter( 'facetwp_pager_html', 'mai_facetwp_genesis_pager', 10, 2 );
/**
 * Style pagination to look like Genesis.
 *
 * @since 0.2.0
 *
 * @link  https://gist.github.com/JiveDig/b9810ba4c322d7757993159ed9ccb61f
 * @link  https://halfelf.org/2017/facetwp-genesis-pagination/
 * @link  https://gist.github.com/mgibbs189/69176ef41fa4e26d1419
 *
 * @param string $output The pager HTML.
 * @param array  $params The current query args.
 *
 * @return string
 */
function mai_facetwp_genesis_pager( $output, $params ) {
	$output      = '<ul>';
	$page        = (int) $params['page'];
	$total_pages = (int) $params['total_pages'];
	$go_to_page  = __( 'Go to page', 'mai-engine' );

	// Only show pagination when more than one page.
	if ( $total_pages > 1 ) {

		if ( 1 < $page ) {
			$output .= sprintf( '<li class="pagination-previous"><a class="facetwp-page button button-secondary" data-page="%s">%s</a>&nbsp;</li>', ( $page - 1 ), esc_html__( '← Previous', 'mai-engine' ) );
		}
		if ( 3 < $page ) {
			$output .= '<li><a class="facetwp-page button button-secondary first-page" data-page="1"><span class="screen-reader-text">' . $go_to_page . '</span> 1</a>&nbsp;</li>';
			$output .= '<li class="pagination-omission">&hellip;</li>';
		}
		for ( $i = 2; $i > 0; $i-- ) {
			if ( 0 < ( $page - $i ) ) {
				$output .= '<li><a class="facetwp-page button button-secondary" data-page="' . ( $page - $i ) . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . ( $page - $i ) . '</a>&nbsp;</li>';
			}
		}

		// Current page.
		$output .= '<li class="active"><a class="facetwp-page button" aria-label="Current page" data-page="' . $page . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . $page . '</a>&nbsp;</li>';

		for ( $i = 1; $i <= 2; $i++ ) {
			if ( $total_pages >= ( $page + $i ) ) {
				$output .= '<li><a class="facetwp-page button button-secondary" data-page="' . ( $page + $i ) . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . ( $page + $i ) . '</a>&nbsp;</li>';
			}
		}
		if ( $total_pages > ( $page + 2 ) ) {
			$output .= '<li class="pagination-omission">&hellip;&nbsp;</li>';
			$output .= '<li><a class="facetwp-page button button-secondary last-page" data-page="' . $total_pages . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . $total_pages . '</a>&nbsp;</li>';
		}
		if ( $page < $total_pages ) {
			$output .= sprintf( '<li class="pagination-next"><a class="facetwp-page button button-secondary" data-page="%s">%s</a>&nbsp;</li>', ( $page + 1 ), esc_html__( 'Next →', 'mai-engine' ) );
		}
	}

	$output .= '<style type="text/css">.archive-pagination .wrap.facetwp-pager + .wrap { display:none; } .facetwp-pager .facetwp-page{ display:inline-block; margin:0; padding:var(--button-padding); }</style>';

	$output .= '</ul>';

	return $output;
}

add_filter( 'genesis_markup_archive-pagination_content', 'mai_facetwp_archive_pagination', 20, 2 );
/**
 * Add facetwp pager before genesis pagination.
 * This will only display if there are facets on the page.
 *
 * @since 0.2.0
 *
 * @param string $content The existing content.
 * @param array  $args    The genesis_markup() element args.
 *
 * @return string
 */
function mai_facetwp_archive_pagination( $content, $args ) {
	if ( ! function_exists( 'facetwp_display' ) ) {
		return $content;
	}

	if ( ! mai_is_type_archive() ) {
		return $content;
	}

	if ( ! $args['open'] ) {
		return $content;
	}

	return facetwp_display( 'pager' ) . $content;
}

// Dependency installer labels.
add_filter( 'wp_dependency_dismiss_label', 'mai_get_name' );
add_filter( 'wp_dependency_required_row_meta', '__return_false' );

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Changes plugin dependency text.
 *
 * @since 0.1.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Mai Theme Dependency', 'mai-engine' )
	);

	return $actions;
}

add_filter( 'mai_plugin_dependencies', 'mai_require_genesis_connect', 10, 1 );
/**
 * Recommend Genesis Connect if WooCommerce is installed.
 *
 * @since 0.1.0
 *
 * @param array $plugins List of plugin dependencies.
 *
 * @return array
 */
function mai_require_genesis_connect( $plugins ) {
	if ( class_exists( 'WooCommerce' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for WooCommerce',
			'host'     => 'wordpress',
			'slug'     => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'      => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'optional' => true,
		];
	}

	return $plugins;
}

add_action( 'admin_bar_menu', 'mai_woocommerce_edit_shop_link', 90 );
/**
 * Adds toolbar link to edit the shop page when view the shop archive.
 *
 * @since 2.10.0
 *
 * @param object $wp_admin_bar
 *
 * @return void
 */
function mai_woocommerce_edit_shop_link( $wp_admin_bar ) {
	if ( is_admin() ) {
		return;
	}

	if ( ! ( class_exists( 'WooCommerce' ) && function_exists( 'is_shop' ) && is_shop() ) ) {
		return;
	}

	$page_id = get_option( 'woocommerce_shop_page_id' );

	if ( ! $page_id ) {
		return;
	}

	$wp_admin_bar->add_node( [
		'id'    => 'mai-woocommerce-shop-page',
		'title' => '<span class="ab-icon dashicons dashicons-edit" style="margin-top:2px;"></span><span class="ab-label">' . __( 'Edit Page', 'mai-engine' ) . '</span>',
		'href'  => get_edit_post_link( $page_id, false ),
	] );
}

/**
 * Ajax update cart contents total.
 *
 * @since 2.7.0
 *
 * @param array $fragments The existing fragment elements to update.
 *
 * @return array
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'mai_cart_total_fragment' );
function mai_cart_total_fragment( $fragments ) {
	$fragments['mai-cart-total'] = mai_get_cart_total();
	return $fragments;
}

/**
 * Gets a cart total that is ajax updated when new products are added to cart.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_get_cart_total() {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}

	$cart = WC()->cart;
	if ( ! $cart ) {
		return;
	}

	$total = WC()->cart->get_cart_contents_count();
	$total = $total ?: '';

	return sprintf( '<span class="mai-cart-total-wrap is-circle"><span class="mai-cart-total">%s</span></span>', $total );
}

add_filter( 'ssp_register_post_type_args', 'mai_ssp_add_settings' );
/**
 * Adds support for mai settings in Seriously Simple Podcasting.
 *
 * @since TBD
 *
 * @param array $args The existing post type args.
 *
 * @return array
 */
function mai_ssp_add_settings( $args ) {
	$args['supports'][] = 'genesis-cpt-archives-settings';
	$args['supports'][] = 'mai-archive-settings';
	$args['supports'][] = 'mai-single-settings';
	$args['supports']   = array_unique( $args['supports'] );

	return $args;
}

add_filter( 'mai_get_option_archive-settings', 'mai_learndash_add_settings' );
add_filter( 'mai_get_option_single-settings', 'mai_learndash_add_settings' );
/**
 * Forces learndash courses post type to use archive/single settings.
 *
 * @since 2.10.0
 *
 * @param array $post_type The post types to for loop settings.
 *
 * @return array
 */
function mai_learndash_add_settings( $post_types ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $post_types;
	}

	$post_types[] = 'sfwd-courses';

	return array_unique( $post_types );
}

add_filter( 'mai_content_archive_settings', 'mai_learndash_course_archive_settings', 10, 2 );
/**
 * Removes posts_per_page setting from courses,
 * since learndash has it's own settings for this.
 *
 * @since 2.10.0
 *
 * @param array $settings The existing settings.
 * @param string $name    The content type name.
 *
 * @return array
 */
function mai_learndash_course_archive_settings( $settings, $name ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $settings;
	}

	if ( 'sfwd-courses' === $name ) {
		foreach ( $settings as $index => $setting ) {
			if ( 'posts_per_page' !== $setting['settings'] ) {
				continue;
			}

			unset( $settings[ $index ] );
		}
	}

	return $settings;
}

add_filter( 'mai_archive_args_name', 'mai_learndash_course_settings_name', 8 );
add_filter( 'mai_single_args_name', 'mai_learndash_course_settings_name', 8 );
/**
 * Uses course single/archive content settings for lessons, topics, quizes, and certificates.
 *
 * @since 2.10.0
 *
 * @param string $name The args name.
 *
 * @return string
 */
function mai_learndash_course_settings_name( $name ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $name;
	}

	$learndash_cpts = array_flip( [ 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-certificates' ] );

	if ( isset( $learndash_cpts[ $name ] ) ) {
		return 'sfwd-courses';
	}

	return $name;
}

add_filter( 'learndash_previous_post_link', 'mai_learndash_adjacent_post_link', 10, 4 );
add_filter( 'learndash_next_post_link', 'mai_learndash_adjacent_post_link', 10, 4 );
/**
 * Adds button classes to adjacent post links on LearnDash content.
 *
 * @since 2.10.0
 *
 * @param string $link      The link HTML.
 * @param string $permalink The link uri.
 * @param string $link_name The link text.
 * @param WP_Post $post     The adjacent post object.
 *
 * @since 2.10.0
 */
function mai_learndash_adjacent_post_link( $link, $permalink, $link_name, $post ) {
	$link = str_replace( 'prev-link', 'prev-link button button-secondary button-small', $link );
	$link = str_replace( 'next-link', 'next-link button button-secondary button-small', $link );
	return $link;
}
