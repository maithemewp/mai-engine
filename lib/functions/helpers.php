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

/**
 * Checks if in dev mode.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_is_in_dev_mode() {
	static $dev_mode = null;

	if ( ! is_null( $dev_mode ) ) {
		return $dev_mode;
	}

	$dev_mode = genesis_is_in_dev_mode() || defined( 'WP_DEBUG' ) && WP_DEBUG;

	return $dev_mode;
}

/**
 * Checks if the current request is in the editor.
 * For use in block callbacks.
 *
 * @since 2.36.1
 *
 * @return bool
 */
function mai_is_editor() {
	$editor = defined('REST_REQUEST') && true === REST_REQUEST && 'edit' === filter_input( INPUT_GET, 'context', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$editor = is_admin() || $editor;

	return $editor;
}

/**
 * Checks if the current site scheme is https.
 *
 * @since 2.6.0
 *
 * @access private
 *
 * @return bool
 */
function mai_is_https() {
	static $https = null;

	if ( ! is_null( $https ) ) {
		return $https;
	}

	$url   = wp_parse_url( home_url() );
	$https = 'https' === $url['scheme'];

	return $https;
}

/**
 * Helper function for debugging.
 *
 * @since 0.3.0
 *
 * @param mixed  $data     Data to dump.
 * @param string $function Debug function to use. Defaults to 's'.
 * @param string $hook     Hook to run function on.
 * @param int    $priority Priority of hook.
 *
 * @return void
 */
function mai_debug( $data, $function = 's', $hook = 'after_setup_theme', $priority = 999 ) {
	if ( ! mai_is_in_dev_mode() ) {
		return;
	}

	add_action(
		$hook,
		function () use ( $data, $function ) {
			if ( function_exists( $function ) ) {
				$function( $data );
			} else {
				echo '<pre style="margin:1em;padding:1em;background:#222;color:#eee;font-size:small">';
				is_array( $data ) ? print_r( $data ) : var_dump( $data ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				echo '</pre>';
			}
		},
		$priority
	);
}

/**
 * Check if a string contains at least one specified string.
 *
 * @since 2.1.1 Added array support in needle.
 * @since 0.1.0
 *
 * @param string|array $needle   String or array of strings to check for.
 * @param string       $haystack String to check in.
 *
 * @return string
 */
function mai_has_string( $needle, $haystack ) {
	if ( ! $haystack ) {
		return false;
	}

	if ( is_array( $needle ) ) {
		foreach ( $needle as $string ) {
			if ( str_contains( $haystack, $string ) ) {
				return true;
			}
		}

		return false;
	}

	return str_contains( $haystack, $needle );
}

/**
 * Check if an array key is set, or return a default.
 *
 * @since 0.3.0
 *
 * @param array  $array   Haystack.
 * @param string $key     Needle.
 * @param mixed  $default Default value to return.
 *
 * @return mixed
 */
function mai_isset( $array, $key, $default = false ) {
	return isset( $array[ $key ] ) ? $array[ $key ] : $default;
}

/**
 * Check if a size is a valid size value.
 *
 * @since 2.4.0
 *
 * @return bool
 */
function mai_is_valid_size( $size ) {
	return in_array( $size, [ 'xxxxs', 'xxxs', 'xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl', 'xxl', 'xxxl', 'xxxxl' ] );
}

/**
 * Checks if we're on any type of singular page.
 *
 * @since 0.1.0
 *
 * @param bool $use_cache Whether to use static caching or not.
 *
 * @return bool
 */
function mai_is_type_single( $use_cache = false ) {
	static $is_type_single = null;

	if ( ! is_null( $is_type_single ) && $use_cache ) {
		return $is_type_single;

	} else {
		$is_type_single = ( is_front_page() || is_single() || is_page() || is_404() || is_attachment() || is_singular() ) && ! is_home();
	}

	return $is_type_single;
}

/**
 * Checks if we're on any type of archive page.
 *
 * @since 0.1.0
 *
 * @param bool $use_cache Whether to use static cache.
 *
 * @return bool
 */
function mai_is_type_archive( $use_cache = false ) {
	static $is_type_archive = null;

	if ( ! is_null( $is_type_archive ) && $use_cache ) {
		return $is_type_archive;

	} else {
		$is_type_archive = is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_author() || is_date() || is_year() || is_month() || is_day() || is_time() || is_archive() || is_search();
	}

	return $is_type_archive;
}

/**
 * Checks if on the wp-login.php page.
 *
 * @since unknown
 *
 * @return bool
 */
function mai_is_login_page() {
	return false !== stripos( $_SERVER['SCRIPT_NAME'], strrchr( wp_login_url(), '/' ) );
}

/**
 * Checks if body has a dark background.
 *
 * @since 2.25.0
 *
 * @return bool
 */
function mai_has_dark_body() {
	static $dark = null;

	if ( ! is_null( $dark ) ) {
		return $dark;
	}

	$colors = mai_get_colors();
	$light  = mai_is_light_color( $colors['background'] );
	$dark   = ! $light;

	return $dark;
}

/**
 * Checks if body has a dark background.
 *
 * @since 2.25.0
 *
 * @return bool
 */
function mai_has_dark_header() {
	static $dark = null;

	if ( ! is_null( $dark ) ) {
		return $dark;
	}

	$colors = mai_get_colors();
	$light  = mai_is_light_color( $colors['header'] );
	$dark   = ! $light;

	return $dark;
}

/**
 * Checks if first block is cover or group block aligned full.
 *
 * @since 0.1.0
 * @since 2.19.0 Checks for alignfull class.
 *               Useful when allowed blocks are added via PHP filter
 *               and block doesn't have align settings.
 *
 * @return bool
 */
function mai_has_alignfull_first() {
	static $has_alignfull_first = null;

	if ( is_null( $has_alignfull_first ) ) {
		$has_alignfull_first = false;

		$first = mai_get_first_block();

		if ( $first ) {
			$block_name = isset( $first['blockName'] ) ? $first['blockName'] : '';
			$allowed    = [ 'core/cover', 'core/group' ];
			$allowed    = apply_filters( 'mai_alignfull_first_blocks', $allowed );

			if ( ! ( $allowed && in_array( $block_name, $allowed ) ) ) {
				return $has_alignfull_first;
			}

			$align = isset( $first['attrs']['align'] ) ? $first['attrs']['align'] : '';

			if ( $align && 'full' === $align ) {
				$has_alignfull_first = true;
				return $has_alignfull_first;
			}

			$classes = isset( $first['attrs']['className'] ) && $first['attrs']['className'] ? trim( $first['attrs']['className'] ) : '';

			if ( $classes ) {
				$classes = explode( ' ', $classes );

				if ( in_array( 'alignfull', $classes ) ) {
					$has_alignfull_first = true;
					return $has_alignfull_first;
				}
			}
		}
	}

	return $has_alignfull_first;
}

/**
 * Checks if first block has dark background.
 *
 * @since 2.12.0
 * @since 2.34.0 Added filter.
 *
 * @return bool
 */
function mai_has_dark_background_first() {
	static $has_dark_first = null;
	       $first          = null;

	if ( is_null( $has_dark_first ) ) {
		$has_dark_first = false;
		$first          = mai_get_first_block();

		if ( $first ) {
			$block_name = isset( $first['blockName'] ) ? $first['blockName'] : '';

			if ( 'core/cover' === $block_name ) {
				if ( isset( $first['attrs']['overlayColor'] ) ) {
					$color          = mai_get_color_value( $first['attrs']['overlayColor'] );
					$has_dark_first = $color && ! mai_is_light_color( $color );
				} else {
					$has_dark_first = true;
				}
			}

			if ( 'core/group' === $block_name ) {
				if ( isset( $first['attrs']['backgroundColor'] ) ) {
					$color          = mai_get_color_value( $first['attrs']['backgroundColor'] );
					$has_dark_first = $color && ! mai_is_light_color( $color );
				} elseif ( isset( $first['attrs']['gradient'] ) ) {
					$gradient  = $first['attrs']['gradient'];
					$gradients = mai_get_config( 'theme-support' )['add']['editor-gradient-presets'];

					if ( $gradient && $gradients ) {
						// Most gradients have dark bg?
						$has_dark_first = false;

						foreach ( $gradients as $values ) {
							if ( $values['slug'] === $gradient ) {
								$has_dark_first = isset( $values['dark'] ) ? $values['dark'] : $has_dark_first;
								break;
							}
						}
					}
				}
			}
		}
	}

	return apply_filters( 'mai_has_dark_background_first', $has_dark_first, $first );
}

/**
 * Gets first block on a page.
 *
 * @since 2.12.0
 *
 * @return array|false
 */
function mai_get_first_block() {
	static $first = null;

	if ( ! is_null( $first ) ) {
		return $first;
	}

	$first   = false;
	$content = '';

	if ( ! mai_is_type_single() ) {
		return $first;
	}

	if ( is_404() ) {
		$content = mai_get_template_part( '404-page' );
	} elseif ( has_blocks() ) {
		$post_object = get_post( get_the_ID() );
		$content     = $post_object->post_content;
	}

	if ( ! $content ) {
		return $first;
	}

	$blocks = (array) parse_blocks( $content );
	$first  = reset( $blocks );

	return $first;
}

/**
 * Checks if given sidebar contains a certain widget.
 *
 * @since 0.1.0
 *
 * @uses  $sidebars_widgets
 *
 * @param string $sidebar Name of sidebar, e.g `primary`.
 * @param string $widget  Widget ID to check, e.g `custom_html`.
 *
 * @return bool
 */
function mai_sidebar_has_widget( $sidebar, $widget ) {
	global $sidebars_widgets;

	if ( isset( $sidebars_widgets[ $sidebar ][0] ) && strpos( $sidebars_widgets[ $sidebar ][0], $widget ) !== false && is_active_sidebar( $sidebar ) ) {
		return true;
	}

	return false;
}

/**
 * Checks if current page has a sidebar.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_sidebar() {
	static $has_sidebar = null;

	if ( ! is_null( $has_sidebar ) && ! is_customize_preview() ) {
		return $has_sidebar;
	}

	$has_sidebar = in_array( mai_site_layout(), [ 'content-sidebar', 'sidebar-content' ], true );

	return $has_sidebar;
}

/**
 * Checks if site has boxed container.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_boxed_container() {
	static $has_boxed = null;

	if ( ! is_null( $has_boxed ) && ! is_customize_preview() ) {
		return $has_boxed;
	}

	$default   = current_theme_supports( 'boxed-container' );
	$has_boxed = mai_get_option( 'boxed-container', $default );

	return $has_boxed;
}

/**
 * Checks if site has sticky header enabled.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_sticky_header_enabled() {
	static $sticky = null;

	if ( ! is_null( $sticky ) && ! is_customize_preview() ) {
		return $sticky;
	}

	$sticky = mai_get_option( 'site-header-sticky', current_theme_supports( 'sticky-header' ) );

	return $sticky;
}

/**
 * Checks if a site has a sticky header.
 *
 * @since 2.22.0
 *
 * @return bool
 */
function mai_has_sticky_header() {
	static $sticky = null;

	if ( ! is_null( $sticky ) && ! is_customize_preview() ) {
		return $sticky;
	}

	$sticky = mai_has_sticky_header_enabled() && ! mai_is_element_hidden( 'sticky_header' );
	$sticky = (bool) apply_filters( 'mai_has_sticky_header', $sticky );

	return $sticky;
}

/**
 * Checks if site has sticky header and a scroll logo set.
 *
 * @since 2.13.0
 *
 * @return bool
 */
function mai_has_sticky_scroll_logo() {
	return (bool) has_custom_logo() && mai_has_sticky_header_enabled() && ! mai_is_element_hidden( 'sticky_header' ) && mai_get_scroll_logo_id();
}

/**
 * Checks if site has transparent header enabled.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_transparent_header_enabled() {
	static $transparent = null;

	if ( ! is_null( $transparent ) ) {
		return $transparent;
	}

	$transparent = mai_get_option( 'site-header-transparent', current_theme_supports( 'transparent-header' ) );

	return $transparent;
}

/**
 * Checks if site has transparent header.
 *
 * @since 2.22.0 Added filter.
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_transparent_header() {
	static $transparent = null;

	if ( ! is_null( $transparent ) ) {
		return $transparent;
	}

	$transparent = false;

	if ( mai_has_transparent_header_enabled() ) {
		if ( ! ( mai_is_element_hidden( 'transparent_header' ) || mai_is_element_hidden( 'site_header' ) ) ) {
			if ( mai_has_page_header() || ( mai_has_alignfull_first() && mai_is_element_hidden( 'entry_title' ) && ! mai_has_breadcrumbs() ) ) {
				$transparent = true;
			}
		}
	}

	/**
	 * If you force a transparent header with this filter,
	 * the content offset will only work if there is a page header
	 * or an alignfull element first in the editor content,
	 * otherwise you'll need to add padding/margin top -- `var(--transparent-header-offset, 0)`.
	 */
	$transparent = (bool) apply_filters( 'mai_has_transparent_header', $transparent );

	return $transparent;
}

/**
 * Check of the page has a light page header background.
 *
 * @since 0.3.0
 *
 * @return bool
 */
function mai_has_light_page_header() {
	static $has_light_page_header = null;

	if ( ! is_null( $has_light_page_header ) ) {
		return $has_light_page_header;
	}

	if ( ! mai_has_page_header() ) {
		$has_light_page_header = false;

	} else {
		$args = mai_get_template_args();

		if ( isset( $args['page-header-text-color'] ) && ! empty( $args['page-header-text-color'] ) ) {
			$text_color = $args['page-header-text-color'];

		} else {
			$config     = mai_get_config( 'settings' )['page-header'];
			$text_color = mai_get_option( 'page-header-text-color', $config['text-color'] );
		}

		$has_light_page_header = 'light' !== $text_color;
	}

	return $has_light_page_header;
}

/**
 * Checks if the Page Header is active.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_page_header() {
	static $has_page_header = null;

	if ( ! is_null( $has_page_header ) ) {
		return $has_page_header;
	}

	$config = mai_get_config( 'settings' )['page-header'];

	if ( is_string( $config ) && '*' === $config ) {
		$has_page_header = true;
	}

	if ( isset( $config['archive'] ) && '*' === $config['archive'] && mai_is_type_archive( false ) ) {
		$has_page_header = true;
	}

	if ( isset( $config['single'] ) && '*' === $config['single'] && mai_is_type_single( false ) ) {
		$has_page_header = true;
	}

	if ( mai_is_type_archive() ) {
		$has_page_header = in_array( mai_get_archive_args_name(), mai_get_page_header_types( 'archive' ), true );

	} elseif ( mai_is_type_single() ) {
		$has_page_header = in_array( mai_get_singular_args_name(), mai_get_page_header_types( 'single' ), true );

		// TODO: Is this needed?
		if ( genesis_entry_header_hidden_on_current_page() ) {
			$has_page_header = false;
		}
	}

	if ( $has_page_header && mai_is_element_hidden( 'page_header' ) ) {
		$has_page_header = false;
	}

	return $has_page_header;
}

/**
 * Checks if showing page header in title.
 *
 * @since 2.25.5
 *
 * @return bool
 */
function mai_has_title_in_page_header() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = (bool) apply_filters( 'mai_entry_title_in_page_header', true );

	return $cache;
}

