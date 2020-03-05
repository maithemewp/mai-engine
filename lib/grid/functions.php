<?php

// TEMPORARY TILL I'M USING MAI ENGINE.

function mai_temp_get_image_sizes() {
	$breakpoints = mai_temp_get_breakpoints();
	return [
		'sm' => $breakpoints['xs'], // 384px.
		'md' => $breakpoints['md'], // 768px.
		'lg' => $breakpoints['xl'], // 1152px.
	];
}

function mai_temp_get_breakpoints() {

	// "screen-xs": "400px", // mobile portrait
	// "screen-sm": "600px", // mobile landscape
	// "screen-md": "800px", // tablet portrait
	// "screen-lg": "1000px", // tablet landscape
	// "screen-xl": "1200px", // desktop

	return [
		'xs' => '',
		'sm' => 512,
		'md' => 768,
		'lg' => 1024,
		'xl' => 1152,
	];
}

// add_action( 'init', function() {

	$image_sizes = mai_temp_get_image_sizes();

	$sizes = [
		'full'         => mai_apply_aspect_ratio( 1600, '16:9' ),
		'landscape-lg' => mai_apply_aspect_ratio( $image_sizes['lg'], '4:3' ),
		'landscape-md' => mai_apply_aspect_ratio( $image_sizes['md'], '4:3' ),
		'landscape-sm' => mai_apply_aspect_ratio( $image_sizes['sm'], '4:3' ),
		'portrait-lg'  => mai_apply_aspect_ratio( $image_sizes['lg'], '3:4' ),
		'portrait-md'  => mai_apply_aspect_ratio( $image_sizes['md'], '3:4' ),
		'portrait-sm'  => mai_apply_aspect_ratio( $image_sizes['sm'], '3:4' ),
		'square-lg'    => mai_apply_aspect_ratio( $image_sizes['lg'], '1:1' ),
		'square-md'    => mai_apply_aspect_ratio( $image_sizes['md'], '1:1' ),
		'square-sm'    => mai_apply_aspect_ratio( $image_sizes['sm'], '1:1' ),
		'tiny'         => mai_apply_aspect_ratio( 80, '1:1' ),
	];

	foreach( $sizes as $name => $values ) {
		add_image_size( $name, $values[0], $values[1], $values[2] );
	}

// });

function mai_apply_aspect_ratio( $width = 896, $ratio = '16:9' ) {
	$ratio       = explode( ':', $ratio );
	$x           = $ratio[0];
	$y           = $ratio[1];
	$height      = (int) $width / $x * $y;
	return [ $width, $height, true ];
}

//  END TEMPORARY.

/**
 * // Loop.
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/loops.php#L64
 * // Post.
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/post.php
 */

// do_action( 'genesis_entry_header' );
// do_action( 'genesis_before_entry_content' );
// do_action( 'genesis_entry_content' );
// do_action( 'genesis_after_entry_content' );
// do_action( 'genesis_entry_footer' );

/**
 * his is in v1 already but we need it.
 * TODO: Remove _new.
 */
function mai_is_content_archive_new() {
	return (bool) ( is_home() || is_archive() || is_tax() || is_search() || is_date() || is_author() );
}

/**
 * Enqueue an asset.
 *
 * @param   string  $handle        The asset handle.
 * @param   string  $name          The asset name.
 * @param   string  $type          The type. Typically js or css.
 * @param   array   $dependencies  Script dependencies.
 *
 * @return  void
 */
function mai_enqueue_asset( $handle, $name, $type, $dependencies = [] ) {
	// TODO: These should get cleaned up once in the engine.
	// TODO: Static variables?
	$base_url = trailingslashit( MAI_GRID_PLUGIN_URL ) . 'assets/' . $type;
	$base_dir = trailingslashit( MAI_GRID_PLUGIN_DIR ) . 'assets/' . $type;
	$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '': '.min';
	$suffix   = ''; // Temp fix.
	$url      = sprintf( '%s/%s%s.%s', $base_url, $name, $suffix, $type );
	$version  = MAI_GRID_VERSION . '.' . date ( 'njYHi', filemtime( "{$base_dir}/{$name}{$suffix}.{$type}" ) );
	switch ( $type ) {
		case 'css':
			wp_enqueue_style( $handle, $url, $dependencies, $version );
		break;
		case 'js':
			wp_enqueue_script( $handle, $url, $dependencies, $version, true );
		break;
	}
}

