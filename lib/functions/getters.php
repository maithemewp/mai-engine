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
 * Returns an array of plugin data from the main plugin file.
 *
 * @since 0.1.0
 *
 * @param string $header Optionally return one key.
 *
 * @return array|string|null
 */
function mai_get_plugin_data( $header = '' ) {
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

	if ( array_key_exists( $header, $data ) ) {
		return $data[ $header ];
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
	$file = str_replace( mai_get_url(), mai_get_dir(), $file );

	return file_exists( $file ) && mai_is_in_dev_mode() && mai_has_string( mai_get_dir(), $file ) ? filemtime( $file ) : mai_get_version();
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

	// Setup caching.
	static $configs = null;
	if ( isset( $configs[ $sub_config ] ) ) {
		return $configs[ $sub_config ];
	}

	$config = require mai_get_dir() . 'config/_default/config.php';
	$theme  = mai_get_dir() . 'config/' . mai_get_active_theme() . '/config.php';

	if ( is_readable( $theme ) ) {
		$config = array_replace_recursive( $config, require $theme );
	}

	$configs[ $sub_config ] = isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . '/config.php';

	if ( is_readable( $child ) ) {
		$configs[ $sub_config ] = require $child;
	}

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
	static $theme = null;

	if ( is_null( $theme ) ) {
		if ( ! $theme ) {
			$theme = get_theme_support( 'mai' );
		}

		if ( ! $theme ) {
			$theme = genesis_get_theme_handle();
		}

		if ( ! $theme ) {
			$theme = wp_get_theme()->get( 'TextDomain' );
		}

		if ( ! $theme ) {
			$onboarding_file = get_stylesheet_directory() . '/config/onboarding.php';

			if ( is_readable( $onboarding_file ) ) {
				$onboarding_config = require $onboarding_file;

				if ( isset( $onboarding_config['dependencies']['mai'] ) ) {
					$theme = $onboarding_config['dependencies']['mai'];
				}
			}
		}

		if ( ! $theme || ! in_array( $theme, mai_get_child_themes(), true ) ) {
			$theme = 'default';
		}
	}

	return str_replace( 'mai-', '', $theme );
}

/**
 * Returns an array of all BizBudding child themes.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_child_themes() {
	$child_themes = [];
	$files        = glob( mai_get_dir() . 'config/*', GLOB_ONLYDIR );

	foreach ( $files as $file ) {
		$child_themes[] = 'mai-' . basename( $file, '.php' );
	}

	return $child_themes;
}

/**
 * Returns an array of the themes JSON variables.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_variables() {
	static $variables;

	if ( is_null( $variables ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$defaults = json_decode( file_get_contents( mai_get_dir() . 'config/_default/config.json' ), true );
		$file     = mai_get_dir() . 'config/' . mai_get_active_theme() . '/config.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$theme     = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : [];
		$variables = array_replace_recursive( $defaults, $theme );
	}

	return $variables;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_colors() {
	static $colors = [];

	if ( empty( $colors ) ) {
		$colors = mai_get_variables()['colors'];

		foreach ( $colors as $name => $hex ) {
			$colors[ $name ] = $hex;
		}
	}

	return $colors;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $color Name of the color to get.
 *
 * @return string
 */
function mai_get_color( $color = null ) {
	$colors = mai_get_colors();

	return isset( $colors[ $color ] ) ? $colors[ $color ] : '';
}

/**
 * Returns the color palette variables.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_color_palette() {
	$colors  = mai_get_colors();
	$palette = [];

	foreach ( $colors as $color => $hex ) {
		$palette[] = [
			'name'  => ucwords( $color ),
			'slug'  => $color,
			'color' => $hex,
		];
	}

	return $palette;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_breakpoints() {
	static $breakpoints = [];

	if ( empty( $breakpoints ) ) {
		$breakpoint        = mai_get_variables()['breakpoint'];
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
function mai_get_breakpoint( $size = 'md', $suffix = '' ) {
	$breakpoints = mai_get_breakpoints();

	return $breakpoints[ $size ] . '';
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
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $value Gets flex align rule.
 *
 * @return string
 */
function mai_get_flex_align( $value ) {
	switch ( $value ) {
		case 'start':
		case 'top':
			$return = 'flex-start';
			break;
		case 'center':
		case 'middle':
			$return = 'center';
			break;
		case 'right':
		case 'bottom':
			$return = 'flex-end';
			break;
		default:
			$return = 'unset';
	}

	return $return;
}

/**
 * Get the unit value.
 *
 * If only a number value, force to pixels.
 *
 * @since 0.1.0
 *
 * @param  string $value The value. Could be integer 24 or with type 24px, 2rem, etc.
 *
 * @return string
 */
function mai_get_unit_value( $value ) {
	if ( empty( $value ) || is_numeric( $value ) ) {
		return sprintf( '%spx', intval( $value ) );
	}

	return trim( $value );
}

/**
 * Get the columns at different breakpoints.
 *
 * @since 0.1.0
 *
 * @param array $args Column args.
 *
 * @return array
 */
function mai_get_breakpoint_columns( $args ) {

	$columns = [
		'lg' => $args['columns'],
	];

	if ( $args['columns_responsive'] ) {
		$columns['md'] = $args['columns_md'];
		$columns['sm'] = $args['columns_sm'];
		$columns['xs'] = $args['columns_xs'];
	} else {
		switch ( $args['columns'] ) {
			case 6:
				$columns['md'] = 4;
				$columns['sm'] = 3;
				$columns['xs'] = 2;
				break;
			case 5:
				$columns['md'] = 3;
				$columns['sm'] = 2;
				$columns['xs'] = 2;
				break;
			case 4:
				$columns['md'] = 4;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
				break;
			case 3:
				$columns['md'] = 3;
				$columns['sm'] = 1;
				$columns['xs'] = 1;
				break;
			case 2:
				$columns['md'] = 2;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
				break;
			case 1:
				$columns['md'] = 1;
				$columns['sm'] = 1;
				$columns['xs'] = 1;
				break;
			case 0: // Auto.
				$columns['md'] = 0;
				$columns['sm'] = 0;
				$columns['xs'] = 0;
				break;
		}
	}

	return $columns;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $alignment Text alignment.
 *
 * @return string
 */
function mai_get_align_text( $alignment ) {
	switch ( $alignment ) {
		case 'start':
		case 'top':
			$value = 'start';
			break;
		case 'center':
		case 'middle':
			$value = 'center';
			break;
		case 'bottom':
		case 'end':
			$value = 'end';
			break;
		default:
			$value = 'unset';
	}

	return $value;
}

/**
 * Return content stripped down and limited content.
 *
 * Strips out tags and shortcodes, limits the output to `$max_char` characters.
 *
 * @since 0.1.0
 *
 * @param string $content The content to limit.
 * @param int    $limit   The maximum number of characters to return.
 *
 * @return string
 */
function mai_get_content_limit( $content, $limit ) {

	// Strip tags and shortcodes so the content truncation count is done correctly.
	$content = strip_tags( strip_shortcodes( $content ), apply_filters( 'get_the_content_limit_allowedtags', '<script>,<style>' ) );

	// Remove inline styles / scripts.
	$content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );

	// Truncate $content to $limit.
	$content = genesis_truncate_phrase( $content, $limit );

	return $content;
}

/**
 * Description of expected behavior.
 *
 * @since  1.0.0
 *
 * @return array
 */
function mai_get_template_args() {
	// Setup cache.
	static $args = null;
	if ( ! is_null( $args ) ) {
		return $args;
	}

	$name    = '';
	$context = '';

	if ( mai_is_type_archive() ) {
		$settings = mai_get_config( 'archive-settings' );
		$name     = mai_get_archive_args_name();
		$context  = 'archive';

	} elseif ( is_singular() ) {
		$name     = mai_get_singular_args_name();
		$context  = 'single';
	}

	// Bail if no data.
	if ( ! ( $name && $context ) ) {
		return [];
	}

	// Get settings.
	$settings = mai_get_config( $context . '-settings' );

	// Bail if no settings.
	if ( ! $settings ) {
		return [];
	}

	// Build key and parse args.
	$key      = sprintf( 'mai_%s_%s', $context, $name );
	$defaults = [ 'context' => $context ] + wp_list_pluck( $settings, 'default', 'name' );
	$args     = wp_parse_args( get_option( $key, [] ), $defaults );

	// Allow devs to filter.
	$args = apply_filters( 'mai_template_args', $args, $context );

	// Sanitize.
	$args = mai_get_sanitized_entry_args( $args, $context );

	return $args;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array  $args    Entry args.
 * @param string $context The args context..
 *
 * @return mixed
 */
function mai_get_sanitized_entry_args( $args, $context ) {

	// Get settings. Cached so it's fine to get again.
	$settings = mai_get_config( $context . '-settings' );

	// Bail if no settings.
	if ( ! $settings ) {
		return $args;
	}

	// Get sanitize array.
	$sanitize = wp_list_pluck( $settings, 'sanitize', 'name' );

	// Sanitize.
	foreach ( $args as $name => $value ) {
		// Skip if not set.
		if ( ! isset( $sanitize[ $name ] ) ) {
			continue;
		}
		$function = $sanitize[ $name ];
		if ( is_array( $value ) ) {
			$escaped = [];
			foreach ( $value as $key => $val ) {
				$escaped[ $key ] = $function( $val );
			}
			$args[ $name ] = $escaped;
		} else {
			$args[ $name ] = $function( $value );
		}
	}

	return $args;
}

/**
 * Get the name to be used in the main args function/helpers.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_archive_args_name() {

	// Get the name.
	if ( is_home() ) {
		$name = 'post';
	} elseif ( is_category() ) {
		$name = 'category';
	} elseif ( is_tag() ) {
		$name = 'post_tag';
	} elseif ( is_tax() ) {
		$name = get_query_var( 'taxonomy' );
	} elseif ( is_post_type_archive() ) {
		$name = get_query_var( 'post_type' );
	} elseif ( is_search() ) {
		$name = 'search';
	} elseif ( is_author() ) {
		$name = 'author';
	} elseif ( is_date() ) {
		$name = 'date';
	} else {
		$name = 'post';
	}

	// If archive isn't supported in config, use 'post'.
	if ( 'post' !== $name && ! in_array( $name, (array) mai_get_config( 'archive-settings' ), true ) ) {
		return 'post';
	}

	return $name;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return false|mixed|string
 */
function mai_get_singular_args_name() {
	return mai_get_post_type();
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $size  Image size.
 * @param string $ratio Aspect ratio.
 *
 * @return array
 */
function mai_get_image_sizes_from_aspect_ratio( $size = 'md', $ratio = '16:9' ) {
	$ratio       = explode( ':', $ratio );
	$x           = $ratio[0];
	$y           = $ratio[1];
	$breakpoints = mai_get_breakpoints();
	$width       = isset( $breakpoints[ $size ] ) ? (int) mai_get_breakpoint( $size ) : (int) $size;
	$height      = $width / $x * $y;

	return [ $width, $height, true ];
}

function mai_get_grid_show_choices() {
	$choices = [
		'image'       => esc_html__( 'Image', 'mai-engine' ),
		'title'       => esc_html__( 'Title', 'mai-engine' ),
		'header_meta' => esc_html__( 'Header Meta', 'mai-engine' ),
		'excerpt'     => esc_html__( 'Excerpt', 'mai-engine' ),
		'content'     => esc_html__( 'Content', 'mai-engine' ),
		'more_link'   => esc_html__( 'Read More link', 'mai-engine' ),
		'footer_meta' => esc_html__( 'Footer Meta', 'mai-engine' ),
	];
	return $choices;
}

function mai_get_archive_show_choices() {
	$choices = [
		'image'                        => esc_html__( 'Image', 'mai-engine' ),
		'genesis_entry_header'         => 'genesis_entry_header',
		'title'                        => esc_html__( 'Title', 'mai-engine' ),
		'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
		'genesis_before_entry_content' => 'genesis_before_entry_content',
		'excerpt'                      => esc_html__( 'Excerpt', 'mai-engine' ),
		'content'                      => esc_html__( 'Content', 'mai-engine' ),
		'genesis_entry_content'        => 'genesis_entry_content',
		'more_link'                    => esc_html__( 'Read More link', 'mai-engine' ),
		'genesis_after_entry_content'  => 'genesis_after_entry_content',
		'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
		'genesis_entry_footer'         => 'genesis_entry_footer',
	];
	return $choices;
}

function mai_get_single_show_choices( $name ) {
	$choices = [
		'image'                        => esc_html__( 'Image', 'mai-engine' ),
		'genesis_entry_header'         => 'genesis_entry_header',
		'title'                        => esc_html__( 'Title', 'mai-engine' ),
		'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
		'genesis_before_entry_content' => 'genesis_before_entry_content',
		'content'                      => esc_html__( 'Content', 'mai-engine' ),
		'genesis_entry_content'        => 'genesis_entry_content',
		'more_link'                    => esc_html__( 'Read More link', 'mai-engine' ),
		'genesis_after_entry_content'  => 'genesis_after_entry_content',
		'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
		'genesis_entry_footer'         => 'genesis_entry_footer',
		'author_box'                   => esc_html__( 'Author Box', 'mai-engine' ),
		'after_entry'                  => esc_html__( 'After Entry Widget Area', 'mai-engine' ),
		'adjacent_entry_nav'           => esc_html__( 'Previous/Next Entry Nav', 'mai-engine' ),
	];
	if ( mai_post_type_has_page_header( $name ) ) {
		// TODO: Check if it's enabled by post/content type, and remove 'title'.
	}
	return $choices;
}

function mai_get_site_layout_choices() {
	return [ '' => esc_html__( 'Site Default', 'mai-engine' ) ] + genesis_get_layouts_for_customizer();
}

function mai_get_image_size_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$sizes = mai_get_available_image_sizes();
	foreach ( $sizes as $index => $value ) {
		$choices[ $index ] = sprintf( '%s (%s x %s)', $index, $value['width'], $value['height'] );
	}

	return $choices;
}

function mai_get_image_orientation_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$all = [
		'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
		'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
		'square'    => esc_html__( 'Square', 'mai-engine' ),
	];
	$orientations = mai_get_available_image_orientations();
	foreach( $orientations as $orientation ) {
		$choices[ $orientation ] = $all[ $orientation ];
	}
	$choices['custom'] = esc_html__( 'Custom', 'mai-engine' );
	return $choices;
}

function mai_get_columns_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	return [
		'1' => esc_html__( '1', 'mai-engine' ),
		'2' => esc_html__( '2', 'mai-engine' ),
		'3' => esc_html__( '3', 'mai-engine' ),
		'4' => esc_html__( '4', 'mai-engine' ),
		'5' => esc_html__( '5', 'mai-engine' ),
		'6' => esc_html__( '6', 'mai-engine' ),
		'0' => esc_html__( 'Auto', 'mai-engine' ),
	];
}

