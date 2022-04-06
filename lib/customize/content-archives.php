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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_add_content_archive_settings', 99 );
/**
 * Add content archive customizer settings.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_content_archive_settings() {
	$handle   = mai_get_handle();
	$panel    = 'content-archives';
	$defaults = mai_get_config( 'settings' )['content-archives']['enable'];
	$sections = mai_get_option( 'archive-settings', $defaults, false );

	// Remove any content types that no longer exist.
	foreach ( $sections as $index => $section ) {
		if ( post_type_exists( $section ) || taxonomy_exists( $section ) ) {
			continue;
		}

		if ( in_array( $section, [ 'search', 'author', 'date' ] ) ) {
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
			$title = $post_type->label;
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

		$settings = mai_get_content_archive_settings( $section );

		foreach ( $settings as $field ) {
			if ( 'post' === $section && 'posts_per_page' === $field['settings'] ) {
				continue;
			}

			$settings         = isset( $field['settings'] ) ? $field['settings'] : '';
			$field['section'] = "{$handle}-{$panel}-{$section}";

			if ( $settings ) {
				$field['settings'] = mai_get_kirki_setting( $settings, "[$panel][$section]" );
			}

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
			if ( isset( $field['active_callback'] ) && is_array( $field['active_callback'] ) ) {
				$field['active_callback'] = mai_get_kirki_active_callback( $field['active_callback'], $panel, $section );
			}

			$class = mai_get_kirki_class( $field['type'] );
			unset( $field['type'] );

			new $class( mai_parse_kirki_args( $field ) );
		}
	}
}

/**
 * Adds Posts Per Page option to Customizer > Theme Settings > Content Archives > Post.
 * Saves/manages WP core option.
 *
 * @since 0.1.0
 * @since 2.4.4 Changed to customize_register hook and use default API to register field,
 *              So it can be saved directly to the core posts_per_page option.
 *
 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
 *
 * @return void
 */
add_action( 'customize_register', 'mai_customize_register_posts_per_page', 999 );
function mai_customize_register_posts_per_page( $wp_customize ) {
	$handle = mai_get_handle();

	$wp_customize->add_setting(
		'posts_per_page',
		[
			'default'           => absint( get_option( 'posts_per_page' ) ),
			'type'              => 'option',
			'sanitize_callback' => 'absint',
		]
	);

	$wp_customize->add_control(
		'posts_per_page',
		[
			'label'    => __( 'Posts Per Page', 'mai-engine' ),
			'section'  => $handle . '-content-archives-post',
			'settings' => 'posts_per_page',
			'type'     => 'text',
		]
	);
}