/**
 * Checks if breadcrumbs are displayed.
 * Mostly taken from genesis_do_breadcrumbs().
 *
 * @since 2.7.0
 *
 * @return bool
 */
function mai_has_breadcrumbs() {
	static $breadcrumbs = null;

	if ( ! is_null( $breadcrumbs ) ) {
		return $breadcrumbs;
	}

	/**
	 * Do not output breadcrumbs if filter returns true.
	 *
	 * @since 3.1.0 Genesis
	 *
	 * @param bool $breadcrumbs_hidden True to hide breadcrumbs, false to show them.
	 */
	$genesis_breadcrumbs_hidden = apply_filters( 'genesis_do_breadcrumbs', genesis_breadcrumbs_hidden_on_current_page() );

	if ( $genesis_breadcrumbs_hidden ) {
		$breadcrumbs = false;
		return $breadcrumbs;
	}

	if ( genesis_breadcrumbs_disabled_on_current_page() ) {
		$breadcrumbs = false;
		return $breadcrumbs;
	}

	if ( mai_is_element_hidden( 'breadcrumbs' ) ) {
		$breadcrumbs = false;
		return $breadcrumbs;
	}

	$breadcrumbs = true;

	return $breadcrumbs;
}

/**
 * Get single content types that have page header enabled.
 *
 * @since 0.1.0
 *
 * @param string $context 'archive' or 'single'.
 *
 * @return string|array May be * for all or array of types.
 */
