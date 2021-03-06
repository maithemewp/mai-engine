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
 * Returns the plugin directory.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_dir() {
	static $dir = null;

	if ( is_null( $dir ) ) {
		$dir = trailingslashit( dirname( dirname( __DIR__ ) ) );
	}

	return $dir;
}

/**
 * Returns the plugin URL.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_url() {
	static $url = null;

	if ( ! is_null( $url ) ) {
		return $url;
	}

	$url = trailingslashit( plugins_url( basename( mai_get_dir() ) ) );

	return $url;
}

/**
 * Gets the plugin basename.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_base() {
	static $base = null;

	if ( ! is_null( $base ) ) {
		return $base;
	}

	$dir  = basename( dirname( dirname( __DIR__ ) ) );
	$file = mai_get_handle() . '.php';
	$base = $dir . DIRECTORY_SEPARATOR . $file;

	return $base;
}

/**
 * Returns an array of plugin data from the main plugin file.
 *
 * @since 0.1.0
 *
 * @param string $key Optionally return one key.
 *
 * @return array|string|null
 */
function mai_get_plugin_data( $key = '' ) {
	static $data = null;

	if ( is_null( $data ) ) {
		$data = get_file_data(
			mai_get_dir() . 'mai-engine.php',
			[
				'name'        => 'Plugin Name',
				'version'     => 'Version',
				'plugin-uri'  => 'Plugin URI',
				'text-domain' => 'Text Domain',
				'description' => 'Description',
				'author'      => 'Author',
				'author-uri'  => 'Author URI',
				'domain-path' => 'Domain Path',
				'network'     => 'Network',
			],
			'plugin'
		);
	}

	if ( array_key_exists( $key, $data ) ) {
		return $data[ $key ];
	}

	return $data;
}

/**
 * Returns the plugin name.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_name() {
	static $name = null;

	if ( is_null( $name ) ) {
		$name = mai_get_plugin_data( 'name' );
	}

	return $name;
}

/**
 * Returns the plugin handle/text domain.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_handle() {
	static $handle = null;

	if ( is_null( $handle ) ) {
		$handle = mai_get_plugin_data( 'text-domain' );
	}

	return $handle;
}

/**
 * Returns the plugin version.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_version() {
	static $version = null;

	if ( is_null( $version ) ) {
		$version = mai_get_plugin_data( 'version' );
	}

	return $version;
}

/**
 * Returns asset version with filetime.
 *
 * @since 0.1.0
 *
 * @param string $file File path.
 *
 * @return string
 */
function mai_get_asset_version( $file ) {
	$file    = str_replace( content_url(), WP_CONTENT_DIR, $file );
	$version = mai_get_version();

	if ( file_exists( $file ) ) {
		$version .= '.' . date( 'njYHi', filemtime( $file ) );
	}

	return $version;
}

/**
 * Returns minified version of asset if in dev mode.
 *
 * @since 2.4.0 Removed min dir if CSS file. Always return minified CSS.
 * @since 0.1.0
 *
 * @param string $file File base name (relative to type directory).
 *
 * @return string
 */
function mai_get_asset_url( $file ) {
	$type    = false !== strpos( $file, '.js' ) ? 'js' : 'css';
	$name    = str_replace( [ '.js', '.css' ], '', $file );
	$uri     = mai_get_url();
	$default = "${uri}assets/${type}/${name}.${type}";
	$dir     = 'js' === $type ? '/min/' : '/';
	$min     = "${uri}assets/${type}${dir}${name}.min.${type}";

	return mai_is_in_dev_mode() && 'js' === $type ? $default : $min;
}

