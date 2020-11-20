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
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

add_filter( 'woocommerce_cart_item_remove_link', 'mai_woocommerce_cart_item_remove_icon' );
/**
 * Replaces cart item remove link x with an svg.
 *
 * @since TBD
 *
 * @return string
 */
function mai_woocommerce_cart_item_remove_icon( $link ) {
	$svg = mai_get_svg_icon( 'times', 'light' );
	return str_replace( '&times;', $svg, $link );
}

add_filter( 'genesis_attr_entries', 'mai_add_facetwp_template_class', 10, 3 );
/**
 * Add facetwp-template class to archives.
 *
 * @since 0.2.0
 *
 * @param array  $attributes FacetWP template attributes.
 * @param string $context    Template context.
 * @param array  $args       Template args.
 *
 * @return array
 */
function mai_add_facetwp_template_class( $attributes, $context, $args ) {
	if ( ! class_exists( 'facetwp' ) ) {
		return $attributes;
	}

	if ( ! isset( $args['params']['args'] ) || ( 'archive' !== $args['params']['args']['context'] ) ) {
		return $attributes;
	}

	$attributes['class'] .= ' facetwp-template';

	return $attributes;
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
 * Change plugin dependency text.
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
 * Recommend Genesis Connect if WooCommerce or EDD installed.
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

/**
 * Ajax update cart contents total.
 *
 * @since TBD
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
 * @since TBD
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
	return sprintf( '<span class="mai-cart-total-wrap is-circle"><span class="mai-cart-total">%s</span></span>', WC()->cart->get_cart_contents_count() );
}