function mai_get_page_header_types( $context ) {
	static $types = null;

	if ( is_array( $types ) && isset( $types[ $context ] ) ) {
		return $types[ $context ];
	}

	$types   = [];
	$config  = mai_get_config( 'settings' )['page-header'];
	$single  = array_merge(
		array_values( get_post_types( [ 'public' => true ] ) ),
		[
			'404-page',
		]
	);
	$archive = array_merge(
		$single,
		array_values( get_taxonomies( [ 'public' => true ] ) ),
		[
			'search',
			'author',
			'date',
		]
	);
	$default = [
		'archive' => $archive,
		'single'  => $single,
	];

	if ( '*' === $config || isset( $config[ $context ] ) && '*' === $config[ $context ] ) {
		$types = $default[ $context ];
	} elseif ( isset( $config[ $context ] ) && is_array( $config[ $context ] ) ) {
		$types = $config[ $context ];
	}

	$types[ $context ] = mai_get_option( 'page-header-' . $context, $types );

	return $types[ $context ];
}

/**
 * Gets page header opacity, with fallbacks.
 *
 * @since  2.6.0
 *
 * @return float
 */
function mai_get_page_header_overlay_opacity() {
	static $opacity = null;

	if ( ! is_null( $opacity ) ) {
		return $opacity;
	}

	$opacity = mai_get_template_arg( 'page-header-overlay-opacity' );

	if ( null !== $opacity && floatval( $opacity ) < 1 ) {
		return $opacity;
	}

	if ( null === $opacity || 1.0 === floatval( $opacity ) ) {
		$opacity = mai_get_option( 'page-header-overlay-opacity' );

		if ( null !== $opacity && floatval( $opacity ) < 1 ) {
			return $opacity;
		}
	}

	$opacity = floatval( mai_get_config( 'settings' )['page-header']['overlay-opacity'] );

	return $opacity;
}

