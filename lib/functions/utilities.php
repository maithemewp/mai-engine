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

	if ( is_null( $url ) ) {
		$url = trailingslashit( plugins_url( basename( mai_get_dir() ) ) );
	}

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

	if ( is_null( $base ) ) {
		$dir  = basename( dirname( dirname( __DIR__ ) ) );
		$file = mai_get_handle() . '.php';
		$base = $dir . DIRECTORY_SEPARATOR . $file;
	}

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
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $file File path.
 *
 * @return string
 */
function mai_get_asset_version( $file ) {
	$file    = str_replace( mai_get_url(), mai_get_dir(), $file );
	$version = mai_get_version();

	if ( file_exists( $file ) && ( mai_has_string( mai_get_dir(), $file ) ) ) {
		$version .= '.' . date( 'njYHi', filemtime( $file ) );
	}

	return $version;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $file File base name.
 *
 * @return string
 */
function mai_get_asset_url( $file ) {
	$type    = false !== strpos( $file, '.js' ) ? 'js' : 'css';
	$name    = str_replace( [ '.js', '.css' ], '', $file );
	$uri     = mai_get_url();
	$default = "${uri}assets/${type}/${name}.${type}";
	$min     = "${uri}assets/${type}/min/${name}.min.${type}";

	return mai_is_in_dev_mode() ? $default : $min;
}

/**
 * Returns the active child theme's config.
 *
 * @since 0.1.0
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function mai_get_config( $sub_config = 'default' ) {
	$config = require mai_get_dir() . 'config/_default.php';
	$theme  = mai_get_active_theme();
	$theme  = ( 'default' === $theme ) ? '_default' : $theme;
	$path   = mai_get_dir() . 'config/' . $theme . '.php';

	if ( is_readable( $path ) ) {
		$config = array_replace_recursive( $config, require $path );
	}

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . '/config.php';

	if ( is_readable( $child ) ) {
		$config = array_replace_recursive( $config, require $child );
	}

	$config = apply_filters( 'mai_config', $config );

	$configs[ $sub_config ] = isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];

	return apply_filters( "mai_{$sub_config}_config", $configs[ $sub_config ] );
}

/**
 * Returns the active theme key.
 *
 * Checks multiple places to find a match.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_active_theme() {
	return apply_filters( 'mai_active_theme', mai_get_engine_theme() );
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
	$options = mai_get_options( $use_cache );

	return isset( $options[ $option ] ) ? $options[ $option ] : $default;
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
	return require mai_get_dir() . 'lib/customize/' . $name . '.php';
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
function mai_get_global_styles( $key = '' ) {
	$global_styles = mai_get_config( 'global-styles' );

	return mai_isset( $global_styles, $key, $global_styles );
}

/**
 * Get the colors from Customizer, with fallback to config.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_colors() {
	$colors   = [];
	$defaults = mai_get_default_colors();
	foreach ( $defaults as $name => $color ) {
		$colors[ $name ] = mai_get_color( $name );
	}

	return array_merge( $colors, mai_get_custom_colors() );
}

/**
 * Returns the array of colors from the global styles config.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_default_colors() {
	return mai_get_global_styles( 'colors' );
}

/**
 * Returns a single color hex value from the config.
 *
 * @since 2.0.0
 *
 * @param string $name Name of the color to get.
 *
 * @return string
 */
function mai_get_default_color( $name ) {
	return mai_isset( mai_get_default_colors(), $name, '' );
}

/**
 * Get custom colors as set in Customizer.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_custom_colors() {
	static $colors = null;
	if ( ! is_null( $colors ) ) {
		return $colors;
	}
	$colors  = [];
	$options = mai_get_option( 'custom-colors', [] );
	$count   = 1;
	foreach( $options as $index => $option ) {
		$colors[ 'custom-' . $count ] = $option['color'];
		$count++;
	}
	return $colors;
}

/**
 * Returns a color option value with config default fallback.
 *
 * @since 2.0.0
 *
 * @param string $name Name of the color to get.
 *
 * @return string
 */
function mai_get_color( $name ) {
	$custom = mai_get_custom_colors();
	if ( isset( $custom[ $name ] ) ) {
		return $custom[ $name ];
	}
	return mai_get_option( 'color-' . $name, mai_get_default_color( $name ) );
}

/**
 * Returns the color palette variables.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_editor_color_palette() {
	$colors  = mai_get_colors();
	$values  = [];
	$palette = [];

	// Remove empty custom colors.
	$colors = array_filter( $colors );

	// Sort colors by lightness.
	$sorted = [];

	foreach ( $colors as $name => $hex ) {
		$sorted[ $name ] = ariColor::newColor( $hex )->lightness;
	}

	asort( $sorted );

	foreach ( $sorted as $name => $lightness ) {
		$hex = $colors[ $name ];

		// Remove duplicate hex codes.
		if ( in_array( $hex, $values, true ) ) {
			continue;
		}

		$values[] = $hex;

		// Add color.
		$palette[] = [
			'name'  => '', // No label, defaults to "Color code: #123456".
			'slug'  => mai_convert_case( $name, 'kebab' ),
			'color' => $hex,
		];
	}

	return $palette;
}

/**
 * Get color element names for settings.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_color_elements() {
	return [
		'background' => __( 'Background', 'mai-engine' ),
		'alt'        => __( 'Background Alt', 'mai-engine' ),
		'body'       => __( 'Body', 'mai-engine' ),
		'heading'    => __( 'Heading', 'mai-engine' ),
		'link'       => __( 'Link', 'mai-engine' ),
		'primary'    => __( 'Button Primary', 'mai-engine' ),
		'secondary'  => __( 'Button Secondary', 'mai-engine' ),
	];
}

/**
 * Get color choices for Kirki.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_color_choices() {
	$color_choices = [];
	$color_palette = mai_get_editor_color_palette();

	foreach ( $color_palette as $color ) {
		$color_choices[] = $color['color'];
	}

	return array_flip( array_flip( $color_choices ) );
}

/**
 * Check if a color is light.
 *
 * This helps with accessibility decisions to determine
 * whether to use a light or dark background or text color.
 *
 * @since 2.0.0
 *
 * @link  https://aristath.github.io/ariColor/
 *
 * @param string $color Any color string, including hex, rgb, rgba, etc.
 *
 * @return bool
 */
function mai_is_light_color( $color ) {
	$color = ariColor::newColor( $color );
	$limit = mai_get_global_styles( 'contrast-limit' );

	return $color->luminance > $limit;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param        $color
 * @param int    $amount
 * @param string $light_or_dark
 *
 * @return string
 */
function mai_get_color_variant( $color, $light_or_dark = 'dark', $amount = 7 ) {
	$color   = ariColor::newColor( $color );
	$value   = 'dark' === $light_or_dark ? $color->lightness - $amount : $color->lightness + $amount;
	$lighter = $color->getNew( 'lightness', $value );

	return $lighter->toCSS( 'hex' );
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

	return mai_get_option( 'mobile-menu-breakpoint', $breakpoints[ $size ] ) . $suffix;
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
 *
 * @param  string $value    The value. Could be integer 24 or with type 24px, 2rem, etc.
 * @param  string $fallback The fallback unit value.
 *
 * @return string
 */
function mai_get_unit_value( $value, $fallback = 'px' ) {
	if ( empty( $value ) || is_numeric( $value ) ) {
		return sprintf( '%s%s', intval( $value ), $fallback );
	}

	return trim( $value );
}

/**
 * Get an integer value from string.
 *
 * @since 0.1.0
 *
 * @param $string
 *
 * @return int
 */
function mai_get_integer_value( $string ) {
	return (int) preg_replace( "/[^0-9.]/", "", $string );
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

	if ( mai_is_type_single() ) {
		$image_id = get_post_meta( get_the_ID(), 'page_header_image', true );

	} elseif ( is_front_page() ) {
		$image_id = '';

		if ( 'page' === get_option( 'show_on_front' ) ) {
			$image_id = get_post_meta( get_option( 'page_on_front' ), 'page_header_image', true );
		}
	} elseif ( is_home() ) {
		$image_id = get_post_meta( get_option( 'page_for_posts' ), 'page_header_image', true );

	} elseif ( mai_is_type_archive() ) {
		if ( is_category() || is_tag() || is_tax() ) {

			/**
			 * @var WP_Query $wp_query
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

	if ( ! $image_id && is_singular() ) {
		$args = mai_get_template_args();

		if ( isset( $args['page-header-featured'] ) && $args['page-header-featured'] ) {
			$image_id = get_post_thumbnail_id();
		}
	}

	if ( ! $image_id && mai_get_option( 'page-header-image' ) ) {
		$image_id = mai_get_option( 'page-header-image' );
	}

	if ( ! $image_id && mai_get_config( 'page-header' )['image'] ) {
		$image_id = mai_get_option( 'page-header-image' );
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
		$title = apply_filters( 'genesis_latest_posts_title', esc_html__( 'Latest Posts', 'mai-engine' ) );

	} elseif ( is_home() ) {
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

	if ( is_singular() ) {
		$description = get_post_meta( get_the_ID(), 'page_header_description', true );

	} elseif ( is_front_page() ) {
		$description = '';

	} elseif ( is_home() ) {
		$description = get_post_meta( get_option( 'page_for_posts' ), 'page_header_description', true );

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

	return $description;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_site_layout_choices() {
	return [ '' => esc_html__( 'Default', 'mai-engine' ) ] + genesis_get_layouts_for_customizer();
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
 * Get content type choices for Kirki.
 *
 * @since 0.2.0
 *
 * @param bool $archive Whether archive or single content type choices.
 *
 * @return array
 */
function mai_get_content_type_choices( $archive = false ) {
	$choices = [
		'post' => esc_html__( 'Post', 'mai-engine' ),
	];

	if ( ! $archive ) {
		$choices['page'] = esc_html__( 'Page', 'mai-engine' );
	}

	$post_types = get_post_types( [
		'public'   => true,
		'_builtin' => false,
	], 'objects' );

	if ( $post_types ) {
		foreach ( $post_types as $name => $post_type ) {
			// Skip post types without archives.
			if ( $archive && ! (bool) $post_type->has_archive ) {
				continue;
			}
			$choices[ $name ] = $post_type->label;
		}
	}

	if ( $archive ) {
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
	} else {
		$choices += [
			'404-page' => __( '404', 'mai-engine' ),
		];
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
	$choices = mai_get_content_type_choices( $archive );
	$feature = $archive ? 'mai-archive-settings' : 'mai-single-settings';

	foreach ( $choices as $name => $label ) {
		// If type is a post type.
		if ( post_type_exists( $name ) ) {
			$post_type = get_post_type_object( $name );

			if ( ! $post_type->_builtin && ! post_type_supports( $post_type->name, $feature ) ) {
				unset( $choices[ $name ] );
			}

		} // If type is a taxonomy.
		elseif ( taxonomy_exists( $name ) ) {
			$post_type = mai_get_taxonomy_post_type( $name );
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
	$taxonomy = get_taxonomy( $taxonomy );
	if ( $taxonomy ) {
		$post_type = reset( $taxonomy->object_type );
		if ( post_type_exists( $post_type ) ) {
			return $post_type;
		}
	}

	return false;
}

/**
 * Get the read more ellipses.
 * Filtered so devs can change.
 *
 * @return string;
 */
function mai_get_ellipsis() {
	return apply_filters( 'mai_read_more_ellipses', ' &hellip;' );
}

/**
 * Get post content by slug or ID.
 * Great for displaying reusable blocks in areas that are not block enabled.
 *
 * Switched from get_post_field to WP_Query so blocks are parsed and shortcodes are rendered better.
 *
 * @since 0.3.0
 *
 * @param int|string $post_slug_or_id The post slug or ID.
 *
 * @return string
 */
function mai_get_post_content( $post_slug_or_id ) {
	$post_id = $post_type = false;

	if ( is_numeric( $post_slug_or_id ) ) {
		$post_id   = $post_slug_or_id;
		$post_type = get_post_type( $post_id );

	} else {
		$post = get_page_by_path( $post_slug_or_id, OBJECT, 'wp_block' );

		if ( $post ) {
			$post_id   = $post->ID;
			$post_type = $post->post_type;
		}
	}

	if ( ! ( $post_id && $post_type ) ) {
		return;
	}

	$loop = new WP_Query( [
		'post_type'              => $post_type,
		'post__in'               => [ $post_id ],
		'posts_per_page'         => 1,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	] );

	ob_start();
	if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();
		the_content();
	endwhile; endif;
	wp_reset_postdata();
	$content = ob_get_clean();

	return $content;
}

/**
 * A big ol' helper/cleanup function to
 * enabled embeds inside the shortcodes and
 * keep the shorcodes from causing extra p's and br's.
 *
 * Most of the order comes from /wp-includes/default-filters.php
 *
 * @since  0.3.0
 *
 * @param  string $content The unprocessed content.
 *
 * @return string  The processed content.
 */
function mai_get_processed_content( $content ) {
	/**
	 * @var WP_Embed $wp_embed
	 */
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );              // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content );          // WP runs priority 8.
	$content = wptexturize( $content );                       // WP runs priority 10.
	$content = wpautop( $content );                           // WP runs priority 10.
	$content = shortcode_unautop( $content );                 // WP runs priority 10.
	$content = wp_make_content_images_responsive( $content ); // WP runs priority 10.
	$content = do_shortcode( $content );                      // WP runs priority 11.
	$content = convert_smilies( $content );                   // WP runs priority 20.

	return $content;
}

/**
 * Get the default read more text.
 * This filter is run before any custom read more text is added via Customizer settings.
 * If you want to filter after that, use `genesis_markup_entry-more-link_content` filter.
 *
 * @since 2.0.0
 *
 * @return string
 */
function mai_get_read_more_text() {
	$text = apply_filters( 'mai_read_more_text', esc_html__( 'Read More', 'mai-engine' ) );

	return sanitize_text_field( $text );
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
function mai_get_menu( $menu, $args = '' ) {
	$menu_class = 'menu genesis-nav-menu';

	if ( isset( $args['class'] ) && $args['class'] ) {
		$menu_class = mai_add_classes( $args['class'], $menu_class );
	}

	$list = isset( $args['display'] ) && ( 'list' === $args['display'] );

	if ( $list ) {
		$menu_class = mai_add_classes( 'menu-list', $menu_class );
	}

	$html = wp_nav_menu( [
		'container'   => 'ul',
		'menu'        => $menu,
		'menu_class'  => $menu_class,
		'echo'        => false,
		'fallback_cb' => '',
	] );

	if ( $html ) {
		$atts = [
			'style' => '',
		];

		if ( $list ) {
			$atts['style'] .= '--menu-display:block;--menu-item-link-padding:var(--spacing-xs) 0;';
		}

		if ( isset( $args['align'] ) && $args['align'] ) {
			switch ( trim( $args['align'] ) ) {
				case 'left':
					$atts['style'] .= '--menu-justify-content:flex-start;--menu-item-justify-content:flex-start;--menu-item-link-justify-content:flex-start;';
					break;
				case 'center':
					$atts['style'] .= '--menu-justify-content:center;--menu-item-justify-content:center;--menu-item-link-justify-content:center;';
					break;
				case 'right':
					$atts['style'] .= '--menu-justify-content:flex-end;--menu-item-justify-content:flex-end;--menu-item-link-justify-content:flex-end;';
					break;
			}
		}
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
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array $args Icon args.
 *
 * @return null|string
 */
function mai_get_icon( $args ) {
	static $id = 0;

	$id++;

	$args = shortcode_atts(
		mai_get_icon_default_args(),
		$args,
		'mai_icon'
	);

	$args = array_map(
		'esc_html',
		$args
	);

	$svg = mai_get_svg_icon( $args['icon'], $args['style'] );

	if ( ! $svg ) {
		return '';
	}

	// Build classes.
	$class = sprintf( 'mai-icon mai-icon-%s', $id );

	// Add custom classes.
	if ( ! empty( $args['class'] ) ) {
		$class .= ' ' . esc_attr( $args['class'] );
	}

	// Get it started.
	$attributes = [
		'class' => $class,
		'style' => '',
	];

	// Build inline styles.
	$attributes['style'] .= sprintf( '--icon-display:%s;', $args['display'] );
	$attributes['style'] .= sprintf( '--icon-align:%s;', $args['align'] );
	$attributes['style'] .= sprintf( '--icon-margin:%s %s %s %s;', mai_get_unit_value( $args['margin_top'] ), mai_get_unit_value( $args['margin_right'] ), mai_get_unit_value( $args['margin_bottom'] ), mai_get_unit_value( $args['margin_left'] ) );
	$attributes['style'] .= sprintf( '--icon-padding:%s;', mai_get_unit_value( $args['padding'] ) );

	if ( $args['size'] ) {
		$attributes['style'] .= sprintf( '--icon-size:%s;', mai_get_unit_value( $args['size'] ) );
	}

	if ( $args['color_icon'] ) {
		$attributes['style'] .= sprintf( '--icon-color:%s;', $args['color_icon'] );
	}

	if ( $args['color_background'] ) {
		$attributes['style'] .= sprintf( '--icon-background:%s;', $args['color_background'] );
	}

	if ( $args['color_shadow'] ) {
		$attributes['style'] .= sprintf( '--icon-box-shadow:%s %s %s %s;', mai_get_unit_value( $args['x_offset'] ), mai_get_unit_value( $args['y_offset'] ), mai_get_unit_value( $args['blur'] ), $args['color_shadow'] );
	}

	if ( $args['color_text_shadow'] ) {
		$attributes['style'] .= sprintf( '--icon-text-shadow:%s %s %s %s;', mai_get_unit_value( $args['text_shadow_x_offset'] ), mai_get_unit_value( $args['text_shadow_y_offset'] ), mai_get_unit_value( $args['text_shadow_blur'] ), $args['color_text_shadow'] );
	}

	if ( $args['border_width'] && $args['color_border'] ) {
		$attributes['style'] .= sprintf( '--icon-border:%s solid %s;', mai_get_unit_value( $args['border_width'] ), mai_get_unit_value( $args['color_border'] ) );
	}

	if ( $args['border_radius'] ) {
		$radius              = explode( ' ', trim( $args['border_radius'] ) );
		$radius              = array_map( 'mai_get_unit_value', $radius );
		$radius              = array_filter( $radius );
		$attributes['style'] .= sprintf( '--icon-border-radius:%s;', implode( ' ', $radius ) );
	}

	return genesis_markup(
		[
			'open'    => '<span %s><span class="mai-icon-wrap">',
			'close'   => '</span></span>',
			'content' => $svg,
			'context' => 'mai-icon',
			'echo'    => false,
			'atts'    => $attributes,
		]
	);
}

/**
 * Helper function that returns list of shortcode attributes.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_icon_default_args() {
	return [
		'style'                => 'light',
		'icon'                 => 'bolt',
		'display'              => 'flex',
		'align'                => 'center',
		'size'                 => '40',
		'class'                => '',
		'color_icon'           => 'currentColor',
		'color_background'     => '',
		'color_border'         => '',
		'color_shadow'         => '',
		'color_text_shadow'    => '',
		'margin_top'           => 0,
		'margin_right'         => 0,
		'margin_left'          => 0,
		'margin_bottom'        => 0,
		'padding'              => 0,
		'border_width'         => 0,
		'border_radius'        => '50%',
		'x_offset'             => 0,
		'y_offset'             => 0,
		'blur'                 => 0,
		'text_shadow_x_offset' => 0,
		'text_shadow_y_offset' => 0,
		'text_shadow_blur'     => 0,
	];
}

/**
 * Description of expected behavior.
 *
 * @since 0.2.0
 *
 * @param string $name  SVG name.
 * @param string $class SVG class name.
 *
 * @return string
 */
function mai_get_svg( $name, $class = '' ) {
	$file = mai_get_dir() . "assets/svg/$name.svg";

	if ( ! file_exists( $file ) ) {
		return '';
	}

	$svg = file_get_contents( $file );

	if ( $class ) {
		$svg = str_replace( '<svg', "<svg class='$class' ", $svg );
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return $svg;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $name  SVG name.
 * @param string $style SVG style.
 * @param array  $atts  SVG HTML attributes.
 *
 * @return string
 */
function mai_get_svg_icon( $name, $style = 'light', $atts = [] ) {
	$file = mai_get_dir() . "assets/icons/svgs/$style/$name.svg";

	if ( ! file_exists( $file ) ) {
		return '';
	}

	$svg = file_get_contents( $file );

	if ( $atts ) {
		$dom  = mai_get_dom_document( $svg );
		$svgs = $dom->getElementsByTagName( 'svg' );

		foreach ( $atts as $att => $value ) {
			$svgs[0]->setAttribute( $att, $value );
		}

		$svg = $dom->saveHTML();
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return $svg;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $name  SVG name.
 * @param string $style SVG style.
 *
 * @return string
 */
function mai_get_svg_icon_url( $name, $style = 'light' ) {
	return mai_get_url() . "assets/icons/svgs/$style/$name.svg";
}

/**
 * Get DOMDocument object. expected behavior.
 *
 * @since 2.0.0
 *
 * @param string $html
 *
 * @return object
 */
function mai_get_dom_document( $html ) {

	// Create the new document.
	$dom = new DOMDocument;

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', "UTF-8" ) );

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
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
	$data     = [ 'palette' => $palette ];
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