/**
 * Returns the active child theme's sub config.
 *
 * @since 0.1.0
 * @since 2.11.0 Add static caching.
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function mai_get_config( $sub_config ) {
	static $configs = null;

	if ( is_array( $configs ) && isset( $configs[ $sub_config ] ) ) {
		return $configs[ $sub_config ];
	}

	if ( ! is_array( $configs ) ) {
		$configs = [];
	}

	$config                 = mai_get_full_config();
	$value                  = isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];
	$configs[ $sub_config ] = apply_filters( "mai_{$sub_config}_config", $value );

	return $configs[ $sub_config ];
}

/**
 * Returns the active child theme's full config.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_full_config() {
	static $config = null;

	if ( ! is_null( $config ) ) {
		return $config;
	}

	$config = require mai_get_dir() . 'config/_default.php';
	$theme  = mai_get_active_theme();
	$theme  = ( 'default' === $theme ) ? '_default' : $theme;
	$path   = mai_get_dir() . 'config/' . $theme . '.php';

	if ( is_readable( $path ) ) {
		$new    = require $path;
		$config = array_replace_recursive( $config, $new );
		if ( isset( $new['settings']['content-archives'] ) ) {
			foreach ( $new['settings']['content-archives'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['content-archives'][ $key ]['show'] ) && isset( $config['settings']['content-archives'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['content-archives'][ $key ]['show'] = $new['settings']['content-archives'][ $key ]['show'];
			}
		}
		if ( isset( $new['settings']['single-content'] ) ) {
			foreach ( $new['settings']['single-content'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['single-content'][ $key ]['show'] ) && isset( $config['settings']['single-content'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['single-content'][ $key ]['show'] = $new['settings']['single-content'][ $key ]['show'];
			}
		}
	}

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . '/config.php';

	if ( is_readable( $child ) ) {
		$new    = require $child;
		$config = array_replace_recursive( $config, $new );
		if ( isset( $new['settings']['content-archives'] ) ) {
			foreach ( $new['settings']['content-archives'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['content-archives'][ $key ]['show'] ) && isset( $config['settings']['content-archives'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['content-archives'][ $key ]['show'] = $new['settings']['content-archives'][ $key ]['show'];
			}
		}
		if ( isset( $new['settings']['single-content'] ) ) {
			foreach ( $new['settings']['single-content'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['single-content'][ $key ]['show'] ) && isset( $config['settings']['single-content'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['single-content'][ $key ]['show'] = $new['settings']['single-content'][ $key ]['show'];
			}
		}
	}

	$config = apply_filters( 'mai_config', $config );

	return $config;
}

/**
 * Returns the active theme key.
 *
 * Wrapper function for mai_get_engine_theme.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_active_theme() {
	static $theme = null;

	if ( ! is_null( $theme ) ) {
		return $theme;
	}

	$theme = apply_filters( 'mai_active_theme', mai_get_engine_theme() );

	return $theme;
}

/**
 * Get all mai-engine options.
 *
 * @since 0.1.0
 *
 * @param bool $use_cache Whether to use static variable.
 *
 * @return array
 */
function mai_get_options( $use_cache = true ) {
	$handle = mai_get_handle();

	if ( ! $use_cache || is_customize_preview() ) {
		$options = get_option( $handle );
	} else {
		static $options = [];

		if ( empty( $options ) ) {
			$options = get_option( $handle );
		}
	}

	return $options;
}

/**
 * Get a single option from mai-engine array of options.
 *
 * @since 0.1.0
 *
 * @param string $option    Option name.
 * @param mixed  $default   Default value.
 * @param bool   $use_cache Whether to use static cache when fetching option.
 *
 * @return mixed
 */
function mai_get_option( $option, $default = false, $use_cache = true ) {
	static $values = null;

	if ( $use_cache && is_array( $values ) && isset( $values[ $option ] ) ) {
		return $values[ $option ];
	}

	$values            = [];
	$options           = mai_get_options( $use_cache );
	$value             = isset( $options[ $option ] ) ? $options[ $option ] : $default;
	$values[ $option ] = apply_filters( "mai_get_option_{$option}", $value );

	return $values[ $option ];
}

/**
 * Update a single option from mai-engine array of options.
 *
 * @since 0.1.0
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 *
 * @return void
 */
function mai_update_option( $option, $value ) {
	$handle  = mai_get_handle();
	$options = get_option( $handle, [] );

	$options[ $option ] = $value;

	update_option( $handle, $options );
}

/**
 * Get settings config file by name.
 *
 * @since 0.1.0
 *
 * @param string $name Settings to get.
 *
 * @return mixed
 */
function mai_get_settings( $name ) {
	static $settings = null;

	if ( is_array( $settings ) && isset( $settings[ $name ] ) ) {
		return $settings[ $name ];
	}

	if ( ! is_array( $settings ) ) {
		$settings = [];
	}

	$settings[ $name ] = require mai_get_dir() . 'lib/customize/' . $name . '.php';

	return $settings[ $name ];
}

/**
 * Returns the global styles from the config.
 *
 * @since 2.0.0
 *
 * @param string $key Key of styles to retrieve.
 *
 * @return mixed
 */
function mai_get_global_styles( $key ) {
	static $styles = null;

	if ( is_array( $styles ) && isset( $styles[ $key ] ) ) {
		return $styles[ $key ];
	}

	if ( ! is_array( $styles ) ) {
		$styles = [];
	}

	$global_styles  = mai_get_config( 'global-styles' );
	$styles[ $key ] = mai_isset( $global_styles, $key, $global_styles );

	return $styles[ $key ];
}

/**
 * Get breakpoints from initial site container width in config.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_breakpoints() {
	static $breakpoints = [];

	if ( empty( $breakpoints ) ) {
		$breakpoint        = mai_get_global_styles( 'breakpoint' );
		$breakpoints['xs'] = absint( $breakpoint / 3 );   // 400  (400 x 1)
		$breakpoints['sm'] = absint( $breakpoint / 2 );   // 600  (400 x 1.5)
		$breakpoints['md'] = absint( $breakpoint / 1.5 ); // 800  (400 x 2)
		$breakpoints['lg'] = absint( $breakpoint / 1.2 ); // 1000 (400 x 2.5)
		$breakpoints['xl'] = absint( $breakpoint / 1 );   // 1200 (400 x 3)
	}

	return $breakpoints;
}

/**
 * Returns the default breakpoint for the theme.
 *
 * @since 0.1.0
 *
 * @param string $size   Breakpoint size.
 * @param string $suffix Optional suffix, e.g. 'px'.
 *
 * @return mixed
 */
