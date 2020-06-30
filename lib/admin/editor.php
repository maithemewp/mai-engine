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

add_action( 'after_setup_theme', 'mai_add_editor_color_palette' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
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
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_editor_font_sizes() {
	add_theme_support( 'editor-font-sizes', mai_get_font_sizes() );
}
