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

// Enable shortcodes in archive description.
add_filter( 'genesis_cpt_archive_intro_text_output', 'do_shortcode' );

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
