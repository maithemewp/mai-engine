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
 * Uses course single/archive content settings for lessons, topics, quizzes, and certificates.
 *
 * @since 2.10.0
 *
 * @param string $name The args name.
 *
 * @return string
 */
function mai_learndash_course_settings_name( $name ) {
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