/**
 * Quick and dirty way to mostly minify CSS.
 *
 * @since  0.1.0
 *
 * @author Gary Jones
 *
 * @param string $css CSS to minify.
 *
 * @return string
 */
function mai_minify_css( $css ) {
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css );
	$css = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $css );
	$css = preg_replace( '/;(?=\s*})/', '', $css );
	$css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );
	$css = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $css );
	$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
	$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
	$css = preg_replace( '/0 0 0 0/', '0', $css );
	$css = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css );

	return trim( $css );
}

/**
 * Sanitize a value. Checks for null/array.
 *
 * @param   string $value      The value to sanitize.
 * @param   string $function   The function to use for escaping.
 * @param   bool   $allow_null Wether to return or escape if the value is.
 *
 * @return  mixed
 */
function mai_sanitize( $value, $function = 'esc_html', $allow_null = false ) {
	// Return null if allowing null.
	if ( is_null( $value ) && $allow_null ) {
		return $value;
	}

	// Require a callable sanitization function.
	$function = is_callable( $function ) ? $function : 'esc_html';

	// If array, escape and return it.
	if ( is_array( $value ) ) {
		$escaped = [];
		foreach ( $value as $index => $item ) {
			if ( is_array( $item ) ) {
				$escaped[ $index ] = mai_sanitize( $item, $function );
			} else {
				$item              = trim( $item );
				$escaped[ $index ] = $function( $item );
			}
		}

		return $escaped;
	}

	// Return single value.
	$value   = trim( $value );
	$escaped = $function( $value );

	return $escaped;
}

