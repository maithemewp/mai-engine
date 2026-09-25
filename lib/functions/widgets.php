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

// Enable shortcodes in widgets.
add_filter( 'widget_text', 'do_shortcode' );

/**
 * Count number of widgets in a widget area.
 *
 * @since 0.1.0
 *
 * @param string $widget_area_id The widget area ID.
 *
 * @return int
 */
function mai_get_widget_count( $widget_area_id ) {

	/**
	 * If loading from front page, consult $_wp_sidebars_widgets rather than options
	 * to see if wp_convert_widget_settings() has made manipulations in memory.
	 */
	global $_wp_sidebars_widgets;

	$wp_sidebars_widgets = ! empty( $_wp_sidebars_widgets ) ? $_wp_sidebars_widgets : get_option( 'sidebars_widgets', [] );

	if ( isset( $wp_sidebars_widgets[ $widget_area_id ] ) ) {
		$widget_count = count( $wp_sidebars_widgets[ $widget_area_id ] );

	} else {
		$widget_count = 0;
	}

	return $widget_count;
}

add_action( 'widgets_init', 'mai_register_reusable_block_widget' );
/**
 * Register the widget.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_reusable_block_widget() {
	register_widget( 'Mai_Reusable_Block_Widget' );
}

add_filter( 'use_widgets_block_editor', 'mai_use_widgets_block_editor', 5 );
/**
 * Turns the block widget editor on or off from Mai's widget screen choice.
 *
 * Genesis turns it off at priority 0. This runs at 5, so a site or plugin that
 * sets its own value at the default priority 10 still wins.
 *
 * @since 2.41.0
 *
 * @return bool
 */
function mai_use_widgets_block_editor() {
	return mai_get_widgets_block_editor_setting();
}

/**
 * Returns Mai's widget screen choice: the saved one, or the default.
 *
 * @since 2.41.0
 *
 * @return bool
 */
function mai_get_widgets_block_editor_setting() {
	return mai_get_saved_widgets_block_editor() ?? mai_get_widgets_block_editor_default();
}

/**
 * Returns the saved widget screen choice, or null when none is saved.
 *
 * Reads the whole options array, because mai_get_option() can't tell a saved
 * false from nothing saved.
 *
 * @since 2.41.0
 *
 * @return bool|null
 */
function mai_get_saved_widgets_block_editor() {
	$options = mai_get_options();

	if ( ! is_array( $options ) || ! array_key_exists( 'widgets-block-editor', $options ) ) {
		return null;
	}

	return (bool) $options['widgets-block-editor'];
}

/**
 * Returns the widget editor setting's value when nothing is saved.
 *
 * On, unless the site already uses classic widgets. A site with classic widgets
 * in its widget areas keeps the classic screen until someone turns blocks on.
 *
 * @since 2.41.0
 *
 * @return bool
 */
function mai_get_widgets_block_editor_default() {
	if ( ! mai_get_config( 'settings' )['widgets']['block-editor'] ) {
		return false;
	}

	return ! mai_has_classic_widgets();
}

/**
 * Whether any widget area holds a classic widget.
 *
 * Inactive widgets don't count. Block widgets are stored as `block-N` and don't
 * count either.
 *
 * @since 2.41.0
 *
 * @return bool
 */
function mai_has_classic_widgets() {
	$sidebars = get_option( 'sidebars_widgets', [] );

	if ( ! is_array( $sidebars ) ) {
		return false;
	}

	foreach ( $sidebars as $sidebar_id => $widget_ids ) {
		if ( 'wp_inactive_widgets' === $sidebar_id || ! is_array( $widget_ids ) ) {
			continue;
		}

		foreach ( $widget_ids as $widget_id ) {
			if ( ! str_starts_with( (string) $widget_id, 'block-' ) ) {
				return true;
			}
		}
	}

	return false;
}

add_action( 'admin_init', 'mai_remove_genesis_block_widgets_notice' );
/**
 * Removes Genesis's notice about opting in to block widgets.
 *
 * It tells people to add a code snippet. Mai's Switch to blocks notice and Help
 * tab on the Widgets screen replace that.
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_remove_genesis_block_widgets_notice() {
	remove_action( 'admin_notices', 'genesis_block_widgets_optin_notification' );
}

/**
 * Switches the widget screen to blocks and saves the choice.
 *
 * Widgets stay as they are. The block editor shows each classic widget as a
 * Legacy Widget block, with a live preview and its own settings form. They are
 * not converted to blocks. A Mai Synced Patterns widget can't become a synced
 * pattern block, because the block widget editor doesn't allow those
 * (`ALLOW_REUSABLE_BLOCKS` is false in core's edit-widgets package).
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_switch_widgets_to_blocks() {
	mai_update_option( 'widgets-block-editor', true );
}

add_action( 'admin_post_mai_switch_widgets_editor', 'mai_switch_widgets_editor_action' );
/**
 * Handles the Switch to blocks link on the Widgets screen.
 *
 * The switch only goes one way. Going back to the classic screen takes code:
 * `add_filter( 'use_widgets_block_editor', '__return_false' );`.
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_switch_widgets_editor_action() {
	check_admin_referer( 'mai_switch_widgets_editor' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change the widget screen.', 'mai-engine' ), 403 );
	}

	mai_switch_widgets_to_blocks();

	// Code at a later priority can still keep the classic screen. Say so, rather than
	// reloading the same screen with nothing changed.
	$url = wp_use_widgets_block_editor() ? admin_url( 'widgets.php' ) : add_query_arg( 'mai-widgets', 'forced', admin_url( 'widgets.php' ) );

	wp_safe_redirect( $url );
	exit;
}

/**
 * Returns the Switch to blocks link.
 *
 * @since 2.41.0
 *
 * @return string
 */
function mai_get_switch_widgets_editor_url() {
	return wp_nonce_url( add_query_arg( [ 'action' => 'mai_switch_widgets_editor' ], admin_url( 'admin-post.php' ) ), 'mai_switch_widgets_editor' );
}

/**
 * Whether to offer the switch to blocks on the classic Widgets screen.
 *
 * Only when the screen is classic, Mai's own choice is classic too, and the user
 * can manage widgets. When Mai's choice is blocks but the screen is still classic,
 * code on the site is keeping it classic, and switching would do nothing.
 *
 * @since 2.41.0
 *
 * @return bool
 */
function mai_can_switch_widgets_to_blocks() {
	return current_user_can( 'edit_theme_options' ) && ! wp_use_widgets_block_editor() && ! mai_get_widgets_block_editor_setting();
}

/**
 * Returns the text that offers the switch to blocks.
 *
 * Shared by the notice and the Help tab.
 *
 * @since 2.41.0
 *
 * @return string
 */
function mai_get_switch_widgets_to_blocks_text() {
	return __( 'Widget areas can now hold blocks. Your current widgets stay as they are. Once you switch, going back takes a code change.', 'mai-engine' );
}