function mai_do_entries_open( $args ) {

	// Start the attributes.
	$attributes = array(
		'class' => 'entries',
		'style' => '',
	);

	// Boxed.
	if ( $args['boxed'] ) {
		$attributes['class'] .= ' has-boxed';
	}

	// Image position.
	if ( in_array( 'image', $args['show'] ) && $args['image_position'] ) {
		$attributes['class'] .= ' has-image-' . $args['image_position'];
		if ( 'background' === $args['image_position'] ) {
			// TODO: This needs to use the engine config to get available image orientations.
			switch ( $args['image_orientation'] ) {
				case 'landscape':
				case 'portrait':
				case 'square':
					$image_size = sprintf( '%s-md', $args['image_orientation'] );
				break;
				default:
					$image_size = $args['image_size'];
			}
			$attributes['style'] .= sprintf( '--aspect-ratio:%s;', mai_get_aspect_ratio( $args['image_size'] ) );
		}
	}

	// Get the columns breakpoint array.
	$columns = mai_get_breakpoint_columns( $args );

	// Global styles.
	$attributes['style'] .= sprintf( '--columns-lg:%s;', $columns['lg'] );
	$attributes['style'] .= sprintf( '--columns-md:%s;', $columns['md'] );
	$attributes['style'] .= sprintf( '--columns-sm:%s;', $columns['sm'] );
	$attributes['style'] .= sprintf( '--columns-xs:%s;', $columns['xs'] );
	$attributes['style'] .= sprintf( '--column-gap:%s;', mai_get_gap( $args['column_gap'] ) );
	$attributes['style'] .= sprintf( '--row-gap:%s;', mai_get_gap( $args['row_gap'] ) );
	$attributes['style'] .= sprintf( '--align-columns:%s;', ! empty( $args['align_columns'] ) ? mai_get_flex_align( $args['align_columns'] ) : 'unset' );
	$attributes['style'] .= sprintf( '--align-columns-vertical:%s;', ! empty( $args['align_columns_vertical'] ) ? mai_get_flex_align( $args['align_columns_vertical'] ) : 'unset' );
	$attributes['style'] .= sprintf( '--align-text:%s;', mai_get_align_text( $args['align_text'] ) );
	$attributes['style'] .= sprintf( '--align-text-vertical:%s;', mai_get_align_text( $args['align_text_vertical'] ) );

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'entries',
			'echo'    => true,
			'atts'    => $attributes,
			'params'  => [
				'args' => $args,
			],
		]
	);

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'entries-wrap',
			'echo'    => true,
			'params'  => [
				'args' => $args,
			],
		]
	);

}

function mai_do_entries_close( $args ) {

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'entries-wrap',
			'echo'    => true,
			'params'  => [
				'args' => $args,
			],
		]
	);

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'entries',
			'echo'    => true,
			'params'  => [
				'args' => $args,
			],
		]
	);

}

/**
 * Echo a grid entry.
 *
 * @param   object  The (post, term, user) entry object.
 * @param   object  The object to get the entry.
 *
 * @return  string
 */
function mai_do_entry( $entry, $args ) {
	$entry = new Mai_Entry( $entry, $args );
	$entry->render();
}

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
 * Get the gap value.
 * If only a number value, force to pixels.
 */
function mai_get_gap( $value ) {
	if ( empty( $value ) || is_numeric( $value ) ) {
		return sprintf( '%spx', intval( $value ) );
	}
	return trim( $value );
}

/**
 * Get the columns at different breakpoints.
 * We use strings because the clear option is just an empty string.
 */
function mai_get_breakpoint_columns( $args ) {

	$columns = [
		'lg' => (int) $args['columns'],
	];
	if ( $args['columns_responsive'] ) {
		$columns['md'] = (int) $args['columns_md'];
		$columns['sm'] = (int) $args['columns_sm'];
		$columns['xs'] = (int) $args['columns_xs'];
	} else {
		switch ( (int) $args['columns'] ) {
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
 * @param   string  $content The content to limit.
 * @param   int     $limit   The maximum number of characters to return.
 *
 * @return  string  Limited content.
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

function mai_get_aspect_ratio( $image_size ) {
	$all_sizes = mai_get_available_image_sizes_new();
	$sizes     = isset( $all_sizes[ $image_size ] ) ? $all_sizes[ $image_size ] : false;
	// TODO: Get default landscape aspect ratio.
	return $sizes ? sprintf( '%s/%s', $sizes['height'], $sizes['width'] ) : '4/3';
}

/**
 * TODO: Rename this, removing the _new. It's in Mai Theme v1 so needed to rename for now.
 *
 * Utility method to get a combined list of default and custom registered image sizes.
 * Originally taken from CMB2. Static variable added here.
 *
 * We can't use `genesis_get_image_sizes()` because we need it earlier than Genesis is loaded for Kirki.
 *
 * @link    http://core.trac.wordpress.org/ticket/18947
 * @global  array  $_wp_additional_image_sizes.
 * @return  array  The image sizes.
 */
function mai_get_available_image_sizes_new() {
	// Cache.
	static $image_sizes = array();
	if ( ! empty( $image_sizes ) ) {
		return $image_sizes;
	}
	// Get image sizes.
	global $_wp_additional_image_sizes;
	$default_image_sizes = array( 'thumbnail', 'medium', 'large' );
	foreach ( $default_image_sizes as $size ) {
		$image_sizes[ $size ] = array(
			'height' => intval( get_option( "{$size}_size_h" ) ),
			'width'  => intval( get_option( "{$size}_size_w" ) ),
			'crop'   => get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false,
		);
	}
	if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
		$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
	}
	return $image_sizes;
}

// function mai_is_post_template( $args ) {
// 	return ( 'post' === $args['type'] ) && in_array( $args['context'], [ 'singular', 'archive' ] );
// }
