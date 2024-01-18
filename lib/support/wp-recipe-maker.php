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

add_filter( 'do_shortcode_tag', 'mai_recipe_maker_button_shortcodes', 10, 2 );
/**
 * Adds button classes to WP Recipe Maker shortcodes.
 * Removes inline styles.
 *
 * @since 2.31.4
 *
 * @param string $output The output from the shortcode.
 * @param string $tag    The name of the shortcode.
 *
 * @return string The modified output.
 */
function mai_recipe_maker_button_shortcodes( $output, $tag ) {
	if ( ! $output ) {
		return $output;
	}

	// Bail if not a shortcode we want.
	if ( ! in_array( $tag, [ 'wprm-recipe-jump', 'wprm-recipe-print', 'wprm-recipe-pin' ] ) ) {
		return $output;
	}

	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $output );

	// Loop through all buttons.
	while ( $tags->next_tag( [ 'tag_name' => 'a' ] ) ) {
		// Set start.
		$tags->set_bookmark( 'start' );

		// Remove styles and force button color. Chic template CSS was overriding this.
		$tags->set_attribute( 'style', 'color:var(--button-color);' );

		// Add new classes.
		$tags->add_class( 'button' );
		$tags->add_class( 'button-secondary' );

		// Add button-small class to inline (snippet) buttons.
		if ( $tags->has_class( 'wprm-recipe-link-inline-button' ) ) {
			$tags->add_class( 'button-small' );
		}

		// Remove classes.
		$tags->remove_class( 'wprm-block-text-normal' );
		$tags->remove_class( 'wprm-color-accent' );
		$tags->remove_class( 'wprm-recipe-link' );
		$tags->remove_class( 'wprm-recipe-link-inline-button' );
	}

	// Reset to start.
	$tags->seek( 'start' );

	// Loop through svg paths.
	while ( $tags->next_tag() ) {
		$fill = $tags->get_attribute( 'fill' );

		// Skip if no fill attribute.
		if ( ! $fill || 'none' === $fill ) {
			continue;
		}

		$tags->set_attribute( 'fill', 'currentColor' );
	}

	return $tags->get_updated_html();
}
