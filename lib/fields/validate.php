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

add_filter( 'acf/is_field_key', 'mai_acf_is_field_key', 10, 2 );
/**
 * Checks if a field key is a valid ACF field key.
 * We need this because we wrongly used custom field keys that don't start with `field_`.
 * Our engine blocks use `mai_` and plugin blocks use `maicca_', `maipub_`, etc., so we check for `mai`.
 *
 * @since 2.35.0
 *
 * @param bool   $bool The result.
 * @param string $id   The identifier.
 *
 * @return bool
 */
function mai_acf_is_field_key( $bool, $id ) {
	// Bail if already true.
	if ( $bool ) {
		return $bool;
	}

	return $id && str_starts_with( $id, 'mai' );
}