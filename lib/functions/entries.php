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
	// Start the atts.
	$atts = [
		'class'   => mai_add_classes( 'entries', isset( $args['class'] ) ? $args['class']: '' ),
		'style'   => '',
	];

	// Context.
	$context       = 'block' === $args['context'] ? 'grid' : $args['context'];
	$atts['class'] = mai_add_classes( 'entries-' . $context, $atts['class'] );

	// Boxed.
	if ( $args['boxed'] && ! ( in_array( 'image', $args['show'], true ) && ( 'background' === $args['image_position'] ) ) ) {
		$atts['class'] .= ' has-boxed';
	}

	// Title size.
	if ( $args['title_size'] ) {
		$atts['style'] .= sprintf( '--entry-title-font-size:var(--font-size-%s);', $args['title_size'] );
	}

	// Image position.
	if ( in_array( 'image', $args['show'], true ) && $args['image_position'] ) {
		$atts['class'] .= ' has-image-' . $args['image_position'];

		// Aspect ratio.
		if ( in_array( $args['image_position'], [ 'background', 'left-full', 'right-full' ], true ) ) {
			$aspect_ratio   = mai_has_image_orientiation( $args['image_orientation'] ) ? mai_get_aspect_ratio_from_orientation( $args['image_orientation'] ) : mai_get_image_aspect_ratio( $args['image_size'] );
			$atts['style'] .= sprintf( '--aspect-ratio:%s;', $aspect_ratio );
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

				$atts['style'] .= sprintf( '--entry-image-link-max-width:%s;', $image_width );

			} elseif ( $left_right ) {

				switch ( $args['image_width'] ) {
					case 'half':
						$atts['style'] .= sprintf( '--entry-image-link-max-width:%s;', '50%' );
						break;
					case 'third':
						$atts['style'] .= sprintf( '--entry-image-link-max-width:%s;', '33.33333333%' );
						break;
					case 'fourth':
						$atts['style'] .= sprintf( '--entry-image-link-max-width:%s;', '25%' );
						break;
				}
			}

			// Image alternating.
			if ( $left_right && $args['image_alternate'] ) {
				if ( mai_has_string( 'left', $args['image_position'] ) ) {
					$atts['class'] .= ' has-image-odd-first';
				} elseif ( mai_has_string( 'right', $args['image_position'] ) ) {
					$atts['class'] .= ' has-image-even-first';
				}
			}
		}
	}

	// Check isset because customizer archives don't have margin settings.
	if ( isset( $args['margin_top'] ) && $args['margin_top'] ) {
		$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-top', $args['margin_top'] ), $atts['class'] );
	}

	// Check isset because customizer archives don't have margin settings.
	if ( isset( $args['margin_bottom'] ) && $args['margin_bottom'] ) {
		$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-bottom', $args['margin_bottom'] ), $atts['class'] );
	}

	$atts['style'] .= sprintf( '--align-text:%s;', mai_get_align_text( $args['align_text'] ) );

	if ( isset( $args['align_text_vertical'] ) && mai_has_string( [
		'left',
		'right',
		'background',
	], $args['image_position'] ) ) {
		$atts['style'] .= sprintf( '--align-text-vertical:%s;', mai_get_align_text( $args['align_text_vertical'] ) );
	}

	if ( isset( $args['border_radius'] ) && '' !== $args['border_radius'] && ( ( 'background' === $args['image_position'] ) || $args['boxed'] ) ) {
		$atts['style'] .= sprintf( '--border-radius:%s;', mai_get_unit_value( $args['border_radius'] ) );
	}

	$atts['style'] .= sprintf( '--entry-meta-text-align:%s;', mai_get_align_text( $args['align_text'] ) );

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'entries',
			'echo'    => true,
			'atts'    => $atts,
			'params'  => [
				'args' => $args,
			],
		]
	);

	$wrap_atts = [
		'class' => 'entries-wrap',
	];

	$wrap_atts = mai_get_columns_atts( $wrap_atts, $args );

	// Add image stack class to entries-wrap so it intercepts the inline variable so we don't need overly specific CSS.
	if ( $args['image_stack'] && in_array( 'image', $args['show'], true ) && $args['image_position'] && mai_has_string( [
			'left',
			'right',
		], $args['image_position'] ) ) {

		$wrap_atts['class'] = mai_add_classes( 'has-image-stack', $wrap_atts['class'] );
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'entries-wrap',
			'echo'    => true,
			'atts'    => $wrap_atts,
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