function mai_get_breakpoint( $size = 'lg', $suffix = '' ) {
	$breakpoints = mai_get_breakpoints();

	return $breakpoints[ $size ] . $suffix;
}

/**
 * Returns the default breakpoint the mobile menu to display.
 *
 * @since TBD
 *
 * @return string
 */
function mai_get_mobile_menu_breakpoint() {
	static $breakpoint = null;

	if ( ! is_null( $breakpoint ) ) {
		return $breakpoint;
	}

	$breakpoint = ! is_null( mai_get_config( 'settings' )['mobile-menu-breakpoint'] ) ? mai_get_config( 'settings' )['mobile-menu-breakpoint'] : mai_get_breakpoint();
	$breakpoint = mai_get_unit_value( $breakpoint );

	return $breakpoint;
}

/**
 * Return the current post type.
 * Sometimes we need this earlier than get_post_type()
 * can handle, so we fall back to the query var.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_post_type() {
	$name = get_post_type();

	return $name ?: get_query_var( 'post_type' );
}

/**
 * Get the unit value.
 *
 * If only a number value, use the fallback..
 *
 * @since 0.1.0
 * @since 2.11.0 Change intval to casting as int to allow negative numbers.
 *
 * @param  string $value    The value. Could be integer 24 or with type 24px, 2rem, etc.
 * @param  string $fallback The fallback unit value.
 *
 * @return string
 */
function mai_get_unit_value( $value, $fallback = 'px' ) {
	if ( empty( $value ) || is_numeric( $value ) ) {
		return sprintf( '%s%s', (int) $value, $fallback );
	}

	return trim( $value );
}

/**
 * Get an integer value from string.
 *
 * @since 0.1.0
 *
 * @param string $string String to check.
 *
 * @return int
 */
function mai_get_integer_value( $string ) {
	return (int) preg_replace( '/[^0-9.]/', '', $string );
}

/**
 * Gets site layout choices.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_site_layout_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$choices = [ '' => esc_html__( 'Default', 'mai-engine' ) ] + genesis_get_layouts_for_customizer();

	return $choices;
}

/**
 * Get a direct link to open a specific customizer section.
 * Optionally include a frontend URL to preview.
 *
 * @since 0.2.0
 *
 * @param   string $name The name of the link to open.
 * @param   string $type The link type (panel or section) to open.
 * @param   string $url  The preview URL.
 *
 * @return  string  The customizer URL.
 */
function mai_get_customizer_link( $name, $type = 'section', $url = '' ) {
	$query[ 'autofocus[' . $type . ']' ] = $name;

	if ( $url ) {
		$query['url'] = esc_url( $url );
	}

	return add_query_arg( $query, admin_url( 'customize.php' ) );
}

/**
 * Get content type archive choices for Kirki.
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_content_type_archive_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$choices = [
		'post' => esc_html__( 'Post', 'mai-engine' ),
	];

	$post_types = get_post_types(
		[
			'public'   => true,
			'_builtin' => false,
		],
		'objects'
	);

	if ( $post_types ) {
		foreach ( $post_types as $name => $post_type ) {
			// Skip post types without archives.
			if ( ! (bool) $post_type->has_archive ) {
				continue;
			}

			$choices[ $name ] = $post_type->label;
		}
	}

	$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

	// Remove taxonomies we don't want.
	unset( $taxonomies['post_format'] );
	unset( $taxonomies['product_shipping_class'] );
	unset( $taxonomies['yst_prominent_words'] );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $name => $taxonomy ) {
			$choices[ $name ] = $taxonomy->label;
		}
	}

	$choices += [
		'search' => __( 'Search Results', 'mai-engine' ),
		'author' => __( 'Author Archives', 'mai-engine' ),
		'date'   => __( 'Date Archives', 'mai-engine' ),
	];

	return $choices;
}

/**
 * Get content type single choices for Kirki.
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_content_type_single_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$choices = [
		'post' => esc_html__( 'Post', 'mai-engine' ),
		'page' => esc_html__( 'Page', 'mai-engine' ),
	];

	$post_types = get_post_types(
		[
			'public'   => true,
			'_builtin' => false,
		],
		'objects'
	);

	if ( $post_types ) {
		foreach ( $post_types as $name => $post_type ) {
			$choices[ $name ] = $post_type->label;
		}
	}

	return $choices;
}

/**
 * Get loop content type choices.
 * We need to check custom post type and a custom taxonomy's post type support
 *
 * @since 0.2.0
 *
 * @param bool $archive Whether archive or single content type choices.
 *
 * @return array
 */