function mai_get_post_type_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$post_types = get_post_types(
		[
			'public'             => true,
			'publicly_queryable' => true,
		],
		'objects',
		'or'
	);
	unset( $post_types['attachment'] );
	if ( $post_types ) {
		foreach ( $post_types as $name => $post_type ) {
			$choices[ $name ] = $post_type->label;
		}
	}

	return $choices;
}

function mai_get_taxonomy_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$taxonomies = get_taxonomies(
		[
			'public'             => true,
			'publicly_queryable' => true,
		],
		'objects',
		'or'
	);
	if ( $taxonomies ) {
		unset( $taxonomies['post_format'] );
		unset( $taxonomies['yst_prominent_words'] );
		foreach ( $taxonomies as $name => $taxonomy ) {
			// TODO: These should be IDs.
			$choices[ $name ] = $taxonomy->label;
		}
	}

	return $choices;
}

function mai_get_post_type_taxonomy_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$post_types = mai_get_acf_request( 'post_type' );
	if ( ! $post_types ) {
		return $choices;
	}
	foreach ( (array) $post_types as $post_type ) {
		$taxonomies = get_object_taxonomies( sanitize_text_field( wp_unslash( $post_type ) ), 'objects' );
		if ( $taxonomies ) {
			unset( $taxonomies['post_format'] );
			unset( $taxonomies['yst_prominent_words'] );
			foreach ( $taxonomies as $name => $taxo ) {
				$choices[ $name ] = $taxo->label;
			}
		}
	}

	return $choices;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param $request
 *
 * @return bool
 */
