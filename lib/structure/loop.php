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

add_action( 'genesis_before_loop', 'mai_setup_loop' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_setup_loop() {
	if ( ! mai_has_custom_loop() ) {
		return;
	}

	// Remove entry elements.
	remove_action( 'genesis_entry_header', 'genesis_do_post_format_image', 4 );
	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_open', 5 );
	remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_close', 15 );
	remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
	remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );

	remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
	remove_action( 'genesis_entry_content', 'genesis_do_post_content' );
	remove_action( 'genesis_entry_content', 'genesis_do_post_content_nav', 12 );
	remove_action( 'genesis_entry_content', 'genesis_do_post_permalink', 14 );

	remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_open', 5 );
	remove_action( 'genesis_entry_footer', 'genesis_entry_footer_markup_close', 15 );
	remove_action( 'genesis_entry_footer', 'genesis_post_meta' );

	// Swap loop.
	remove_action( 'genesis_loop', 'genesis_do_loop' );
	add_action( 'genesis_loop', 'mai_do_loop' );
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_loop() {
	$archive = mai_is_type_archive();
	$args    = mai_get_template_args();

	if ( have_posts() ) {

		/**
		 * Fires inside the standard loop, before the while() block.
		 *
		 * @since 2.1.0
		 */
		do_action( 'genesis_before_while' );

		if ( $archive ) {
			mai_do_entries_open( $args );
		}

		while ( have_posts() ) {
			the_post();

			/**
			 * Fires inside the standard loop, before the entry opening markup.
			 *
			 * @since 2.0.0
			 */
			do_action( 'genesis_before_entry' );

			global $post;
			mai_do_entry( $post, $args );

			/**
			 * Fires inside the standard loop, before the entry opening markup.
			 *
			 * @since 2.0.0
			 */
			do_action( 'genesis_after_entry' );
		}

		if ( $archive ) {
			mai_do_entries_close( $args );
		}

		/**
		 * Fires inside the standard loop, after the while() block.
		 *
		 * @since 0.1.0
		 */
		do_action( 'genesis_after_endwhile' );
	} else {

		/**
		 * Fires inside the standard loop when they are no posts to show.
		 *
		 * @since 0.1.0
		 */
		do_action( 'genesis_loop_else' );
	}
}

add_filter( 'pre_get_posts', 'mai_archive_posts_per_page' );
/**
 * Show only posts in 1 or more categories on the main blog.
 * Only targets the main blog page set as the static Page for Posts
 * in Dashboard > Settings > Reading.
 *
 * @since 0.1.0
 *
 * @param object $query WP Query.
 *
 * @return  void
 */
function mai_archive_posts_per_page( $query ) {

	// Bail if in the Dashboard.
	if ( is_admin() ) {
		return;
	}

	// Bail if not the main query.
	if ( ! $query->is_main_query() ) {
		return;
	}

	// Bail if not an archive.
	if ( ! mai_is_type_archive() ) {
		return;
	}

	// Bail if home, this uses the default posts_per_page.
	if ( is_home() ) {
		return;
	}

	// Bail if not an explicited supported loop.
	if ( ! in_array( mai_get_archive_args_name(), mai_get_option( 'archive-settings', mai_get_config( 'archive-settings' ) ), true ) ) {
		return;
	}

	// Get the template args.
	$args = mai_get_template_args();

	// Bail if no posts_per_page.
	if ( ! isset( $args['posts_per_page'] ) || ( empty( $args['posts_per_page'] ) && '0' !== $args['posts_per_page'] ) ) {
		return;
	}
	// TODO: Hide posts if posts_per_page is 0?
	// Set posts per page.
	$query->set( 'posts_per_page', $args['posts_per_page'] );
}
