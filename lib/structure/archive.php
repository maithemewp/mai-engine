<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Reposition entry image.
remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
add_action( 'genesis_entry_header', 'genesis_do_post_image', 1 );

// Enable shortcodes in archive description.
add_filter( 'genesis_cpt_archive_intro_text_output', 'do_shortcode' );

add_filter( 'post_class', 'mai_archive_post_class' );
/**
 * Add column class to archive posts.
 *
 * @since 0.1.0
 *
 * @param array $classes Array of post classes.
 *
 * @return array
 */
function mai_archive_post_class( $classes ) {
	if ( ! mai_is_type_archive() ) {
		return $classes;
	}

	if ( class_exists( 'WooCommerce' ) && is_woocommerce() ) {
		return $classes;
	}

	if ( did_action( 'genesis_before_sidebar_widget_area' ) ) {
		return $classes;
	}

	if ( 'full-width-content' === genesis_site_layout() ) {
		$classes[] = 'one-third';
		$count     = 3;

	} else {
		$classes[] = 'one-half';
		$count     = 2;
	}

	global $wp_query;

	if ( 0 === $wp_query->current_post || 0 === $wp_query->current_post % $count ) {
		$classes[] = 'first';
	}

	return $classes;
}

add_filter( 'get_the_content_more_link', 'mai_read_more_link' );
add_filter( 'the_content_more_link', 'mai_read_more_link' );
/**
 * Modify the content limit read more link
 *
 * @since 0.1.0
 *
 * @param string $more_link_text Default more link text.
 *
 * @return string
 */
function mai_read_more_link( $more_link_text ) {
	return str_replace( [ '[', ']', '...' ], '', $more_link_text );
}

add_filter( 'genesis_author_box_gravatar_size', 'mai_author_box_gravatar' );
/**
 * Modifies size of the Gravatar in the author box.
 *
 * @since 2.2.3
 *
 * @param int $size Original icon size.
 *
 * @return int Modified icon size.
 */
function mai_author_box_gravatar( $size ) {
	return mai_get_config( 'genesis-settings' )['avatar_size'];
}

add_action( 'genesis_entry_header', 'mai_entry_wrap_open', 4 );
/**
 * Outputs the opening entry wrap markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_entry_wrap_open() {
	if ( mai_is_type_archive() ) {
		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'entry-wrap',
			]
		);
	}
}

add_action( 'genesis_entry_footer', 'mai_entry_wrap_close', 15 );
/**
 * Outputs the closing entry wrap markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_entry_wrap_close() {
	if ( mai_is_type_archive() ) {
		genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'entry-wrap',
			]
		);
	}
}

add_filter( 'genesis_markup_entry-header_open', 'mai_widget_entry_wrap_open', 10, 2 );
/**
 * Outputs the opening entry wrap markup in widgets.
 *
 * @since 0.1.0
 *
 * @param string $open Opening markup.
 * @param array  $args Markup args.
 *
 * @return string
 */
function mai_widget_entry_wrap_open( $open, $args ) {
	if ( isset( $args['params']['is_widget'] ) && $args['params']['is_widget'] ) {
		$markup = genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'entry-wrap',
				'echo'    => false,
			]
		);

		$open = $markup . $open;
	}

	return $open;
}
