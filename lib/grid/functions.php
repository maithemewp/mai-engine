<?php

/**
 * // Loop.
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/loops.php#L64
 * // Post.
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/post.php
 */

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