function mai_get_loop_content_type_choices( $archive = true ) {
	if ( $archive ) {
		$choices = mai_get_content_type_archive_choices();
		$feature = 'mai-archive-settings';
	} else {
		$choices = mai_get_content_type_single_choices();
		$feature = 'mai-single-settings';
	}

	foreach ( $choices as $name => $label ) {

		// If type is a post type.
		if ( post_type_exists( $name ) ) {
			$post_type = get_post_type_object( $name );

			if ( ! $post_type->_builtin && ! post_type_supports( $post_type->name, $feature ) ) {
				unset( $choices[ $name ] );
			}
		} elseif ( taxonomy_exists( $name ) ) {

			$post_type = mai_get_taxonomy_post_type( $name );

			// If type is a taxonomy.
			if ( $post_type ) {
				$post_type = get_post_type_object( $post_type );

				if ( ! $post_type->_builtin && ! post_type_supports( $post_type->name, $feature ) ) {
					unset( $choices[ $name ] );
				}
			}
		}
	}

	return $choices;
}

/**
 * Get the post type a taxonomy is registered to.
 *
 * If we have a tax, get the first one.
 * This is the simplest way to handle shared taxonomies.
 * Using reset() since we hit an error on a term archive that object_type array didn't start with [0].
 *
 * @since 2.0.0
 *
 * @param string $taxonomy The registered taxonomy name.
 *
 * @return string|false
 */
function mai_get_taxonomy_post_type( $taxonomy ) {
	static $post_types = null;

	if ( is_array( $post_types ) && isset( $post_types[ $taxonomy ] ) ) {
		return $post_types[ $taxonomy ];
	}

	if ( ! is_array( $post_types ) ) {
		$post_types = [];
	}

	$post_types[ $taxonomy ] = false;
	$taxo                    = get_taxonomy( $taxonomy );

	if ( $taxo ) {
		$post_type = reset( $taxo->object_type );

		if ( post_type_exists( $post_type ) ) {
			$post_types[ $taxonomy ] = $post_type;
		}
	}

	return $post_types[ $taxonomy ];
}

/**
 * Get the read more ellipses. Filtered so devs can change.
 *
 * @since 1.0.0
 *
 * @return string;
 */
function mai_get_ellipsis() {
	static $ellipsis = null;

	if ( ! is_null( $ellipsis ) ) {
		return $ellipsis;
	}

	$ellipsis = apply_filters( 'mai_read_more_ellipses', ' &hellip;' );

	return $ellipsis;
}

/**
 * Get post content by slug or ID.
 *
 * Great for displaying reusable blocks in areas that are not block enabled.
 *
 * @since 0.3.0
 * @since N/A    Switched from get_post_field to WP_Query so blocks are parsed and shortcodes are rendered better.
 * @since 2.11.0 Switched from WP_Query to mai_get_processed_content() to avoid conflicts with is_main_query().
 *
 * @param int|string $post_slug_or_id The post slug or ID.
 * @param string     $post_type       The post type, if using post slug.
 *
 * @return string
 */
function mai_get_post_content( $post_slug_or_id, $post_type = 'wp_block' ) {
	if ( is_numeric( $post_slug_or_id ) ) {
		$post = get_post( $post_slug_or_id );

	} else {
		$post = get_page_by_path( $post_slug_or_id, OBJECT, $post_type );
	}

	if ( ! $post ) {
		return;
	}

	return mai_get_processed_content( $post->post_content );
}

/**
 * A big ol' helper/cleanup function to enabled embeds inside the shortcodes and
 * keep the shorcodes from causing extra p's and br's.
 *
 * Most of the order comes from /wp-includes/default-filters.php.
 *
 * @since 2.4.2 Remove use of wp_make_content_images_responsive.
 * @since 0.3.0
 *
 * @param string $content The unprocessed content.
 *
 * @return string
 */
function mai_get_processed_content( $content ) {

	/**
	 * Embed.
	 *
	 * @var WP_Embed $wp_embed Embed object.
	 */
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );     // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content ); // WP runs priority 8.
	$content = do_blocks( $content );                // WP runs priority 9.
	$content = wptexturize( $content );              // WP runs priority 10.
	$content = wpautop( $content );                  // WP runs priority 10.
	$content = shortcode_unautop( $content );        // WP runs priority 10.
	$content = function_exists( 'wp_filter_content_tags' ) ? wp_filter_content_tags( $content ) : wp_make_content_images_responsive( $content ); // WP runs priority 10. WP 5.5 with fallback.
	$content = do_shortcode( $content );             // WP runs priority 11.
	$content = convert_smilies( $content );          // WP runs priority 20.

	return $content;
}

/**
 * Gets the default read more text.
 *
 * This filter is run before any custom read more text is added via Customizer settings.
 * If you want to filter after that, use `genesis_markup_entry-more-link_content` filter.
 *
 * @since 2.4.2 Move default text to config.
 * @since 2.0.0
 *
 * @return string
 */