/**
 * Sanitize a value to boolean.
 *
 * Taken from rest_sanitize_boolean() but seemed risky to use that directly.
 *
 * String values are translated to `true`; make sure 'false' is false.
 *
 * @since  0.1.0
 *
 * @param  string $value String to sanitize.
 *
 * @return bool
 */
function mai_sanitize_bool( $value ) {
	if ( is_string( $value ) ) {
		$value = strtolower( $value );

		if ( in_array( $value, [ 'false', '0' ], true ) ) {
			$value = false;
		}
	}

	// Everything else will map nicely to boolean.
	return (bool) $value;
}

/**
 * Converts a fraction to a percentage.
 *
 * @since 2.10.0
 *
 * @param string $fraction The fraction.
 *
 * @return void
 */
function mai_fraction_to_percent( $fraction ) {
	$numbers = explode( '/', $fraction );
	$top     = (int) $numbers[0];
	$bottom  = (int) $numbers[1];
	$top     = 0 === $top ? 1 : $top;
	$bottom  = 0 === $bottom ? 1 : $bottom;

	return ( round( $top / $bottom, 6 ) * 100 ) . '%';
}

/**
 * Maps an array recursively.
 *
 * @access private This may change at some point so don't use outside of the engine.
 *
 * @since 2.10.0
 *
 * @param callable $func  The function name map.
 * @param array    $array The array being sanitized/filtered.
 *
 * @return array
 */
