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

add_filter( 'get_search_form', 'mai_search_form_submit_icon' );
/**
 * Add search form button icon to all search forms.
 *
 * @since 0.1.0
 *
 * @param string $form The form markup.
 *
 * @return string
 */
function mai_search_form_submit_icon( $form ) {
	$icon = mai_get_svg_icon_url( 'search', 'regular' );
	$form = str_replace( '<form class="search-form"', sprintf( '<form style="--background-image:url(%s);" class="search-form"', $icon ), $form );
	$form = str_replace( 'search-form-submit', 'button-secondary search-form-submit search-form-submit-icon', $form );

	return $form;
}
