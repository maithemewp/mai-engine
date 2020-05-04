<?php

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_demo_style_choices() {
	$choices = [];
	$demos   = mai_get_theme_demos();

	foreach ( $demos as $demo => $id ) {
		$choices[] = [
			'element' => 'input',
			'type'    => 'radio',
			'name'    => 'mai-step-style',
			'id'      => 'mai-step-style-' . $demo,
			'class'   => 'mai-radio-img-input',
			'checked' => mai_get_chosen_demo_style() === $demo ? true : false,
			'value'   => $demo,
			'label'   => sprintf(
				'<h4>%s</h4>',
				mai_convert_case( mai_get_active_theme() . ' ' . $demo, 'title' )
			),
			'image'   => [
				'src'   => mai_get_demo_screenshot( $demo ),
				'alt'   => 'image',
				'width' => 200,
				'class' => 'mai-step-screenshot',
			],
		];
	}

	return $choices;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function mai_get_chosen_demo_style() {
	$option = get_option( 'mai-setup-wizard' );

	return isset( $option['site_style'] ) ? $option['site_style'] : false;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return bool|mixed
 */
function mai_get_theme_demos() {
	$theme    = mai_get_active_theme();
	$endpoint = 'https://demo.bizbudding.com/sparkle-creative/wp-json/mai-demo-exporter/v2/sites/?theme=' . $theme;
	$request  = wp_remote_get( $endpoint );

	if ( is_wp_error( $request ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $request );

	return json_decode( $body );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param        $demo
 * @param string $theme
 *
 * @return string
 */
function mai_get_demo_screenshot( $demo, $theme = '' ) {
	$theme     = $theme ? $theme : mai_get_active_theme();
	$params    = [
		'w' => 400,
		'h' => 300,
	];
	$url       = urlencode( "https://demo.bizbudding.com/$theme-$demo" );
	$src       = 'http://s.wordpress.com/mshots/v1/' . $url . '?' . http_build_query( $params, null, '&' );
	$cache_key = 'mai_demo_screenshot_' . md5( $src );
	$data_uri  = get_transient( $cache_key );

	if ( ! $data_uri ) {
		$response = wp_remote_get( $src );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$image_data = wp_remote_retrieve_body( $response );
			if ( $image_data && is_string( $image_data ) ) {
				$src = $data_uri = 'data:image/jpeg;base64,' . base64_encode( $image_data );
				set_transient( $cache_key, $data_uri, DAY_IN_SECONDS );
			}
		}
	}

	return esc_attr( $src );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_demo_data_types() {
	return [
		'content'    => 'xml',
		'widgets'    => 'json',
		'customizer' => 'dat',
	];
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $theme
 * @param string $demo
 *
 * @return array
 */
function mai_fetch_demo_data( $theme = 'reach', $demo = 'podcast' ) {
	include_once ABSPATH . 'wp-admin/includes/file.php';
	\WP_Filesystem();
	global $wp_filesystem;
	$cache_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . mai_get_handle() . DIRECTORY_SEPARATOR;

	if ( ! is_dir( $cache_dir ) ) {
		wp_mkdir_p( $cache_dir );
	}

	$response = [];
	$demos    = mai_get_theme_demos();

	if ( ! is_object( $demos ) ) {
		$response['error'] = __( 'Demos is not an object', 'mai-engine' );

		return $response;
	}

	if ( ! isset( $demos->{$demo} ) ) {
		$response['error'] = __( 'Could not find demo ID ', 'mai-engine' );
		$response['error'] .= $demo;

		return $response;
	}

	$id    = $demos->{$demo};
	$types = mai_get_demo_data_types();

	foreach ( $types as $content_type => $file_type ) {
		$file     = "$cache_dir/$content_type.$file_type";
		$url      = "https://demo.bizbudding.com/$theme-$demo/wp-content/uploads/sites/$id/mai-engine/$content_type.$file_type";
		$response = wp_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );

		if ( $body ) {
			$wp_filesystem->put_contents( $file, $body );
		} else {
			$response['error'] = "Could not find $url. Make sure the Mai Demo Exporter plugin is active on the demo site.";
		}
	}

	return $response;
}
