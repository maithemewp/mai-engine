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
	// $settings = new Mai_Entry_Settings( 'block' );
	// $fields   = $settings->fields;

	// // Setup fields.
	// foreach ( $fields as $name => $field ) {

	// Bail if not a block field.
	// if ( ! $field['block'] ) {
	// continue;
	// }

	// Bail if no groups.
	// if ( ! isset( $field['group'] ) ) {
	// continue;
	// }

	$fields = mai_get_config( 'grid-settings' );

	foreach ( $fields as $key => $field ) {

		// Post grid.
		if ( in_array( 'post', $field['block'], true ) ) {
			$post_grid[] = mai_get_acf_field_data( $key, $field );
		}
		// Term grid.
		if ( in_array( 'term', $field['block'], true ) ) {
			$term_grid[] = mai_get_acf_field_data( $key, $field );
		}
		// Post grid.
		if ( in_array( 'user', $field['block'], true ) ) {
			$user_grid[] = mai_get_acf_field_data( $key, $field );
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


// function mai_get_grid_show_choices() {

// }


/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $key   Field key.
 * @param array  $field Field data.
 *
 * @return array
 */
function mai_get_acf_field_data( $key, $field ) {

	// Setup data.
	$data = [
		'key'   => $key,
		'name'  => $field['name'],
		'label' => $field['label'],
		'type'  => $field['type'],
	];

	// Maybe add description.
	if ( isset( $field['desc'] ) ) {
		$data['instructions'] = $field['desc'];
	}

	// Additional attributes.
	if ( isset( $field['atts'] ) ) {
		foreach ( $field['atts'] as $key => $value ) {
			// Sub fields.
			if ( 'sub_fields' === $key ) {
				$data['sub_fields'] = [];
				foreach ( $value as $sub_key => $sub_field ) {
					$data['sub_fields'][] = mai_get_acf_field_data( $sub_key, $sub_field );
				}
			} else {
				// Standard field data.
				$data[ $key ] = $value;
			}
		}
	}

	// Maybe add conditional logic.
	if ( isset( $field['conditions'] ) ) {
		$data['conditional_logic'] = $field['conditions'];
	}

	// Maybe add default.
	if ( isset( $field['default'] ) ) {
		/**
		 * This needs default_value instead of default.
		 *
		 * @link  https://www.advancedcustomfields.com/resources/register-fields-via-php/
		 */
		$data['default_value'] = $field['default'];
	}

	// Maybe add choices.
	// TODO: If sites with a lot of posts cause slow loading,
	// (if ACF ajax isn't working with this code),
	// we may need to move to load_field filters,
	// though this should work as-is.
	if ( isset( $field['choices'] ) ) {
		if ( is_array( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];
		} elseif ( is_callable( $field['choices'] ) ) {
			$data['choices'] = call_user_func( $field['choices'] );
		}
	}

	return $data;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_grid_localized_data() {
	$fields = mai_get_config( 'grid-settings' );
	$fields = wp_list_pluck( $fields, 'name' );
	$fields = array_flip( $fields );

	return [ 'keys' => $fields ];
}
