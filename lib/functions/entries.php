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
 * Render the entries opening markup.
 *
 * @since 0.1.0
 *
 * @param array $args Entries open args.
 *
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/loops.php#L64
 * @link  https://github.com/studiopress/genesis/blob/master/lib/structure/post.php
 *
 * @return void
 */
function mai_do_entries_open( $args ) {
	// Start the attributes.
	$attributes = [
		'class' => mai_add_classes( 'entries', isset( $args['class'] ) ? $args['class'] : '' ),
		'style' => '',
	];

	// Context.
	$context             = 'block' === $args['context'] ? 'grid' : $args['context'];
	$attributes['class'] = mai_add_classes( 'entries-' . $context, $attributes['class'] );

	// Boxed.
	if ( $args['boxed'] && ! ( in_array( 'image', $args['show'], true ) && ( 'background' === $args['image_position'] ) ) ) {
		$attributes['class'] .= ' has-boxed';
	}

	// Spacing. Only for grid blocks, so check isset.
	if ( mai_isset( $args, 'remove_spacing', false ) ) {
		$attributes['style'] .= '--entries-margin-bottom:0;';
	}

	// Title size.
	if ( $args['title_size'] ) {
		$attributes['style'] .= sprintf( '--entry-title-font-size:var(--font-size-%s);', $args['title_size'] );
	}

	// Image position.
	if ( in_array( 'image', $args['show'], true ) && $args['image_position'] ) {
		$attributes['class'] .= ' has-image-' . $args['image_position'];

		// Aspect ratio.
		if ( in_array( $args['image_position'], [ 'background', 'left-full', 'right-full' ], true ) ) {
			$aspect_ratio         = mai_has_image_orientiation( $args['image_orientation'] ) ? mai_get_aspect_ratio_from_orientation( $args['image_orientation'] ) : mai_get_image_aspect_ratio( $args['image_size'] );
			$attributes['style'] .= sprintf( '--aspect-ratio:%s;', $aspect_ratio );
		}

		if ( 'background' !== $args['image_position'] ) {

			$left_right = mai_has_string( [ 'left', 'right' ], $args['image_position'] );

			// Image width.
			if ( 'custom' === $args['image_orientation'] ) {
				if ( isset( $args['class'] ) && ( mai_has_string( 'alignfull', $args['class'] ) || mai_has_string( 'alignwide', $args['class'] ) ) ) {
					$image_width = mai_get_image_width( $args['image_size'] );
					$image_width = $image_width ? mai_get_unit_value( $image_width ) : 'unset';
				} else {
					$image_sizes = mai_get_available_image_sizes();
					$image_size  = isset( $image_sizes[ $args['image_size'] ] ) ? $image_sizes[ $args['image_size'] ] : $image_sizes['landscape-md'];
					$image_width = $image_size['width'] . 'px';
				}

				$attributes['style'] .= sprintf( '--entry-image-link-max-width:%s;', $image_width );

			} elseif ( $left_right ) {

				switch ( $args['image_width'] ) {
					case 'half':
						$attributes['style'] .= sprintf( '--entry-image-link-max-width:%s;', '50%' );
						break;
					case 'third':
						$attributes['style'] .= sprintf( '--entry-image-link-max-width:%s;', '33.33333333%' );
						break;
					case 'fourth':
						$attributes['style'] .= sprintf( '--entry-image-link-max-width:%s;', '25%' );
						break;
				}
			}

			// Image alternating.
			if ( $left_right && $args['image_alternate'] ) {
				if ( mai_has_string( 'left', $args['image_position'] ) ) {
					$attributes['class'] .= ' has-image-odd-first';
				} elseif ( mai_has_string( 'right', $args['image_position'] ) ) {
					$attributes['class'] .= ' has-image-even-first';
				}
			}
		}
	}

	// Get the columns breakpoint array.
	$columns = mai_get_breakpoint_columns( $args );

	$attributes['style'] .= sprintf( '--columns-lg:%s;', $columns['lg'] );
	$attributes['style'] .= sprintf( '--columns-md:%s;', $columns['md'] );
	$attributes['style'] .= sprintf( '--columns-sm:%s;', $columns['sm'] );
	$attributes['style'] .= sprintf( '--columns-xs:%s;', $columns['xs'] );

	// Get column gap, deprecating old text field values.
	if ( $args['column_gap'] ) {
		$column_gap = mai_is_valid_size( $args['column_gap'] ) ? $args['column_gap'] : 'lg';
		$column_gap = sprintf( 'var(--spacing-%s)', $column_gap );
	} else {
		$column_gap = '0px'; // px needed for calculations.
	}

	// Get row gap, deprecating old text field values.
	if ( $args['row_gap'] ) {
		$row_gap = mai_is_valid_size( $args['row_gap'] ) ? $args['row_gap'] : 'lg';
		$row_gap = sprintf( 'var(--spacing-%s)', $row_gap );
	} else {
		$row_gap = '0px'; // px needed for calculations.
	}

	$attributes['style'] .= sprintf( '--column-gap:%s;', $column_gap );
	$attributes['style'] .= sprintf( '--row-gap:%s;', $row_gap );
	$attributes['style'] .= sprintf( '--align-text:%s;', mai_get_align_text( $args['align_text'] ) );

	if ( isset( $args['align_columns'] ) && $args['align_columns'] ) {
		$attributes['style'] .= sprintf( '--align-columns:%s;', mai_get_flex_align( $args['align_columns'] ) );
	}

	if ( isset( $args['align_columns_vertical'] ) && $args['align_columns_vertical'] ) {
		$attributes['style'] .= sprintf( '--align-columns-vertical:%s;', mai_get_flex_align( $args['align_columns_vertical'] ) );
	}

	if ( isset( $args['align_text_vertical'] ) && mai_has_string( [
		'left',
		'right',
		'background',
	], $args['image_position'] ) ) {
		$attributes['style'] .= sprintf( '--align-text-vertical:%s;', mai_get_align_text( $args['align_text_vertical'] ) );
	}

	if ( isset( $args['border_radius'] ) && '' !== $args['border_radius'] && ( ( 'background' === $args['image_position'] ) || $args['boxed'] ) ) {
		$attributes['style'] .= sprintf( '--border-radius:%s;', mai_get_unit_value( $args['border_radius'] ) );
	}

	$attributes['style'] .= sprintf( '--entry-meta-text-align:%s;', mai_get_align_text( $args['align_text'] ) );

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

	$wrap_class = 'entries-wrap';

	// Add image stack class to entries-wrap so it intercepts the inline variable so we don't need overly specific CSS.
	if ( $args['image_stack'] && in_array( 'image', $args['show'], true ) && $args['image_position'] && mai_has_string( [
			'left',
			'right',
		], $args['image_position'] ) ) {
		$wrap_class .= ' has-image-stack';
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'entries-wrap',
			'echo'    => true,
			'atts'    => [
				'class' => $wrap_class,
			],
			'params'  => [
				'args' => $args,
			],
		]
	);
}

/**
 * Render the entries closing markup.
 *
 * @since 0.1.0
 *
 * @param array $args Entries close args.
 *
 * @return void
 */
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
 * Render a grid entry.
 *
 * @since 0.1.0
 *
 * @param WP_Post|WP_Term $entry The (post, term, user) entry object.
 * @param array           $args  The object to get the entry.
 *
 * @return  void
 */
function mai_do_entry( $entry, $args = [] ) {
	$entry = new Mai_Entry( $entry, $args );
	$entry->render();
}
