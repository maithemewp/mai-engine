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

add_filter( 'wp_theme_json_data_default', 'mai_remove_default_theme_json_presets' );
/**
 * Removes WordPress core's default theme.json presets.
 *
 * Mai provides its own color palette and font sizes via `add_theme_support()`, and
 * does not use core's default gradients, duotone, spacing sizes, shadows, or aspect
 * ratios. Emptying those default presets removes a large block of unused
 * `--wp--preset--*` custom properties from the global styles output on every page
 * (and in the editor). Mai's own colors are applied via `--color-*` and are
 * unaffected.
 *
 * @since TBD
 *
 * @param WP_Theme_JSON_Data $theme_json The default theme.json data.
 *
 * @return WP_Theme_JSON_Data
 */
function mai_remove_default_theme_json_presets( $theme_json ) {
	$data = $theme_json->get_data();

	$remove = [
		'color'      => [ 'palette', 'gradients', 'duotone' ],
		'typography' => [ 'fontSizes' ],
		'spacing'    => [ 'spacingSizes' ],
		'shadow'     => [ 'presets' ],
		'dimensions' => [ 'aspectRatios' ],
	];

	foreach ( $remove as $group => $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $data['settings'][ $group ][ $key ] ) ) {
				continue;
			}

			// Default theme.json presets are origin-keyed (`default`); empty that origin.
			if ( isset( $data['settings'][ $group ][ $key ]['default'] ) ) {
				$data['settings'][ $group ][ $key ]['default'] = [];
			} else {
				$data['settings'][ $group ][ $key ] = [];
			}
		}
	}

	// Spacing presets (`--wp--preset--spacing--*`) are generated from spacingScale,
	// so emptying the sizes array is not enough; zero the steps to stop generation.
	if ( isset( $data['settings']['spacing']['spacingScale']['default'] ) ) {
		$data['settings']['spacing']['spacingScale']['default']['steps'] = 0;
	}

	return $theme_json->update_with( $data );
}