function mai_array_map_recursive( callable $func, array $array ) {
	return filter_var( $array, FILTER_CALLBACK, [ 'options' => $func ] );
}

/**
 * Checks if element is hidden on a post.
 *
 * @since 0.3.0
 * @since 2.5.0 Add $post_id to use outside of the loop.
 * @since 2.25.5 Change $post_id to $id and added support for term IDs.
 *
 * @param bool $element Element to check.
 * @param int  $id      The ID.
 *
 * @return mixed
 */
function mai_is_element_hidden( $element, $id = '' ) {
	$type = 'post';

	if ( ! $id ) {
		if ( is_singular() ) {
			$id = get_the_ID();
		}
		elseif ( 'page' === get_option( 'show_on_front' ) && is_home() ) {
			$id = get_option( 'page_for_posts' );
		}
		elseif ( class_exists( 'WooCommerce' ) && is_shop() ) {
			$id = get_option( 'woocommerce_shop_page_id' );
		}
		elseif ( is_category() || is_tag() || is_tax() ) {
			$type = 'term';
			$id   = get_queried_object_id();
		}
	}

	if ( ! $id ) {
		return false;
	}

	// Get elements.
	$elements = mai_get_hidden_elements( $id, $type );

	// Set value.
	$hidden = in_array( $element, (array) $elements, true );

	// Filter.
	$hidden = (bool) apply_filters( 'mai_element_hidden', $hidden, $element );

	return $hidden;
}

/**
 * Gets hidden elements for a single page.
 * This may run on single and term archives.
 *
 * @since 2.25.5
 *
 * @param int    $id   The object ID.
 * @param string $type The object type.
 *
 * @return array
 */
function mai_get_hidden_elements( $id, $type = 'post' ) {
	static $elements = null;

	if ( ! is_null( $elements ) ) {
		return $elements;
	}

	$elements = [];

	switch ( $type ) {
		case 'post':
			$elements = get_post_meta( $id, 'hide_elements', true );
		break;
		case 'term':
			$elements = get_term_meta( $id, 'hide_elements', true );
		break;
	}

	// Filter.
	$elements = apply_filters( 'mai_hidden_elements', $elements );
	$elements = array_map( 'sanitize_key', (array) $elements );

	return $elements;
}

/**
 * Converts a string to different naming conventions.
 *
 * Camel:    myNameIsBond.
 * Pascal:   MyNameIsBond.
 * Snake:    my_name_is_bond.
 * Ada:      My_Name_Is_Bond.
 * Macro:    MY_NAME_IS_BOND.
 * Kebab:    my-name-is-bond.
 * Train:    My-Name-Is-Bond.
 * Cobol:    MY-NAME-IS-BOND.
 * Lower:    my name is bond.
 * Upper:    MY NAME IS BOND.
 * Title:    My Name Is Bond.
 * Sentence: My name is bond.
 * Dot:      my.name.is.bond.
 *
 * @since  0.3.0
 *
 * @author Lee Anthony https://seothemes.com
 *
 * @param string $string String to convert.
 * @param string $case   Naming convention.
 *
 * @return string
 */
function mai_convert_case( $string, $case = 'snake' ) {
	$delimiters = 'sentence' === $case ? [ ' ', '-', '_' ] : [ ' ', '-', '_', '.' ];
	$lower      = trim( str_replace( $delimiters, $delimiters[0], strtolower( $string ) ), $delimiters[0] );
	$upper      = trim( ucwords( $lower ), $delimiters[0] );
	$pieces     = explode( $delimiters[0], $lower );

	$cases = [
		'camel'    => lcfirst( str_replace( ' ', '', $upper ) ),
		'pascal'   => str_replace( ' ', '', $upper ),
		'snake'    => strtolower( implode( '_', $pieces ) ),
		'ada'      => str_replace( ' ', '_', $upper ),
		'macro'    => strtoupper( implode( '_', $pieces ) ),
		'kebab'    => strtolower( implode( '-', $pieces ) ),
		'train'    => lcfirst( str_replace( ' ', '-', $upper ) ),
		'cobol'    => strtoupper( implode( '-', $pieces ) ),
		'lower'    => strtolower( $string ),
		'upper'    => strtoupper( $string ),
		'title'    => $upper,
		'sentence' => ucfirst( $lower ),
		'dot'      => strtolower( implode( '.', $pieces ) ),
	];

	return isset( $cases[ $case ] ) ? $cases[ $case ] : $string;
}