function mai_get_read_more_text() {
	static $text = null;

	if ( ! is_null( $text ) ) {
		return $text;
	}

	$text = apply_filters( 'mai_read_more_text', mai_get_config( 'settings' )['content-archives']['more_link_text'] );

	return $text;
}

/**
 * Gets the header shrink offset.
 *
 * @since 2.8.0
 *
 * @return int
 */
function mai_get_header_shrink_offset() {
	static $offset = null;

	if ( ! is_null( $offset ) ) {
		return $offset;
	}

	$offset             = 0;
	$config             = mai_get_config( 'settings' )['logo'];
	$customizer_spacing = mai_get_option( 'logo-spacing', $config['spacing'] );
	$customizer_spacing = array_map( 'intval', $customizer_spacing );
	$desktop_spacing    = $customizer_spacing['desktop'];
	$shrunk_spacing     = $customizer_spacing['mobile'];
	$spacing_difference = ceil( ( $desktop_spacing - $shrunk_spacing) * 2 );
	$logo_id            = get_theme_mod( 'custom_logo' );

	if ( ! $logo_id ) {
		$offset = $spacing_difference;
		return $offset;
	}

	$source = wp_get_attachment_image_src( $logo_id, 'full' );

	if ( ! $source ) {
		$offset = $spacing_difference;
		return $offset;
	}

	$source_width       = $source[1];
	$source_height      = $source[2];
	$customizer_widths  = mai_get_option( 'logo-width', $config['width'] );
	$customizer_widths  = array_map( 'intval', $customizer_widths );
	$desktop_width      = $customizer_widths['desktop'];
	$desktop_height     = ( $desktop_width / $source_width ) * $source_height;
	$shrunk_width       = $customizer_widths['mobile'];
	$shrunk_height      = ( $shrunk_width / $desktop_width ) * $desktop_height;
	$height_difference  = ceil( $desktop_height - $shrunk_height );
	$offset             = $height_difference + $spacing_difference;

	return $offset;
}

/**
 * Get a menu.
 *
 * @since 0.3.3
 *
 * @param mixed $menu The menu ID, slug, name, or object.
 * @param array $args The menu args.
 *
 * @return string
 */
function mai_get_menu( $menu, $args = [] ) {
	$defaults = mai_get_menu_defaults();
	$args     = shortcode_atts( $defaults, $args, 'mai_menu' );

	if ( ! is_nav_menu( $menu ) ) {
		return;
	}

	$menu_class = 'menu genesis-nav-menu';

	if ( $args['class'] ) {
		$menu_class = mai_add_classes( $args['class'], $menu_class );
	}

	$list = 'list' === $args['display'];

	if ( $list ) {
		$menu_class = mai_add_classes( 'menu-list', $menu_class );
	}

	$html = wp_nav_menu(
		[
			'container'   => 'ul',
			'menu'        => $menu,
			'menu_class'  => $menu_class,
			'link_before' => genesis_markup(
				[
					'open'    => '<span %s>',
					'context' => 'nav-link-wrap',
					'echo'    => false,
				]
			),
			'link_after'  => genesis_markup(
				[
					'close'   => '</span>',
					'context' => 'nav-link-wrap',
					'echo'    => false,
				]
			),
			'echo'        => false,
			'fallback_cb' => '',
		]
	);

	if ( $html ) {

		$object = wp_get_nav_menu_object( $menu );
		$class  = '';

		if ( $object ) {
			$slug      = $object->slug;
			$locations = get_nav_menu_locations();
			$slug      = $locations && isset( $locations[ $slug ] ) ? $slug . '-menu' : $slug; // Prevent class name clash with official menu locations.
			$class     = 'nav-' .  $slug;
		}

		$atts   = [
			'class' => $class,
			'style' => '',
		];

		if ( $args['align'] ) {
			switch ( trim( $args['align'] ) ) {
				case 'left':
					$atts['style'] .= '--menu-justify-content:flex-start;--menu-item-justify-content:flex-start;';
					if ( $list ) {
						$atts['style'] .= '--menu-item-link-justify-content:flex-start;--menu-item-link-text-align:start;';
					}
					break;
				case 'center':
					$atts['style'] .= '--menu-justify-content:center;--menu-item-justify-content:center;';
					if ( $list ) {
						$atts['style'] .= '--menu-item-link-justify-content:center;--menu-item-link-text-align:center;';
					}
					break;
				case 'right':
					$atts['style'] .= '--menu-justify-content:flex-end;--menu-item-justify-content:flex-end;';
					if ( $list ) {
						$atts['style'] .= '--menu-item-link-justify-content:flex-end;--menu-item-link-text-align:end;';
					}
					break;
			}
		}

		if ( $args['font_size'] ) {
			if ( in_array( $args['font_size'], ['xs', 'sm', 'md', 'lg', 'xl', 'xxl' ] ) ) {
				$size = sprintf( 'var(--font-size-%s)', $args['font_size'] );
			} else {
				$size = mai_get_unit_value( $args['font-size'] );
			}

			$atts['style'] .= sprintf( '--menu-font-size:%s;', $size );
		}

		$atts['itemtype'] = 'https://schema.org/SiteNavigationElement';

		$html = genesis_markup(
			[
				'open'    => '<nav %s>',
				'close'   => '</nav>',
				'content' => $html,
				'context' => 'nav-menu',
				'echo'    => false,
				'atts'    => $atts,
				'params'  => $args,
			]
		);
	}

	return $html;
}

