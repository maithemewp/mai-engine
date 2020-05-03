<?php

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $file
 *
 * @return array
 */
function mai_import_demo_data_widgets( $file ) {
	global $wp_registered_sidebars, $wp_registered_widget_controls;

	if ( ! file_exists( $file ) ) {
		wp_die(
			esc_html__( 'Import file could not be found. Please try again.', 'mai-engine' ),
			'',
			[
				'back_link' => true,
			]
		);
	}

	$data = json_decode( implode( '', file( $file ) ) );

	if ( empty( $data ) || ! is_object( $data ) ) {
		wp_die(
			esc_html__( 'Import data could not be read. Please try a different file.', 'mai-engine' ),
			'',
			[
				'back_link' => true,
			]
		);
	}

	$available_widgets   = [];
	$widget_instances    = [];
	$results             = [];
	$widget_message_type = '';
	$widget_message      = '';

	foreach ( $wp_registered_widget_controls as $widget ) {
		if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
			$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
			$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
		}
	}

	foreach ( $available_widgets as $widget_data ) {
		$widget_instances[ $widget_data['id_base'] ] = get_option( 'widget_' . $widget_data['id_base'] );
	}

	foreach ( $data as $sidebar_id => $widgets ) {
		if ( 'wp_inactive_widgets' === $sidebar_id ) {
			continue;
		}

		if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			$sidebar_available    = true;
			$use_sidebar_id       = $sidebar_id;
			$sidebar_message_type = 'success';
			$sidebar_message      = '';

		} else {
			$sidebar_available    = false;
			$use_sidebar_id       = 'wp_inactive_widgets';
			$sidebar_message_type = 'error';
			$sidebar_message      = esc_html__( 'Widget area does not exist in theme (using Inactive)', 'mai-engine' );
		}

		$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id;
		$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
		$results[ $sidebar_id ]['message']      = $sidebar_message;
		$results[ $sidebar_id ]['widgets']      = [];

		foreach ( $widgets as $widget_instance_id => $widget ) {
			$fail    = false;
			$id_base = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
			$widget  = json_decode( wp_json_encode( $widget ), true );

			if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
				$fail                = true;
				$widget_message_type = 'error';
				$widget_message      = esc_html__( 'Site does not support widget', 'mai-engine' );
			}

			if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {
				$sidebars_widgets        = get_option( 'sidebars_widgets' );
				$sidebar_widgets         = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : [];
				$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : [];

				foreach ( $single_widget_instances as $check_id => $check_widget ) {
					if ( in_array( "$id_base-$check_id", $sidebar_widgets, true ) && (array) $widget === $check_widget ) {
						$fail                = true;
						$widget_message_type = 'warning';
						$widget_message      = esc_html__( 'Widget already exists', 'mai-engine' );
						break;
					}
				}
			}

			if ( ! $fail ) {
				$single_widget_instances   = get_option( 'widget_' . $id_base );
				$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : [
					'_multiwidget' => 1,
				];
				$single_widget_instances[] = $widget;

				end( $single_widget_instances );

				$new_instance_id_number = key( $single_widget_instances );

				if ( '0' === strval( $new_instance_id_number ) ) {
					$new_instance_id_number                             = 1;
					$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
					unset( $single_widget_instances[0] );
				}

				if ( isset( $single_widget_instances['_multiwidget'] ) ) {
					$multiwidget = $single_widget_instances['_multiwidget'];
					unset( $single_widget_instances['_multiwidget'] );
					$single_widget_instances['_multiwidget'] = $multiwidget;
				}

				update_option( 'widget_' . $id_base, $single_widget_instances );

				$sidebars_widgets = get_option( 'sidebars_widgets' );

				if ( ! $sidebars_widgets ) {
					$sidebars_widgets = [];
				}

				$new_instance_id = $id_base . '-' . $new_instance_id_number;

				$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id;

				update_option( 'sidebars_widgets', $sidebars_widgets );

				if ( $sidebar_available ) {
					$widget_message_type = 'success';
					$widget_message      = esc_html__( 'Imported', 'mai-engine' );
				} else {
					$widget_message_type = 'warning';
					$widget_message      = esc_html__( 'Imported to Inactive', 'mai-engine' );
				}
			}

			$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base;
			$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : esc_html__( 'No Title', 'mai-engine' );
			$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
			$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;
		}
	}

	return $results;
}
