<?php


/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_demo_content_choices() {
	$choices       = [];
	$content_types = mai_get_demo_data_types();

	foreach ( $content_types as $content_type => $file_type ) {
		$choices[] = [
			'element' => 'input',
			'type'    => 'checkbox',
			'name'    => 'mai-step-content[]',
			'checked' => true,
			'id'      => $content_type,
			'value'   => $content_type,
			'label'   => mai_convert_case( $content_type, 'title' ),
		];
	}

	return $choices;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $file
 *
 * @return void
 */
function mai_import_demo_data_content( $file ) {
	if ( ! class_exists( 'WP_Importer' ) ) {
		require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . '/wp-admin/includes/image.php';
	}

	if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
		require_once ABSPATH . '/wp-admin/includes/media.php';
	}

	$logger   = new \ProteusThemes\WPContentImporter2\WPImporterLogger();
	$importer = new \ProteusThemes\WPContentImporter2\Importer( [ 'fetch_attachments' => true ], $logger );

	$importer->import( $file );
}
