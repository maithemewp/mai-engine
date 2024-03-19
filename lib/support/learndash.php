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

add_action( 'learndash-content-tabs-before', 'mai_learndash_content_tabs_before', 10, 3 );
/**
 * Fires before the content tabs.
 *
 * @since TBD
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 *
 * @return void
 */
function mai_learndash_content_tabs_before( $post_id, $group_id, $user_id ) {
	ob_start( 'mai_learndash_handle_content' );
}

add_action( 'learndash-content-tabs-after', 'mai_learndash_content_tabs_after', 10, 3 );
/**
 * Fires after the content tabs.
 *
 * @since TBD
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 *
 * @return voide
 */
function mai_learndash_content_tabs_after( $post_id, $group_id, $user_id ) {
	/**
	 * End flush.
	 *
	 * @link https://stackoverflow.com/questions/7355356/whats-the-difference-between-ob-flush-and-ob-end-flush
	 */
	if ( ob_get_length() ) {
		ob_end_flush();
	}
}

add_action( 'learndash-all-course-steps-before', 'mai_learndash_all_course_steps_before', 10, 3 );
/**
 * Fires before the content tabs.
 *
 * @since TBD
 *
 * @param string $post_type The post type.
 * @param int    $course_id Course ID.
 * @param int    $user_id   User ID.
 *
 * @return void
 */
function mai_learndash_all_course_steps_before( $post_type, $course_id, $user_id ) {
	ob_start( 'mai_learndash_handle_course_steps' );
}

add_action( 'learndash-all-course-steps-after', 'mai_learndash_all_course_steps_after', 10, 3 );
/**
 * Fires after the content tabs.
 *
 * @since TBD
 *
 * @param string $post_type The post type.
 * @param int    $course_id Course ID.
 * @param int    $user_id   User ID.
 *
 * @return voide
 */
function mai_learndash_all_course_steps_after( $post_type, $course_id, $user_id ) {
	/**
	 * End flush.
	 *
	 * @link https://stackoverflow.com/questions/7355356/whats-the-difference-between-ob-flush-and-ob-end-flush
	 */
	if ( ob_get_length() ) {
		ob_end_flush();
	}
}

/**
 * Buffer callback.
 *
 * @since TBD
 *
 * @param string $buffer The full dom markup.
 *
 * @return string
 */
function mai_learndash_handle_content( $buffer ) {
	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $buffer );

	// Loop through ad units.
	while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'ld-tab-content' ] ) ) {
		$tags->add_class( 'entry-content' );
	}

	// Update the buffer.
	$buffer = $tags->get_updated_html();

	return $buffer;
}

/**
 * Buffer callback.
 *
 * @since TBD
 *
 * @param string $buffer The full dom markup.
 *
 * @return string
 */
function mai_learndash_handle_course_steps( $buffer ) {
	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $buffer );

	// Loop through elements.
	while ( $tags->next_tag( [ 'tag_name' => 'a', 'class_name' => 'ld-button' ] ) ) {
		$tags->remove_class( 'ld-button' );
		$tags->add_class( 'button button-secondary button-small' );
	}

	// Update the buffer.
	$buffer = $tags->get_updated_html();

	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $buffer );

	// Loop through elements.
	while ( $tags->next_tag( [ 'tag_name' => 'input', 'class_name' => 'learndash_mark_complete_button' ] ) ) {
		$tags->add_class( 'button button-small' );
	}

	// Update the buffer.
	$buffer = $tags->get_updated_html();

	return $buffer;
}
