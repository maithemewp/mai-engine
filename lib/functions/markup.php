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

add_filter( 'language_attributes', 'mai_admin_bar_showing' );
/**
 * Add class to html element for styling.
 *
 * @since 0.1.0
 *
 * @param string $output Language attributes markup.
 *
 * @return string
 */
function mai_admin_bar_showing( $output ) {
	if ( is_admin_bar_showing() ) {
		$output .= ' class="admin-bar-showing"';
	}

	return $output;
}

add_filter( 'body_class', 'mai_body_classes' );
/**
 * Add additional classes to the body element.
 *
 * @since  0.1.0
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function mai_body_classes( $classes ) {

	// Remove unnecessary page template classes.
	$template  = get_page_template_slug();
	$basename  = basename( $template, '.php' );
	$directory = str_replace( [ '/', basename( $template ) ], '', $template );
	$classes   = array_diff(
		$classes,
		[
			'page-template',
			'page-template-' . $basename,
			'page-template-' . $directory,
			'page-template-' . $directory . $basename . '-php',
		]
	);

	// Add simple template name.
	if ( $basename ) {
		$classes[] = 'template-' . $basename;
	}

	// Add transparent header class.
	if ( current_theme_supports( 'transparent-header' ) && ( mai_is_page_header_active() || is_front_page() && is_active_sidebar( 'front-page-1' ) ) ) {
		$classes[] = 'has-transparent-header';
	}

	// Add sticky header class.
	if ( current_theme_supports( 'sticky-header' ) ) {
		$classes[] = 'has-sticky-header';
	}

	// Add single type class.
	if ( mai_is_type_single() ) {
		$classes[] = 'is-single';
	}

	// Add archive type class.
	if ( mai_is_type_archive() ) {
		$classes[] = 'is-archive';
	}

	// Add nav classes.
	if ( has_nav_menu( 'header-left' ) && has_nav_menu( 'header-right' ) ) {
		$classes[] = 'has-logo-center';
	}

	// Add no page header class.
	$classes[] = 'no-page-header';

	// Add front page 1 slider class.
	$classes[] = mai_sidebar_has_widget( 'front-page-1', 'seo_slider' ) ? 'has-home-slider' : '';

	// Add block classes.
	$classes[] = mai_has_cover_block() ? 'has-cover-block' : '';

	return $classes;
}

add_filter( 'genesis_attr_site-container', 'mai_back_to_top_anchor' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array $attr Element attributes.
 *
 * @return array
 */
function mai_back_to_top_anchor( $attr ) {
	$attr['id'] = 'top';

	return $attr;
}

/**
 * Convert cover block to inline image with custom srcset.
 * Changes inline styles to CSS custom properties for use in CSS.
 *
 * @param   string  $block_content  The existing block content.
 * @param   object  $block          The cover block object.
 *
 * @return  string|HTML  The modified block HTML.
 */
add_filter( 'render_block', 'mai_render_cover_block', 10, 2 );
function mai_render_cover_block( $block_content, $block ) {

	// Bail if not a cover block.
	if ( 'core/cover' !== $block['blockName'] ) {
		return $block_content;
	}

	// Get the image ID.
	$image_id = isset( $block['attrs']['id'] ) ? $block['attrs']['id'] : false;

	// Bail if no image ID.
	if ( ! $image_id ) {
		return $block_content;
	}

	// Convert inline styles to css properties.
	$block_content = str_replace( 'background-image', '--background-image', $block_content );
	$block_content = str_replace( 'background-position', '--object-position', $block_content );
	$block_content = mai_add_cover_block_image( $block_content, $image_id );

	return $block_content;
}

/**
 * Add cover block image as inline element,
 * instead of using a background-image inline style.
 * Adds custom srcset to the image element.
 *
 * TODO: Use <figure>?
 *
 * @param   string  $block_content  The existing block content.
 * @param   int     $image_id       The cover block image ID.
 *
 * @return  string|HTML  The modified block HTML.
 */
function mai_add_cover_block_image( $block_content, $image_id ) {

	// Create the new document.
	$dom = new DOMDocument;

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $block_content, 'HTML-ENTITIES', "UTF-8" ) );

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	// Start image atts.
	$atts = [
		'class'  => 'wp-cover-block__image',
		'sizes'  => '100vw',
		'srcset' => '',
	];

	// Build srcset array.
	$image_sizes = mai_get_available_image_sizes();
	$srcset = [];
	$sizes  = [
		'landscape-sm',
		'landscape-md',
		'landscape-lg',
		'cover',
	];
	foreach( $sizes as $size ) {
		if ( ! isset( $image_sizes[ $size ] ) ) {
			continue;
		}
		$url = wp_get_attachment_image_url( $image_id, $size );
		if ( ! $url ) {
			continue;
		}
		$srcset[] = sprintf( '%s %sw', $url, $image_sizes[ $size ]['width'] );
	}

	// Convert to string.
	$atts['srcset'] = implode( ',', $srcset );

	// Get the image HTML.
	$image_html = wp_get_attachment_image( $image_id, 'cover', false, $atts );

	if ( ! $image_html ) {
		return $block_content;
	}

	// Get cover blocks by class. Checks if class contains `wp-block-cover` but not as part of another class, like `wp-block-cover__inner-container`.
	$xpath        = new DOMXPath( $dom );
	$cover_blocks = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' wp-block-cover ')]" );

	// Bail if none.
	if ( ! $cover_blocks->length ) {
		return $block_content;
	}

	// Loop through, though we know it's one.
	foreach( $cover_blocks as $block ) {

		// Build the HTML node.
		$fragment = $dom->createDocumentFragment();
		$fragment->appendXml( $image_html );

		// Add it to the beginning.
		$block->insertBefore( $fragment, $block->firstChild );
	}

	// Save the new content.
	return $dom->saveHTML();
}
