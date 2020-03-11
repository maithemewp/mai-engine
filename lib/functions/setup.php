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

add_action( 'genesis_setup', 'mai_setup', 100 );
/**
 * Theme setup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_setup() {
	// Get active theme.
	$active_theme = mai_get_active_theme();
	$handle       = mai_get_handle();

	// Get setup configs.
	$custom_functions  = mai_get_config( 'custom-functions' );
	$responsive_menu   = mai_get_config( 'responsive-menu' );
	$theme_support     = mai_get_config( 'theme-support' );
	$post_type_support = mai_get_config( 'post-type-support' );
	$image_sizes       = mai_get_config( 'image-sizes' );
	$page_layouts      = mai_get_config( 'page-layouts' );
	$widget_areas      = mai_get_config( 'widget-areas' );

	// Add theme textdomain.
	load_child_theme_textdomain( genesis_get_theme_handle(), mai_get_dir() . '/assets/lang' );

	// Add editor styles (uri).
	add_editor_style( "../../plugins/$handle/assets/css/min/{$active_theme}-editor.min.css" );

	// Add custom functionality.
	is_callable( $custom_functions ) ? $custom_functions() : null;

	// Add responsive menus.
	genesis_register_responsive_menus( $responsive_menu );

	// Add theme supports.
	array_walk(
		$theme_support['add'],
		function ( $value, $key ) {
			is_int( $key ) ? add_theme_support( $value ) : add_theme_support( $key, $value );
		}
	);

	// Remove theme supports.
	array_walk(
		$theme_support['remove'],
		function ( $name ) {
			remove_theme_support( $name );
		}
	);

	// Add post type supports.
	array_walk(
		$post_type_support['add'],
		function ( $post_types, $feature ) {
			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, $feature );
			}
		}
	);

	// Remove post type supports.
	array_walk(
		$post_type_support['remove'],
		function ( $feature, $post_type ) {
			remove_post_type_support( $post_type, $feature );
		}
	);

	// Add image sizes.
	array_walk(
		$image_sizes['add'],
		function ( $args, $name ) {
			if ( is_array( $args ) ) {
				add_image_size( $name, $args[0], $args[1], $args[2] );
			} elseif ( $args ) {
				$sm = mai_get_image_sizes_from_aspect_ratio( 'xs', $args );
				$md = mai_get_image_sizes_from_aspect_ratio( 'md', $args );
				$lg = mai_get_image_sizes_from_aspect_ratio( 'xl', $args );
				add_image_size( $name . '-sm', $sm[0], $sm[1], $sm[2] );
				add_image_size( $name . '-md', $md[0], $md[1], $md[2] );
				add_image_size( $name . '-lg', $lg[0], $lg[1], $lg[2] );
			}
		}
	);

	// Remove image sizes.
	array_walk(
		$image_sizes['remove'],
		function ( $name ) {
			remove_image_size( $name );
		}
	);

	// Add page layouts.
	array_walk(
		$page_layouts['add'],
		function ( $args ) {
			genesis_register_layout( $args['id'], $args );
		}
	);

	// Remove page layouts.
	array_walk(
		$page_layouts['remove'],
		function ( $name ) {
			genesis_unregister_layout( $name );
		}
	);

	// Add widget areas.
	array_walk(
		$widget_areas['add'],
		function ( $widget_area ) {
			genesis_register_widget_area( $widget_area );
		}
	);

	// Remove widget areas.
	array_walk(
		$widget_areas['remove'],
		function ( $id ) {
			unregister_sidebar( $id );
		}
	);

	/**
	 * Remove default widget area content if sidebar is not registered or if a no-sidebar layout.
	 * Unregistering isn't enough in some scenarios where an existing site already has content/widgets in the sidebar.
	 */
	if ( in_array( 'sidebar', $widget_areas['remove'] ) || in_array( genesis_site_layout(), [ 'wide-content', 'standard-content', 'narrow-content' ] ) ) {
		remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
	}
	if ( in_array( 'sidebar-alt', $widget_areas['remove'] ) ) {
		remove_action( 'genesis_sidebar_alt', 'genesis_do_sidebar_alt' );
	}

}
