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
 * Gets fields for acf field group.
 *
 * @access private
 *
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_grid_tabs_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$fields = [
		[
			'key'   => 'mai_grid_block_display_tab',
			'name'  => 'display_tab',
			'label' => esc_html__( 'Display', 'mai-engine' ),
			'type'  => 'tab',
		],
		[
			'key'   => 'mai_grid_block_layout_tab',
			'name'  => 'layout_tab',
			'label' => esc_html__( 'Layout', 'mai-engine' ),
			'type'  => 'tab',
		],
		[
			'key'   => 'mai_grid_block_entries_tab',
			'name'  => 'entries_tab',
			'label' => esc_html__( 'Entries', 'mai-engine' ),
			'type'  => 'tab',
		],
	];

	return $fields;
}
