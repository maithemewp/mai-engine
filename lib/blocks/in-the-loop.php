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

/**
 * Fixes WP core blocks when used outside of the loop, often via a Content Area.
 *
 * @access private
 */
class Mai_Core_Blocks_In_The_Loop_Fix {
	protected $in_the_loop = null;

	/**
	 * Get it started.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Run the hooks.
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'pre_render_block',                      [ $this, 'set_in_the_loop' ], 10, 3 );
		add_filter( 'render_block_core/post-content',        [ $this, 'revert_in_the_loop' ], 10, 3 );
		add_filter( 'render_block_core/post-featured-image', [ $this, 'revert_in_the_loop' ], 10, 3 );
	}

	/**
	 * Set `$wp_query->in_the_loop` to true any time the post-featured-image block is used.
	 * This stops the `render_block_core_post_featured_image()` and `render_block_core_post_content()` functions
	 * from using `the_post()`, which causes other issues like empty content, in Mai Theme.
	 *
	 * @param string|null   $pre_render   The pre-rendered content. Default null.
	 * @param array         $parsed_block The block being rendered.
	 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 *
	 * @return null|string
	 */
	function set_in_the_loop( $pre_render, $parsed_block, $parent_block ) {
		if ( ! isset( $parsed_block['blockName'] ) ) {
			return $pre_render;
		}

		if ( ! in_array( $parsed_block['blockName'], [ 'core/post-featured-image', 'core/post-content' ] ) ) {
			return $pre_render;
		}

		global $wp_query;

		if ( isset( $wp_query ) && $wp_query->posts && ! in_the_loop() ) {
			$this->in_the_loop     = in_the_loop();
			$wp_query->in_the_loop = true;
		}

		return $pre_render;
	}

	/**
	 * Revert `$wp_query->in_the_loop` to whatever its value was before `pre_render_block`.
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The full block, including name and attributes.
	 * @param WP_Block $instance      The block instance.
	 *
	 * @return string
	 */
	function revert_in_the_loop( $block_content, $parsed_block, $wp_block ) {
		if ( is_null( $this->in_the_loop ) ) {
			return $block_content;
		}

		global $wp_query;

		if ( isset( $wp_query ) && $wp_query->posts && ! is_null( $this->in_the_loop ) ) {
			$wp_query->in_the_loop = $this->in_the_loop;
		}

		return $block_content;
	}
}

new Mai_Core_Blocks_In_The_Loop_Fix;