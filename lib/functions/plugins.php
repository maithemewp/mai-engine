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

// Dependency installer labels.
add_filter( 'wp_dependency_dismiss_label', 'mai_get_name' );
add_filter( 'wp_dependency_required_row_meta', '__return_false' );

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Changes plugin dependency text.
 *
 * @since 0.1.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Mai Theme Dependency', 'mai-engine' )
	);

	return $actions;
}

add_action( 'wp_enqueue_scripts', 'mai_remove_simple_social_icons_css', 15 );
/**
 * Remove Simple Social Icons CSS.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_simple_social_icons_css() {
	mai_deregister_asset( 'simple-social-icons-font' );
}

add_filter( 'wpforms_settings_defaults', 'mai_wpforms_default_css' );
/**
 * Set the default WP Forms styling to "Base styling only".
 * This still requires the user to actually save the settings before it applies.
 *
 * @since 0.1.0
 *
 * @param array $defaults The settings defaults.
 *
 * @return array
 */
function mai_wpforms_default_css( $defaults ) {
	if ( isset( $defaults['general']['disable-css']['default'] ) ) {
		$defaults['general']['disable-css']['default'] = 2;
	}

	return $defaults;
}

add_filter( 'wpforms_frontend_form_data', 'mai_wpforms_default_button_class' );
/**
 * Add default button class to WP Forms.
 *
 * @since 0.1.0
 *
 * @param array $data The form data.
 *
 * @return array
 */
function mai_wpforms_default_button_class( $data ) {
	if ( isset( $data['settings']['submit_class'] ) && ! mai_has_string( 'button', $data['settings']['submit_class'] ) ) {
		$data['settings']['submit_class'] .= ' button';
		$data['settings']['submit_class']  = trim( $data['settings']['submit_class'] );
	}

	return $data;
}

add_filter( 'ssp_register_post_type_args', 'mai_ssp_add_settings' );
/**
 * Adds support for mai settings in Seriously Simple Podcasting.
 *
 * @since 2.15.0
 *
 * @param array $args The existing post type args.
 *
 * @return array
 */
function mai_ssp_add_settings( $args ) {
	$args['supports'][] = 'genesis-cpt-archives-settings';
	$args['supports'][] = 'mai-archive-settings';
	$args['supports'][] = 'mai-single-settings';
	$args['supports']   = array_unique( $args['supports'] );

	return $args;
}

add_filter( 'mai_get_option_archive-settings', 'mai_learndash_add_settings' );
add_filter( 'mai_get_option_single-settings', 'mai_learndash_add_settings' );
/**
 * Forces learndash courses post type to use archive/single settings.
 *
 * @since 2.10.0
 *
 * @param array $post_type The post types to for loop settings.
 *
 * @return array
 */
function mai_learndash_add_settings( $post_types ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $post_types;
	}

	$post_types[] = 'sfwd-courses';

	return array_unique( $post_types );
}

add_filter( 'mai_content_archive_settings', 'mai_learndash_course_archive_settings', 10, 2 );
/**
 * Removes posts_per_page setting from courses,
 * since learndash has it's own settings for this.
 *
 * @since 2.10.0
 *
 * @param array $settings The existing settings.
 * @param string $name    The content type name.
 *
 * @return array
 */
function mai_learndash_course_archive_settings( $settings, $name ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $settings;
	}

	if ( 'sfwd-courses' === $name ) {
		foreach ( $settings as $index => $setting ) {
			if ( 'posts_per_page' !== $setting['settings'] ) {
				continue;
			}

			unset( $settings[ $index ] );
		}
	}

	return $settings;
}

add_filter( 'mai_archive_args_name', 'mai_learndash_course_settings_name', 8 );
add_filter( 'mai_single_args_name', 'mai_learndash_course_settings_name', 8 );
/**
 * Uses course single/archive content settings for lessons, topics, quizes, and certificates.
 *
 * @since 2.10.0
 *
 * @param string $name The args name.
 *
 * @return string
 */
function mai_learndash_course_settings_name( $name ) {
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return $name;
	}

	$learndash_cpts = array_flip( [ 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-certificates' ] );

	if ( isset( $learndash_cpts[ $name ] ) ) {
		return 'sfwd-courses';
	}

	return $name;
}

add_filter( 'learndash_previous_post_link', 'mai_learndash_adjacent_post_link', 10, 4 );
add_filter( 'learndash_next_post_link', 'mai_learndash_adjacent_post_link', 10, 4 );
/**
 * Adds button classes to adjacent post links on LearnDash content.
 *
 * @since 2.10.0
 *
 * @param string $link      The link HTML.
 * @param string $permalink The link uri.
 * @param string $link_name The link text.
 * @param WP_Post $post     The adjacent post object.
 *
 * @since 2.10.0
 */
function mai_learndash_adjacent_post_link( $link, $permalink, $link_name, $post ) {
	$link = str_replace( 'prev-link', 'prev-link button button-secondary button-small', $link );
	$link = str_replace( 'next-link', 'next-link button button-secondary button-small', $link );
	return $link;
}
