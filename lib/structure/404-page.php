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

add_action( 'genesis_before', 'mai_do_404_page' );
/**
 * Renders the 404-page template part on 404 page.
 *
 * @since 2.10.0
 *
 * @return void
 */
function mai_do_404_page() {
	if ( ! is_404() ) {
		return;
	}

	if ( ! mai_has_template_part( '404-page' ) ) {
		return;
	}

	// Remove title.
	add_filter( 'genesis_markup_entry-title', '__return_empty_string' );

	// Swap content.
	add_filter( 'genesis_markup_entry-content', function( $content, $args ) {
		ob_start();
		mai_render_template_part( '404-page' );
		return ob_get_clean();
	}, 10, 2 );
}