/**
 * Returns menu items by menu location slug.
 *
 * @since 2.7.0
 *
 * @param string $location The menu location slug
 *
 * @return array
 */
function mai_get_menu_items_by_location( $location ) {
	static $menu_items = null;

	if ( is_array( $menu_items ) && isset( $menu_items[ $location ] ) ) {
		return $menu_items[ $location ];
	}

	if ( ! is_array( $menu_items ) ) {
		$menu_items = [];
	}

	$items = [];
	$locations = get_nav_menu_locations();
	if ( $locations && isset( $locations[ $location ] ) && $locations[ $location ] ) {
		$menu                    = get_term( $locations[ $location ] );
		$menu_items[ $location ] = $menu && ! is_wp_error( $menu ) ? wp_get_nav_menu_items( $menu->term_id ) : $items;
	}

	return $menu_items[ $location ];
}

/**
 * Gets menu default args.
 * For use with mai_menu shorcode.
 *
 * @since 2.12.0
 *
 * @return array
 */
function mai_get_menu_defaults() {
	$defaults = [
		'id'        => '',       // The menu ID, slug, name.
		'class'     => '',       // HTML classes.
		'align'     => 'center', // Accepts left, center, or right.
		'display'   => '',       // Accepts list.
		'font_size' => '',       // Accepts size values 'sm', 'md', etc. or integer or unit value.
	];
	return apply_filters( 'mai_menu_defaults', $defaults );
}

/**
 * Gets a user avatar.
 *
 * @since 2.7.0
 *
 * @param int|string The user ID or 'current'.
 * @param int|string The avatar size. Accepts integer or unit value; 20px, 1em, etc.
 *
 * @return string
 */
function mai_get_avatar( $args = [] ) {
	$args = shortcode_atts(
		mai_get_avatar_default_args(),
		$args,
		'mai_avatar'
	);

	switch ( $args['id'] ) {
		case 'current':
			$args['id'] = mai_get_author_id();
		break;
		case 'user':
			$args['id'] = get_current_user_id();
		break;
		default:
			$args['id'] = $args['id'];
	}

	if ( ! $args['id'] ) {
		return;
	}

	$avatar = get_avatar( $args['id'], absint( $args['size'] ) );

	if ( ! $avatar ) {
		return;
	}

	$atts = [
		'class' => 'mai-avatar',
		'style' => '',
	];

	// Build inline styles.
	$atts['style'] .= sprintf( '--avatar-display:%s;', $args['display'] );
	$atts['style'] .= sprintf( '--avatar-max-width:%s;', mai_get_unit_value( $args['size'] ) );
	$atts['style'] .= sprintf( '--avatar-margin:%s %s %s %s;', mai_get_unit_value( $args['margin_top'] ), mai_get_unit_value( $args['margin_right'] ), mai_get_unit_value( $args['margin_bottom'] ), mai_get_unit_value( $args['margin_left'] ) );

	return genesis_markup(
		[
			'open'    => "<span %s>",
			'close'   => '</span>',
			'content' => $avatar,
			'context' => 'avatar',
			'atts'    => $atts,
			'echo'    => false,
		]
	);
}

/**
 * Gets list of icon shortcode attributes.
 *
 * @since 2.7.0
 *
 * @return array
 */
function mai_get_avatar_default_args() {
	static $args = null;

	if ( ! is_null( $args ) ) {
		return $args;
	}

	$args = [
		'id'            => 'current', // Or 'user', or a user ID.
		'size'          => mai_get_image_width( 'tiny' ) / 2, // Half of the tiny size.
		'display'       => in_the_loop() ? 'inline-block' : 'block',
		'margin_top'    => 0,
		'margin_right'  => in_the_loop() ? 'var(--spacing-xs)' : 0,
		'margin_bottom' => 0,
		'margin_left'   => 0,
	];

	return $args;
}

/**
 * Gets an entry author ID.
 *
 * @since 2.7.0
 * @since 2.13.0 Switch get_the_author_meta( 'ID' )
 *        to get_post_field( 'post_author' )
 *        since it wasn't working in page header and other contexts.
 *        Was this actually from static caching?
 * @since TBD Remove static caching because it breaks on archives
 *        and other contextx when different authors are on the same page.
 *
 * @return int|false
 */
function mai_get_author_id() {
	if ( is_author() && ! in_the_loop() ) {
		$author_id = get_query_var( 'author' );
	} else {
		$author_id = get_post_field( 'post_author' );
	}

	return $author_id;
}

/**
 * Gets a star rating.
 *
 * @since 2.11.0
 *
 * @param array $args The rating args.
 *
 * @return string
 */
