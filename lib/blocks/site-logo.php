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

add_filter( 'render_block', 'mai_render_site_logo_block', 10, 2 );
/**
 * Swaps class name for site logo block to avoid conflicts with custom-logo CSS in Mai.
 *
 * @since 2.30.3
 *
 * @param string $block_content Block HTML markup.
 * @param array  $block         Block data.
 *
 * @return string
 */
function mai_render_site_logo_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( 'core/site-logo' !== $block['blockName'] ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );

	while ( $tags->next_tag( [ 'tag_name' => 'img', 'class_name' => 'custom-logo' ] ) ) {
		$class = (string) $tags->get_attribute( 'class' );
		$class = str_replace( 'custom-logo', 'site-logo', $class );
		$tags->set_attribute( 'class', $class );
	}

	return $tags->get_updated_html();
}