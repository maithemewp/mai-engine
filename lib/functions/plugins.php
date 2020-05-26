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
		$data['settings']['submit_class'] = trim( $data['settings']['submit_class'] );
	}

	return $data;
}

add_filter( 'woocommerce_enqueue_styles', 'mai_dequeue_woocommerce_styles' );
/**
 * Disable WooCommerce styles.
 *
 * @since 0.1.0
 *
 * @param $enqueue_styles
 *
 * @return mixed
 */
function mai_dequeue_woocommerce_styles( $enqueue_styles ) {
	$styles = [
		'general',
		// 'layout',
		// 'smallscreen',
		// 'blocks',
	];

	foreach ( $styles as $style ) {
		unset( $enqueue_styles["woocommerce-$style"] );
	}

	return $enqueue_styles;
}

add_filter( 'woocommerce_style_smallscreen_breakpoint', 'mai_woocommerce_breakpoint' );
/**
 * Modifies the WooCommerce breakpoints.
 *
 * @since 0.1.0
 *
 * @return string Pixel width of the theme's breakpoint.
 */
function mai_woocommerce_breakpoint() {
	$breakpoint      = 'md';
	$current         = mai_site_layout( false );
	$sidebar_layouts = [
		'content-sidebar',
		'sidebar-content',
	];

	if ( in_array( $current, $sidebar_layouts, true ) ) {
		$breakpoint = 'lg';
	}

	return mai_get_breakpoint( $breakpoint );
}

/**
 * Trim zeros in price decimals.
 *
 * @since 0.1.0
 *
 * @return bool
 */
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

add_filter( 'genesis_attr_entries', 'mai_add_facetwp_template_class' );
/**
 * Add facetwp-template class to archives.
 *
 * @since 0.2.0
 *
 * @return array
 */
function mai_add_facetwp_template_class( $attributes ) {
	if ( class_exists( 'facetwp' ) ) {
		$attributes['class'] .= ' facetwp-template';
	}

	return $attributes;
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
 * @param array   The current query args.
 *
 * @return string
 */
function mai_facetwp_genesis_pager( $output, $params ) {
	$output      = '<ul>';
	$page        = (int) $params['page'];
	$total_pages = (int) $params['total_pages'];

	// Only show pagination when > 1 page.
	if ( 1 < $total_pages ) {

		if ( 1 < $page ) {
			$output .= sprintf( '<li><a class="facetwp-page" data-page="%s">%s</a></li>', ( $page - 1 ), esc_html__( '← Previous', 'mai-engine' ) );
		}
		if ( 3 < $page ) {
			$output .= '<li><a class="facetwp-page first-page" data-page="1"><span class="screen-reader-text">Page </span>1</a></li>';
			$output .= '<li class="pagination-omission">&hellip;</li>';
		}
		for ( $i = 2; $i > 0; $i-- ) {
			if ( 0 < ( $page - $i ) ) {
				$output .= '<li><a class="facetwp-page" data-page="' . ( $page - $i ) . '"><span class="screen-reader-text">Page </span>' . ( $page - $i ) . '</a></li>';
			}
		}

		// Current page.
		$output .= '<li class="active"><a class="facetwp-page" aria-label="Current page" data-page="' . $page . '"><span class="screen-reader-text">Page </span>' . $page . '</a></li>';

		for ( $i = 1; $i <= 2; $i++ ) {
			if ( $total_pages >= ( $page + $i ) ) {
				$output .= '<li><a class="facetwp-page" data-page="' . ( $page + $i ) . '"><span class="screen-reader-text">Page </span>' . ( $page + $i ) . '</a></li>';
			}
		}
		if ( $total_pages > ( $page + 2 ) ) {
			$output .= '<li class="pagination-omission">&hellip;</li>';
			$output .= '<li><a class="facetwp-page last-page" data-page="' . $total_pages . '"><span class="screen-reader-text">Page </span>' . $total_pages . '</a></li>';
		}
		if ( $page < $total_pages ) {
			$output .= sprintf( '<li><a class="facetwp-page" data-page="%s">%s</a></li>', ( $page + 1 ), esc_html__( 'Next →', 'mai-engine' ) );
		}
	}

	$output .= '</ul>';

	return $output;
}

add_filter( 'genesis_markup_archive-pagination_content', 'mai_facetwp_archive_pagination', 10, 2 );
/**
 * Add facetwp pager before genesis pagination.
 * This will only display if there are facets on the page.
 *
 * @since 0.2.0
 *
 * @param string $content The existing content.
 * @param array  $args    The genesis_markup() element args.
 *
 * @return string|HTML
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

	return $content . facetwp_display( 'pager' );
}

// Dependency installer labels.
add_filter( 'wp_dependency_dismiss_label', 'mai_get_name' );
add_filter( 'wp_dependency_required_row_meta', '__return_false' );

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Change plugin dependency text.
 *
 * @since 1.0.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Theme Dependency', 'mai-engine' )
	);

	return $actions;
}

add_filter( 'mai_plugin_dependencies', 'mai_require_genesis_connect', 10, 1 );
/**
 * Recommend Genesis Connect if WooCommerce or EDD installed.
 *
 * @since 1.0.0
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

	if ( class_exists( 'Easy_Digital_Downloads' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for EDD',
			'host'     => 'wordpress',
			'slug'     => 'easy-digital-downloads/easy-digital-downloads.php',
			'uri'      => 'https://wordpress.org/plugins/easy-digital-downloads/',
			'optional' => true,
		];
	}

	return $plugins;
}
