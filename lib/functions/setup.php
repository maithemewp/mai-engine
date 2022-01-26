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

add_action( 'after_setup_theme', 'mai_setup', 5 );
/**
 * Theme setup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_setup() {

	// Get setup configs.
	$custom_functions  = mai_get_config( 'custom-functions' );
	$theme_support     = mai_get_config( 'theme-support' );
	$post_type_support = mai_get_config( 'post-type-support' );
	$image_sizes       = mai_get_config( 'image-sizes' );
	$page_layouts      = mai_get_config( 'page-layouts' );
	$widget_areas      = mai_get_config( 'widget-areas' );

	// Add theme textdomain.
	load_child_theme_textdomain( genesis_get_theme_handle(), mai_get_dir() . '/assets/lang' );

	$plugin_dir   = basename( mai_get_dir() );
	$active_theme = mai_get_active_theme();

	// Add editor styles (uri).
	add_editor_style( "../../plugins/$plugin_dir/assets/css/editor.min.css" );
	add_editor_style( "../../plugins/$plugin_dir/assets/css/themes/$active_theme.min.css" );

	// Add custom functionality.
	is_callable( $custom_functions ) ? $custom_functions() : null;

	// Add theme supports.
	array_walk(
		$theme_support['add'],
		function( $value, $key ) {
			is_int( $key ) ? add_theme_support( $value ) : add_theme_support( $key, $value );
		}
	);

	// Remove theme supports.
	array_walk(
		$theme_support['remove'],
		function( $name ) {
			remove_theme_support( $name );
		}
	);

	// Add post type supports.
	array_walk(
		$post_type_support['add'],
		function( $post_types, $feature ) {
			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, $feature );
			}
		}
	);

	// Remove post type supports.
	array_walk(
		$post_type_support['remove'],
		function( $feature, $post_type ) {
			remove_post_type_support( $post_type, $feature );
		}
	);

	// Add image sizes.
	array_walk(
		$image_sizes['add'],
		function( $args, $name ) {
			if ( is_array( $args ) ) {
				add_image_size( $name, $args[0], $args[1], $args[2] );

			} elseif ( is_string( $args ) && mai_has_string( ':', $args ) ) {
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
		function( $name ) {
			remove_image_size( $name );
		}
	);

	// Add page layouts.
	array_walk(
		$page_layouts['add'],
		function( $args ) {
			genesis_register_layout( $args['id'], $args );
		}
	);

	// Remove page layouts.
	array_walk(
		$page_layouts['remove'],
		function( $name ) {
			genesis_unregister_layout( $name );
		}
	);

	// Add widget areas.
	array_walk(
		$widget_areas['add'],
		function( $widget_area, $id ) {
			$widget_area['id'] = $id;
			genesis_register_widget_area( $widget_area );
		}
	);

	// Remove widget areas.
	array_walk(
		$widget_areas['remove'],
		function( $id ) {
			unregister_sidebar( $id );
		}
	);
}
