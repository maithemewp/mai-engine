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

add_filter( 'facetwp_shortcode_html', 'mai_facetwp_page_wrap', 10, 2 );
/**
 * Add the wrap class to facetwp pager element.
 *
 * @since 0.3.11
 *
 * @param string $html The shortcode HTML.
 * @param array  $atts The shortcode attributes.
 *
 * @return string
 */
function mai_facetwp_page_wrap( $html, $atts ) {
	if ( ! ( isset( $atts['pager'] ) && $atts['pager'] ) ) {
		return $html;
	}

	$html = str_replace( 'facetwp-pager', 'wrap facetwp-pager', $html );

	return $html;
}

add_filter( 'facetwp_pager_html', 'mai_facetwp_genesis_pager', 10, 2 );
/**
 * Style pagination to look like Genesis.
 *
 * @since 0.2.0
 *
 * @link https://gist.github.com/JiveDig/b9810ba4c322d7757993159ed9ccb61f
 * @link https://halfelf.org/2017/facetwp-genesis-pagination/
 * @link https://gist.github.com/mgibbs189/69176ef41fa4e26d1419
 *
 * @param string $output The pager HTML.
 * @param array  $params The current query args.
 *
 * @return string
 */
function mai_facetwp_genesis_pager( $output, $params ) {
	$output      = '<ul>';
	$page        = (int) $params['page'];
	$total_pages = (int) $params['total_pages'];
	$go_to_page  = __( 'Go to page', 'mai-engine' );

	// Only show pagination when more than one page.
	if ( $total_pages > 1 ) {

		if ( 1 < $page ) {
			$output .= sprintf( '<li class="pagination-previous"><a class="facetwp-page pagination-link button button-secondary button-small" data-page="%s">%s</a>&nbsp;</li>', ( $page - 1 ), esc_html__( 'Previous', 'mai-engine' ) );
		}
		if ( 3 < $page ) {
			$output .= '<li><a class="facetwp-page pagination-link button button-secondary button-small first-page" data-page="1"><span class="screen-reader-text">' . $go_to_page . '</span> 1</a>&nbsp;</li>';
			$output .= '<li class="pagination-omission">&hellip;</li>';
		}
		for ( $i = 2; $i > 0; $i-- ) {
			if ( 0 < ( $page - $i ) ) {
				$output .= '<li><a class="facetwp-page pagination-link button button-secondary button-small" data-page="' . ( $page - $i ) . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . ( $page - $i ) . '</a>&nbsp;</li>';
			}
		}

		// Current page.
		$output .= '<li class="active"><a class="facetwp-page pagination-link button button-small" aria-label="Current page" data-page="' . $page . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . $page . '</a>&nbsp;</li>';

		for ( $i = 1; $i <= 2; $i++ ) {
			if ( $total_pages >= ( $page + $i ) ) {
				$output .= '<li><a class="facetwp-page pagination-link button button-secondary button-small" data-page="' . ( $page + $i ) . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . ( $page + $i ) . '</a>&nbsp;</li>';
			}
		}
		if ( $total_pages > ( $page + 2 ) ) {
			$output .= '<li class="pagination-omission">&hellip;&nbsp;</li>';
			$output .= '<li><a class="facetwp-page pagination-link button button-secondary button-small last-page" data-page="' . $total_pages . '"><span class="screen-reader-text">' . $go_to_page . '</span> ' . $total_pages . '</a>&nbsp;</li>';
		}
		if ( $page < $total_pages ) {
			$output .= sprintf( '<li class="pagination-next"><a class="facetwp-page pagination-link button button-secondary button-small" data-page="%s">%s</a>&nbsp;</li>', ( $page + 1 ), esc_html__( 'Next', 'mai-engine' ) );
		}
	}

	$output .= '<style type="text/css">.archive-pagination .wrap.facetwp-pager + .wrap { display:none; } .facetwp-pager .facetwp-page{ display:inline-block; margin:0; padding:var(--button-padding); }</style>';

	$output .= '</ul>';

	return $output;
}

add_filter( 'genesis_markup_archive-pagination_content', 'mai_facetwp_archive_pagination', 20, 2 );
/**
 * Add facetwp pager before genesis pagination.
 * This will only display if there are facets on the page.
 *
 * @since 0.2.0
 *
 * @param string $content The existing content.
 * @param array  $args    The genesis_markup() element args.
 *
 * @return string
 */
function mai_facetwp_archive_pagination( $content, $args ) {
	if ( ! mai_is_type_archive() ) {
		return $content;
	}

	if ( ! $args['open'] ) {
		return $content;
	}

	return facetwp_display( 'pager' ) . $content;
}
