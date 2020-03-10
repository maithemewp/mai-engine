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

add_action( 'acf/init', 'mai_register_grid_field_groups' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_grid_field_groups() {
	$post_grid = [];
	$term_grid = [];
	$user_grid = [];

	// Get fields.
	$settings = new Mai_Entry_Settings( 'block' );
	$fields   = $settings->fields;

	// Setup fields.
	foreach ( $fields as $name => $field ) {

		// Bail if not a block field.
		if ( ! $field['block'] ) {
			continue;
		}

		// Bail if no groups.
		if ( ! isset( $field['group'] ) ) {
			continue;
		}

		// Post grid.
		if ( in_array( 'mai_post_grid', $field['group'], true ) ) {
			$post_grid[] = $settings->get_data( $name, $field );
		}
		// Term grid.
		if ( in_array( 'mai_term_grid', $field['group'], true ) ) {
			$term_grid[] = $settings->get_data( $name, $field );
		}
		// Post grid.
		if ( in_array( 'mai_user_grid', $field['group'], true ) ) {
			$user_grid[] = $settings->get_data( $name, $field );
		}
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_5de9b54440a2p',
			'title'    => __( 'Mai Post Grid', 'mai-engine' ),
			'fields'   => $post_grid,
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-post-grid',
					],
				],
			],
			'active'   => true,
		]
	);

	acf_add_local_field_group(
		[
			'key'      => 'group_5de9b54440a2t',
			'title'    => __( 'Mai Term Grid', 'mai-engine' ),
			'fields'   => $term_grid,
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-term-grid',
					],
				],
			],
			'active'   => true,
		]
	);
}
