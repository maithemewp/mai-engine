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

// Disable Genesis Title Toggle setting.
add_filter( 'genesis_title_toggle_enabled', '__return_false' );

add_filter( 'wp_link_pages_link', 'mai_entry_pagination_button_class', 10, 2 );
/**
 * Adds button classes to entry pagination links.
 *
 * @since 2.11.0
 *
 * @param string $link The page number HTML output.
 * @param int    $i    Page number for paginated posts' page links.
 *
 * @return string
 */
function mai_entry_pagination_button_class( $link, $i ) {
	$dom = mai_get_dom_document( $link );
	/**
	 * The link element.
	 *
	 * @var DOMElement $first
	 */
	$first = mai_get_dom_first_child( $dom );

	if ( $first ) {
		$class = $first->getAttribute( 'class' );
		$new   = 'button';

		if ( ! mai_has_string( 'current', $class ) ) {
			$new .= ' button-secondary';
		}

		$new  .= ' button-small';
		$class = mai_add_classes( $new, $class );
		$first->setAttribute( 'class', $class );
		$link = $dom->saveHTML();
	}

	return $link;
}
