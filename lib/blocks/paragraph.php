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

add_action( 'init', 'mai_register_paragraph_styles' );
/**
 * Add paragraph custom styles.
 *
 * @since 0.1.0
 * @since 2.6.0 Added heading block style.
 *
 * @return void
 */
function mai_register_paragraph_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	register_block_style(
		'core/paragraph',
		[
			'name'  => 'heading',
			'label' => __( 'Heading', 'mai-engine' ),
		]
	);

	register_block_style(
		'core/paragraph',
		[
			'name'  => 'subheading',
			'label' => __( 'Subheading', 'mai-engine' ),
		]
	);

	// Check if alt font is enabled.
	$altfont = mai_get_option( 'altfont-enabled', false );

	// If we have an alt font, add new style.
	if ( $altfont ) {
		register_block_style(
			'core/paragraph',
			[
				'name'  => 'altfont',
				'label' => __( 'Alternate', 'mai-engine' ),
			]
		);
	}
}

add_filter( 'render_block', 'mai_render_paragraph_block', 10, 2 );
/**
 * Removes empty paragraph block markup.
 * Removes altfont class if alt font family and weight are not set.
 * Adds margin utility classes for content alignment settings.
 *
 * For some reason, `' <p></p> ' === $block_content` doesn't work, so
 * instead we have to count the number of characters in the string.
 *
 * @since 2.0.1
 *
 * @param string $block_content Block HTML markup.
 * @param array  $block         Block data.
 *
 * @return string
 */
function mai_render_paragraph_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( 'core/paragraph' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( 9 === strlen( $block_content ) ) {
		return '';
	}

	// Bail if no content alignment setting.
	if ( ! ( isset( $block['attrs']['contentAlign'] ) && $block['attrs']['contentAlign'] ) ) {
		return $block_content;
	}

	// Bail if center, this is default layout for backwards compatibility.
	if ( 'center' === $block['attrs']['contentAlign'] ) {
		return $block_content;
	}

	$align = $block['attrs']['contentAlign'];

	switch ( $align ) {
		case 'start':
			$side = is_rtl() ? 'right' : 'left';
			break;
		case 'end':
			$side = is_rtl() ? 'left' : 'right';
		break;
		default:
			$side = '';
	}

	if ( ! $side ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Without a tag there is nothing to add the class to, so the input comes back untouched.
	if ( $tags->next_tag() ) {
		$tags->add_class( sprintf( 'has-no-margin-%s', $side ) );
		$block_content = $tags->get_updated_html();
	}

	return $block_content;
}
