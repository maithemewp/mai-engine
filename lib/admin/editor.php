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

add_filter( 'admin_body_class', 'mai_admin_body_classes' );
/**
 * Add additional classes to the body element.
 *
 * @since 2.6.0
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function mai_admin_body_classes( $classes ) {
	// Layout.
	$layout  = genesis_site_layout();
	$classes = mai_add_classes( $layout, $classes );

	// Sidebar.
	if ( mai_has_string( 'sidebar', $layout ) ) {
		$classes = mai_add_classes( 'has-sidebar', $classes );
	} else {
		$classes = mai_add_classes( 'no-sidebar', $classes );
	}

	return $classes;
}

add_action( 'after_setup_theme', 'mai_add_editor_color_palette' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_editor_color_palette() {
	add_theme_support( 'editor-color-palette', mai_get_editor_color_palette() );
}

add_action( 'after_setup_theme', 'mai_add_editor_font_sizes' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_editor_font_sizes() {
	add_theme_support( 'editor-font-sizes', mai_get_font_sizes() );
}

add_action( 'edit_form_after_title', 'mai_add_editor_on_posts_page' );
/**
 * Enable the editor on the page for posts.
 *
 * Force an empty space as the post content so block editor (Gutenberg) will show
 * in the admin when editing this page, otherwise default editor will show. This
 * is a bit hacky and I wish Gutenberg didn't do this.
 *
 * @since 2.4.0 Moved to editor.php file.
 * @since 0.1.0
 *
 * @param WP_Post $post Post object.
 *
 * @return void
 */
function mai_add_editor_on_posts_page( $post ) {
	if ( get_option( 'page_for_posts' ) !== $post->ID ) {
		return;
	}

	remove_action( 'edit_form_after_title', '_wp_posts_page_notice' );
	add_post_type_support( 'page', 'editor' );

	// Bail if the post has content.
	if ( ! empty( $post->post_content ) ) {
		return;
	}
}