/**
 * Add classes to an existing string of classes.
 *
 * @since  0.1.0
 *
 * @param  string|array $new      The classes to add.
 * @param  string       $existing The existing classes.
 *
 * @return string  HTML ready classes.
 */
function mai_add_classes( $new, $existing = '' ) {
	if ( ! empty( $new ) ) {
		$space = ! empty( $existing ) ? ' ' : '';
		$new   = is_array( $new ) ? implode( ' ', $new ) : $new;

		return esc_attr( $existing . $space . $new );
	}

	return $existing;
}

/**
 * Returns single instance of class (avoids singletons).
 *
 * @since 0.3.0
 *
 * @param string $class   Class name.
 * @param mixed  ...$args Passed args.
 *
 * @return object
 */
function mai_get_instance( $class, ...$args ) {
	static $classes = [];

	if ( ! array_key_exists( $class, $classes ) ) {
		$classes[ $class ] = new $class( ...$args );
	}

	return $classes[ $class ];
}

/**
 * Gets width or height attribute value from CSS values.
 *
 * @since 2.11.0
 *
 * @param string $value The existing value. May be numeric, px, rem, or em.
 *
 * @return int
 */
function mai_get_width_height_attribute( $value, $fallback = false ) {
	if ( is_numeric( $value ) || mai_has_string( 'calc(', $value ) ) {
		return $value;
	}
	// Pixel values.
	elseif ( mai_has_string( 'px', $value ) ) {
		$size = trim( str_replace( 'px', '', $value ) );
		return $size;
	}
	// Rem or em values.
	elseif ( mai_has_string( 'em', $value ) ) {
		$size = filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
		if ( $size ) {
			$size = absint( $size ) * 16;
			return $size;
		}
	}

	return $fallback ? absint( $fallback ) : absint( filter_var( $value, FILTER_SANITIZE_NUMBER_INT ) );
}

/**
 * Gets info icon with link.
 *
 * @since 2.19.0
 *
 * @param $href The href value.
 *
 * @return int
 */
function mai_get_block_setting_info_link( $href ) {
	return sprintf( '<a target="_blank" class="mai-info-icon-link" href="%s"><span class="screen-reader-text">%s</span></a>',
		esc_url( $href ),
		esc_html__( 'More info', 'mai-engine' )
	);
}

/**
 * Checks if a page has at least one WooCommerce block.
 *
 * @since 2.18.0
 * *
 * @return bool
 */
function mai_has_woocommerce_blocks() {
	static $has_blocks = null;

	if ( ! is_null( $has_blocks ) ) {
		return $has_blocks;
	}

	$has_blocks = false;

	if ( is_singular() ) {
		$post = get_post();

		if ( $post && mai_has_woocommerce_block( $post->post_content ) ) {
			$has_blocks = true;
		}
	}

	if ( ! $has_blocks ) {

		$template_parts = mai_get_template_parts();

		if ( $template_parts ) {
			foreach ( $template_parts as $content ) {
				if ( mai_has_woocommerce_block( $content ) ) {
					$has_blocks = true;
				}
			}
		}
	}

	$has_blocks = apply_filters( 'mai_has_woocommerce_blocks', $has_blocks );

	return $has_blocks;
}

/**
 * Checks if a string of content has at least one WooCommerce block.
 *
 * @since 2.18.0
 * *
 * @return bool
 */
function mai_has_woocommerce_block( $content ) {
	if ( ! has_blocks( $content ) ) {
		return false;
	}

	$blocks = parse_blocks( $content );

	if ( ! $blocks ) {
		return false;
	}

	$names = wp_list_pluck( $blocks, 'blockName', 'blockName' );
	$names = implode( ' ', $names );

	return mai_has_string( 'woocommerce/', $names );
}
