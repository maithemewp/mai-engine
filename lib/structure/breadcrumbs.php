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

// Disables Genesis Hide Breadcrumbs option.
add_filter( 'genesis_breadcrumbs_toggle_enabled', '__return_false' );

// Remove default Genesis breadcrumb output.
remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );

add_action( 'genesis_before_content_sidebar_wrap', 'mai_do_breadcrumbs', 12 );
/**
 * Displays breadcrumbs if not hidden.
 * Genesis already supports Breadcrumb NavXT and Yoast, but we add support for others here.
 *
 * @since 2.12.0
 *
 * @return void
 */
function mai_do_breadcrumbs() {
	if ( mai_is_element_hidden( 'breadcrumbs' ) ) {
		return;
	}

	$rank = function_exists( 'rank_math_the_breadcrumbs' );
	$aio  = function_exists( 'aioseo_breadcrumbs' );

	if ( $rank || $aio ) {
		// Conditions taken from `genesis_do_breadcrumbs()`.
		$genesis_breadcrumbs_hidden = apply_filters( 'genesis_do_breadcrumbs', genesis_breadcrumbs_hidden_on_current_page() );

		if ( $genesis_breadcrumbs_hidden ) {
			return;
		}

		if ( genesis_breadcrumbs_disabled_on_current_page() ) {
			return;
		}

		if ( $rank ) {
			rank_math_the_breadcrumbs();
		} elseif ( $aio ) {
			ob_start();
			aioseo_breadcrumbs();
			$breadcrumbs = ob_get_clean();

			if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
				$tags = new WP_HTML_Tag_Processor( $breadcrumbs );

				while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'aioseo-breadcrumbs' ] ) ) {
					$tags->add_class( 'breadcrumb' );
					break;
				}

				$breadcrumbs = $tags->get_updated_html();
			}

			echo $breadcrumbs;
		}
	} else {
		genesis_do_breadcrumbs();
	}
}