function mai_get_acf_request( $request ) {
	if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST[ $request ] ) && ! empty( $_REQUEST[ $request ] ) ) {
		return $_REQUEST[ $request ];
	}

	return false;
}

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

	$svg = mai_get_svg( $args['icon'], $args['style'] );

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
	$html       = '';
	$attributes = [
		'class' => $class,
		'style' => '',
	];

	// Build inline styles.
	$attributes['style'] .= sprintf( '--icon-display:%s;', $args['display'] );
	$attributes['style'] .= sprintf( '--icon-align:%s;', $args['align'] );
	$attributes['style'] .= sprintf( '--icon-size:%s;', mai_get_unit_value( $args['size'] ) );
	$attributes['style'] .= sprintf( '--icon-color:%s;', $args['color_icon'] );
	$attributes['style'] .= sprintf( '--icon-margin:%s %s %s %s;', mai_get_unit_value( $args['margin_top'] ), mai_get_unit_value( $args['margin_right'] ), mai_get_unit_value( $args['margin_bottom'] ), mai_get_unit_value( $args['margin_left'] ) );
	$attributes['style'] .= sprintf( '--icon-padding:%s %s %s %s;', mai_get_unit_value( $args['padding_top'] ), mai_get_unit_value( $args['padding_right'] ), mai_get_unit_value( $args['padding_bottom'] ), mai_get_unit_value( $args['padding_left'] ) );
	if ( $args['color_background'] ) {
		$attributes['style'] .= sprintf( '--icon-background:%s;', $args['color_background'] );
	}
	if ( $args['color_shadow'] ) {
		$attributes['style'] .= sprintf( '--icon-shadow:%s;', $args['color_shadow'] );
	}
	if ( $args['border_width'] && $args['color_border'] ) {
		$attributes['style'] .= sprintf( '--icon-border:%s solid %s;', mai_get_unit_value( $args['border_width'] ), mai_get_unit_value( $args['color_border'] ) );
	}
	if ( $args['border_radius'] ) {
		$attributes['style'] .= sprintf( '--icon-border-radius:%s;', $args['border_radius'] );
	}

	// TODO: x_offset, y_offset, blur.

	return genesis_markup(
		[
			'open'    => '<span %s>',
			'close'   => '</span>',
			'content' => $svg,
			'context' => 'mai-icon',
			'echo'    => false,
			'atts'    => $attributes,
		]
	);

}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $name
 * @param string $style
 *
 * @return string
 */
function mai_get_svg( $name, $style = 'regular' ) {
	$file = mai_get_dir() . "vendor/fortawesome/font-awesome/svgs/$style/$name.svg";

	if ( ! file_exists( $file ) ) {
		return '';
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return file_get_contents( $file );
}
