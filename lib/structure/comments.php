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

add_filter( 'genesis_comment_list_args', 'mai_setup_comments_gravatar' );
/**
 * Modify size of the Gravatar in the entry comments.
 *
 * @since 0.1.0
 *
 * @param array $args Genesis comment list arguments.
 *
 * @return mixed
 */
function mai_setup_comments_gravatar( array $args ) {
	$args['avatar_size'] = mai_get_config( 'settings' )['genesis']['avatar_size'];

	return $args;
}
