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

add_filter( 'genesis_markup_search-form-submit_open', 'mai_search_form_submit_open' );
/**
 * Adds search icon to submit button.
 * Changes the search form input markup to button.
 *
 * @since 1.0.0
 *
 * @param string $open_html Opening HTML.
 *
 * @return mixed
 */
function mai_search_form_submit_open( $open_html ) {
	if ( $open_html ) {
		$open_html .= mai_get_svg_icon(
			'search',
			'regular',
			[
				'class' => 'search-form-submit-icon',
			]
		);
	}

	$open_html = str_replace(
		[ 'input', 'search-form-submit"' ],
		[ 'button', 'search-form-submit button-secondary"' ],
		$open_html
	);

	return $open_html;
}

add_filter( 'genesis_markup_search-form-submit_close', 'mai_search_form_submit_close' );
/**
 * Adds closing button markup.
 *
 * @since 1.0.0
 *
 * @param string $close_html Closing HTML.
 *
 * @return mixed
 */
function mai_search_form_submit_close( $close_html ) {
	return '</button>';
}
