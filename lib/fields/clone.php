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

add_action( 'acf/init', 'mai_register_clone_fields', 0 );
/**
 * Register field groups for resuable fields.
 *
 * @since TBD
 *
 * @return void
 */
function mai_register_clone_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$fields = array_merge(
		mai_get_icons_fields(),
		mai_get_columns_fields(),
		mai_get_grid_tabs_fields(),
		mai_get_grid_display_fields(),
		mai_get_grid_layout_fields(),
		mai_get_wp_query_fields(),
		mai_get_wp_term_query_fields()
	);

	acf_add_local_field_group(
		[
			'key'         => 'mai_clone_fields',
			'title'       => esc_html__( 'Mai Clone Fields', 'mai-engine' ),
			'fields'      => $fields,
			'location'    => false,
			'active'      => true,
			'description' => '',
		]
	);
}
