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

add_action( 'after_setup_theme', 'mai_add_inline_editor_styles' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_inline_editor_styles() {
	add_editor_style( mai_get_url() );
}

add_filter( 'pre_http_request', 'mai_pre_http_request_editor_styles', 10, 3 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array  $response    Whether to preempt an HTTP request's return value. Default false.
 * @param array  $parsed_args HTTP request arguments.
 * @param string $url         The request URL.
 *
 * @return array
 */
function mai_pre_http_request_editor_styles( $response, $parsed_args, $url ) {
	if ( mai_get_url() === $url ) {
		$colors = mai_get_colors();
		$css    = 'body {';

		foreach ( $colors as $name => $hex ) {
			$css .= "--color-$name: $hex;";
		}

		$css .= '}';

		$response = [
			'body'     => $css,
			'headers'  => new Requests_Utility_CaseInsensitiveDictionary(),
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	return $response;
}