function mai_get_rating( $args ) {
	$args = shortcode_atts( [
		'value' => 5,
		'total' => 5,
		'size'  => '1em',
		'color' => 'gold',
		'align' => '',
	], $args, 'mai_rating' );

	$args = [
		'value' => floatval( $args['value'] ),
		'total' => absint( $args['total'] ),
		'size'  => esc_html( $args['size'] ),
		'color' => mai_get_color_value( $args['color'] ),
		'align' => esc_html( $args['align'] ),
	];

	$attr = [
		'class' => 'mai-rating',
		'style' => '',
	];

	if ( $args['align'] ) {
		$attr['style'] .= sprintf( '--mai-rating-justify-content:%s;', mai_get_flex_align( $args['align'] ) );
	}

	$split  = explode( '.', $args['value'] );
	$half   = isset( $split[1] ) && $split[1];
	$rating = floor( $args['value'] );
	$total  = $args['total'];
	$star   = mai_get_icon(
		[
			'icon'       => 'star',
			'style'      => 'solid',
			'size'       => $args['size'],
			'color_icon' => $args['color'],
			'class'      => 'mai-rating-icon',
		]
	);

	$html = genesis_markup(
		[
			'open'    => '<ul %s>',
			'context' => 'mai-rating',
			'echo'    => false,
			'atts'    => $attr,
			'params'  => [
				'args' => $args,
			],
		]
	);

		$count = 1;
		for ( $rating = 1; $rating <= $args['value']; $rating++ ) {
			if ( $rating > $total ) {
				break;
			}

			$html .= genesis_markup(
				[
					'open'    => '<li %s>',
					'close'   => '</li>',
					'context' => 'mai-rating-item',
					'content' => $star,
					'echo'    => false,
					'params'  => [
						'args' => $args,
					],
				]
			);
			$count++;
		}

		if ( $count <= $total ) {
			if ( $half ) {
				$half = mai_get_icon(
					[
						'icon'       => 'star-half-alt',
						'style'      => 'solid',
						'size'       => $args['size'],
						'color_icon' => $args['color'],
						'class'      => 'mai-rating-icon',
					]
				);
				$html .= genesis_markup(
					[
						'open'    => '<li %s>',
						'close'   => '</li>',
						'context' => 'mai-rating-item',
						'content' => $half,
						'echo'    => false,
						'params'  => [
							'args' => $args,
						],
					]
				);
				$count++;
			}

			if ( $count <= $total ) {
				$empty = mai_get_icon(
					[
						'icon'       => 'star',
						'style'      => 'light',
						'size'       => $args['size'],
						'color_icon' => $args['color'],
						'class'      => 'mai-rating-icon',
					]
				);
				for ( $count; $count <= $total; $count++ ) {
					$html .= genesis_markup(
						[
							'open'    => '<li %s>',
							'close'   => '</li>',
							'context' => 'mai-rating-item',
							'content' => $empty,
							'echo'    => false,
							'params'  => [
								'args' => $args,
							],
						]
					);
				}
			}
		}

	$html .= genesis_markup(
		[
			'close'   => '</ul>',
			'context' => 'mai-rating',
			'echo'    => false,
			'params'  => [
				'args' => $args,
			],
		]
	);

	return $html;
}

/**
 * Gets a search form.
 * Available default args:
 * [
 *    'label',        // Hidden.
 *    'placeholder',  // Defaults to "Search site".
 *    'input_value',  // Defaults to the search query.
 *    'submit_value', // Defaults to search icon. Uses this value for screen-reader-text.
 * ]
 *
 * @since 2.11.0
 *
 * @param array $args The search form args.
 *
 * @return string
 */
function mai_get_search_form( $args = [] ) {
	if ( ! class_exists( 'Genesis_Search_Form' ) ) {
		return get_search_form( false );
	}

	$searchform = new Genesis_Search_Form( $args );
	return $searchform->get_form();
}

/**
 * Gets a search icon with form for menu items
 * and mobile header.
 *
 * @since 2.11.0
 *
 * @access private The params may change in a future date.
 *
 * @param string $title     The toggle icon screen reader text.
 * @param string $icon_size The size of the icon.
 *
 * @return string
 */
function mai_get_search_icon_form( $title = '', $icon_size = '16' ) {
	$icon = mai_get_svg_icon( 'search', 'regular',
		[
			'class'  => 'search-toggle-icon',
			'width'  => mai_get_width_height_attribute( $icon_size ),
			'height' => mai_get_width_height_attribute( $icon_size ),
		]
	);

	$close = mai_get_svg_icon( 'times', 'regular',
		[
			'class'  => 'search-toggle-close',
			'width'  => mai_get_width_height_attribute( $icon_size ),
			'height' => mai_get_width_height_attribute( $icon_size ),
		]
	);

	$html = sprintf( '<button class="search-toggle" aria-expanded="false" aria-pressed="false"><span class="screen-reader-text">%s</span>%s%s</button>',
		esc_html( $title ?: __( 'Search', 'mai-engine' ) ),
		$icon,
		$close
	);

	$html .= mai_get_search_form();

	return $html;
}

