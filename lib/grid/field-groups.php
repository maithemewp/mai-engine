<?php


add_action( 'acf/init', 'mai_register_grid_field_groups' );
function mai_register_grid_field_groups() {

	$post_grid = $term_grid = $user_grid = [];

	// Settings config.
	$config = new Mai_Entry_Settings( 'block' );
	$fields = $config->get_fields();

	// Setup fields.
	foreach( $fields as $name => $field ) {

		// Bail if not a block field.
		if ( ! $field['block'] ) {
			continue;
		}

		// Bail if no groups.
		if ( ! isset( $field['group'] ) ) {
			continue;
		}

		// Post grid.
		if ( in_array( 'mai_post_grid', $field['group'] ) ) {
			$post_grid[] = $config->get_data( $name, $field );
		}
		// Term grid.
		if ( in_array( 'mai_term_grid', $field['group'] ) ) {
			$term_grid[] = $config->get_data( $name, $field );
		}
		// Post grid.
		if ( in_array( 'mai_user_grid', $field['group'] ) ) {
			$user_grid[] = $config->get_data( $name, $field );
		}
	}

	acf_add_local_field_group( array(
		'key'      => 'group_5de9b54440a2p',
		'title'    => __( 'Mai Post Grid', 'mai-engine' ),
		'fields'   => $post_grid,
		'location' => array(
			array(
				array(
					'param'    => 'block',
					'operator' => '==',
					'value'    => 'acf/mai-post-grid',
				),
			),
		),
		'active' => true,
	));

	acf_add_local_field_group( array(
		'key'      => 'group_5de9b54440a2t',
		'title'    => __( 'Mai Term Grid', 'mai-engine' ),
		'fields'   => $term_grid,
		'location' => array(
			array(
				array(
					'param'    => 'block',
					'operator' => '==',
					'value'    => 'acf/mai-term-grid',
				),
			),
		),
		'active' => true,
	));

	// acf_add_local_field_group( array(
	// 	'key'      => 'group_5de9b54440a2u',
	// 	'title'    => __( 'Mai Term Grid', 'mai-engine' ),
	// 	'fields'   => $user_grid,
	// 	'location' => array(
	// 		array(
	// 			array(
	// 				'param'    => 'block',
	// 				'operator' => '==',
	// 				'value'    => 'acf/mai-user-grid',
	// 			),
	// 		),
	// 	),
	// 	'active' => true,
	// ));

}
