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

add_action( 'acf/init', 'mai_register_column_block' );
/**
 * Registers the columns blocks.
 *
 * @since 2.10.0
 * @since TBD Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_column_block() {
	register_block_type( __DIR__ . '/block.json',
		[
			'icon' => '<svg role="img" aria-hidden="true" focusable="false" style="display:block;" width="20" height="20" viewBox="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(0.75,0,0,0.780483,12,10.5366)"><g transform="matrix(1,0,0,0.851775,-31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(-0.268797,0,0,0.273288,155.348,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.112,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g><g transform="matrix(0.291836,0,0,0.273288,-35.4345,7.00041)"><g><path d="M330.441,18.753L330.441,281.297L241.349,281.297L241.349,18.753M351,-0C351,-0.003 220.79,-0 220.79,-0L220.79,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g><g transform="matrix(0.268797,0,0,0.273288,-59.3476,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.768,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g></svg>',
		]
	);
}
