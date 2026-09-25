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
 * Turns the block widget editor on or off from the Customizer setting.
 *
 * Genesis turns it off at priority 0. This runs at 5, so a site or plugin that
 * sets its own value at the default priority 10 still wins.
 *
 * Reads the option straight from the database rather than through the cached
 * mai_get_option(), because the upgrade saves it earlier in the same request.
 *
 * @since 2.41.0
 *
 * @param bool $use_block_editor Whether to use the block widget editor.
 *
 * @return bool
 */
function mai_use_widgets_block_editor( $use_block_editor ) {
	$options = get_option( mai_get_handle(), [] );

	if ( is_array( $options ) && array_key_exists( 'widgets-block-editor', $options ) ) {
		return (bool) $options['widgets-block-editor'];
	}

	return mai_get_widgets_block_editor_default();
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
 * It tells people to add a code snippet, which the Customizer setting replaces.
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_remove_genesis_block_widgets_notice() {
	remove_action( 'admin_notices', 'genesis_block_widgets_optin_notification' );
}

/**
 * Switches the widget screen between blocks and classic, and saves the choice.
 *
 * Widgets stay as they are. The block editor shows each classic widget as a
 * Legacy Widget block, with a live preview and its own settings form. Mai
 * Synced Patterns widgets are not converted to synced pattern blocks, because
 * the block widget editor doesn't allow those (`ALLOW_REUSABLE_BLOCKS` is false
 * in core's edit-widgets package).
 *
 * @since 2.41.0
 *
 * @param bool $blocks Whether to use the block widget editor.
 *
 * @return void
 */
function mai_switch_widgets_editor( $blocks ) {
	mai_update_option( 'widgets-block-editor', (bool) $blocks );
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

	mai_switch_widgets_editor( true );

	wp_safe_redirect( admin_url( 'widgets.php' ) );
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
	return wp_nonce_url( admin_url( 'admin-post.php?action=mai_switch_widgets_editor' ), 'mai_switch_widgets_editor' );
}
