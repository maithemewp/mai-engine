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

add_action( 'init', 'mai_add_single_content_settings', 99 );
/**
 * Adds single content settings.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_single_content_settings() {
	$handle   = mai_get_handle();
	$panel    = 'single-content';
	$defaults = mai_get_config( 'settings' )['single-content']['enable'];
	$sections = mai_get_option( 'single-settings', $defaults, false );

	// Remove any content types that no longer exist.
	foreach ( $sections as $index => $section ) {
		if ( post_type_exists( $section ) ) {
			continue;
		}
		unset( $sections[ $index ] );
	}

	new \Kirki\Panel(
		"{$handle}-{$panel}",
		[
			'title' => mai_convert_case( $panel, 'title' ),
			'panel' => $handle,
		]
	);

	foreach ( $sections as $section ) {
		if ( post_type_exists( $section ) && $post_type = get_post_type_object( $section ) ) {
			$title = $post_type->labels->singular_name;
		} elseif ( taxonomy_exists( $section ) && $taxonomy = get_taxonomy( $section ) ) {
			$title = $taxonomy->label;
		} else {
			$title = mai_convert_case( $section, 'title' );
		}

		new \Kirki\Section(
			"{$handle}-{$panel}-{$section}",
			[
				'title' => $title,
				'panel' => "{$handle}-{$panel}",
			]
		);

		$settings = mai_get_single_content_settings( $section );

		foreach ( $settings as $field ) {
			$settings         = isset( $field['settings'] ) ? $field['settings'] : '';
			$field['section'] = "{$handle}-{$panel}-{$section}";

			if ( $settings ) {
				$field['settings'] = $section . '-' . mai_convert_case( $settings, 'kebab' );
			}

			$field['option_type'] = 'option';
			$field['option_name'] = $handle . '[' . $panel . '][' . $section . ']';
			$field['settings']    = $settings;

			if ( isset( $field['default'] ) && is_string( $field['default'] ) && mai_has_string( 'mai_', $field['default'] ) && is_callable( $field['default'] ) ) {
				$field['default'] = call_user_func( $field['default'], $section );
			}

			if ( isset( $field['choices'] ) && is_string( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) && is_callable( $field['choices'] ) ) {
				$field['choices'] = call_user_func( $field['choices'] );
			}

			if ( isset( $field['sanitize'] ) ) {
				$field['sanitize_callback'] = $field['sanitize'];
			}

			// Workaround to fix active callback function with nested options.
			if ( isset( $field['active_callback'] ) ) {
				if ( is_array( $field['active_callback'] ) ) {
					foreach ( $field['active_callback'] as $index => $condition ) {
						foreach ( $condition as $key => $value ) {
							if ( 'setting' === $key ) {
								$field['active_callback'][ $index ][ $key ] = "{$handle}[$panel][$section][$value]";
							}

							if ( is_array( $value ) ) {
								foreach ( $value as $nested_key => $nested_value ) {
									if ( 'setting' === $nested_key ) {
										$field['active_callback'][ $index ][ $key ][ $nested_key ] = "{$handle}[$panel][$section][$nested_value]";
									}
								}
							}
						}
					}
				}
			}

			Kirki::add_field( $handle, $field );
		}
	}
}
