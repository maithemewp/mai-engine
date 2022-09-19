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
 * Gets an ACF request, checking nonce and value.
 *
 * @since 0.1.0
 *
 * @param string $request Request data.
 *
 * @return bool
 */
function mai_get_acf_request( $request ) {
	if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST[ $request ] ) && ! empty( $_REQUEST[ $request ] ) ) {
		return $_REQUEST[ $request ];
	}

	return false;
}

/**
 * Gets parent block field value from block context.
 * Props Martin Jost @CreativeDive.
 *
 * CURRENTLY UNUSED, BUT LEFT BECAUSE WE MAY WANT IT LATER.
 *
 * @access private
 *
 * @since TBD
 *
 * @param string $field_name The ACF field name.
 * @param array  $fields     The field data from `$context['acf/fields']`.
 *
 * @return mixed
 */
function mai_get_acf_parent_block_field( $field_name, $fields ) {
	// Get the correct field, depending on the "field_name" or "field_key" is passed.
	$data = acf_setup_meta( $fields );

	// Get the field value.
	$value = isset( $data[ $field_name ] ) ? $data[ $field_name ] : '';

	// Get the field object.
	$field = get_field_object( $field_name );

	// Get the formatted value if exists.
	$value = apply_filters( 'acf/format_value', $value, '', $field );

	return $value;
}