/**
 * Gets DOMDocument object.
 *
 * @since  2.0.0
 * @since  2.3.0 Remove wraps to only return the html passed.
 *
 * @param string $html Any given HTML string.
 *
 * @return DOMDocument
 */
function mai_get_dom_document( $html ) {

	// Create the new document.
	$dom = new DOMDocument();

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );

	// Remove <!DOCTYPE.
	$dom->removeChild( $dom->doctype );

	// Remove <html><body></body></html>.
	$dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}

/**
 * Gets a DOMDocument first child element.
 *
 * @since 2.4.1.
 *
 * @var DOMDocument The dom document object.
 *
 * @return DOMElement $first_block The group block container.
 */
function mai_get_dom_first_child( $dom ) {
	/**
	 * The group block container.
	 *
	 * @var DOMElement $first_block The group block container.
	 */
	return $dom->childNodes && isset( $dom->childNodes[0] ) ? $dom->childNodes[0] : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
 * Localize data for editor JS.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_editor_localized_data() {
	$palette  = mai_get_editor_color_palette();
	$palette  = wp_list_pluck( $palette, 'color', 'slug' );
	$palette  = array_values( $palette ); // Remove keys.
	$data     = [
		'palette'   => $palette,
		'primary'   => __( 'Primary', 'mai-engine' ),
		'secondary' => __( 'Secondary', 'mai-engine' ),
		'outline'   => __( 'Outline', 'mai-engine' ),
		'link'      => __( 'Link', 'mai-engine' ),
	];

	$settings = mai_get_grid_block_settings();

	foreach ( $settings as $key => $field ) {
		if ( 'tab' === $field['type'] ) {
			continue;
		}

		foreach ( [ 'post', 'term', 'user' ] as $type ) {
			if ( ! in_array( $type, $field['block'], true ) ) {
				continue;
			}

			if ( isset( $field['atts']['sub_fields'] ) ) {
				foreach ( $field['atts']['sub_fields'] as $sub_key => $sub_field ) {
					$data[ $type ][ $sub_field['name'] ] = $sub_key;
				}
			} else {
				$data[ $type ][ $field['name'] ] = $key;
			}
		}
	}

	return $data;
}

/**
 * Get the post type on an admin page.
 *
 * @since 0.3.3
 *
 * @return string|null
 */
function mai_get_admin_post_type() {
	global $post, $typenow, $current_screen;

	if ( $post && $post->post_type ) {
		return $post->post_type;
	}

	if ( $typenow ) {
		return $typenow;
	}

	if ( $current_screen && $current_screen->post_type ) {
		return $current_screen->post_type;
	}

	if ( isset( $_REQUEST['post_type'] ) ) {
		return sanitize_key( $_REQUEST['post_type'] );
	}

	return null;
}

/**
 * Gets the header/footer meta setting description.
 *
 * @since 2.9.2
 *
 * @return string
 */
function mai_get_entry_meta_setting_description() {
	return sprintf( '%s <a href="https://studiopress.github.io/genesis/basics/genesis-shortcodes/" target="_blank" rel="noopener nofollow">%s</a> %s <a href="https://docs.bizbudding.com/kb/shortcodes/" target="_blank" rel="noopener nofollow">%s</a>.',
		__( 'View available shortcodes from', 'mai-engine' ),
		__( 'Genesis', 'mai-engine' ),
		__( 'and', 'mai-engine' ),
		__( 'Mai Theme', 'mai-engine' )
	);
}

/**
 * Gets the plugin updater icons.
 * This may be used in additiona Mai Plugins.
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_updater_icons() {
	$icons    = [];
	$standard = mai_get_logo_icon_1x();
	$retina   = mai_get_logo_icon_2x();
	if ( $standard && $retina ) {
		$icons = [
			'1x' => $standard,
			'2x' => $retina,
		];
	}
	return $icons;
}

/**
 * Gets the Mai Theme logo icon @1x for plugin updater.
 *
 * @since 2.11.0
 *
 * @return string
 */
function mai_get_logo_icon_1x() {
	static $icon = null;
	if ( ! is_null( $icon ) ) {
		return $icon;
	}
	$file = 'assets/img/icon-128x128.png';
	$icon = file_exists( mai_get_dir() . $file ) ? mai_get_url() . $file : '';
	return $icon;
}

/**
 * Gets the Mai Theme logo icon @1x for plugin updater.
 *
 * @since 2.11.0
 *
 * @return string
 */
function mai_get_logo_icon_2x() {
	static $icon = null;
	if ( ! is_null( $icon ) ) {
		return $icon;
	}
	$file = 'assets/img/icon-256x256.png';
	$icon = file_exists( mai_get_dir() . $file ) ? mai_get_url() . $file : '';
	return $icon;
}
