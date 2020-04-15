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

add_action( 'after_setup_theme', 'mai_page_header_customizer_settings' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	$handle          = mai_get_handle();
	$section         = $handle . '-page-header';
	$config          = mai_get_config( 'page-header' );
	$archive_default = [];
	$single_default  = [];
	$archives        = [];
	$singles         = [];

	if ( isset( $config['archive'] ) && ! empty( $config['archive'] && is_array( $config['archive'] ) ) ) {
		$archive_default = array_merge( $archive_default, $config['archive'] );
	}

	if ( isset( $config['single'] ) && ! empty( $config['single']  && is_array( $config['single'] ) ) ) {
		$single_default = array_merge( $single_default, $config['single'] );
	}

	$post_types      = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );
	if ( $post_types ) {
		foreach( $post_types as $name => $post_type ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $name;
			}
			if ( ( '*' === $config ) || ( isset( $config['single'] ) && '*' === $config['single'] ) ) {
				$single_default[] = $name;
			}
			$archives[ $name ] = $post_type->label;
			$singles[ $name ]  = $post_type->label;
		}
	}
	$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
	unset( $taxonomies['post_format'] );
	unset( $taxonomies['yst_prominent_words'] );
	if ( $taxonomies ) {
		foreach( $taxonomies as $name => $taxonomy ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $name;
			}
			$archives[ $name ] = $taxonomy->label;
		}
	}

	\Kirki::add_section(
		$section,
		[
			'title' => __( 'Page Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'multiple'    => 99,
			'settings'    => 'page-header-single',
			'section'     => $section,
			'label'       => __( 'Enable on singular content', 'mai-engine' ),
			'description' => __( 'These settings can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $single_default,
			'choices'     => array_merge(
				$singles,
				[
					'404' => __( '404', 'mai-engine' ),
				]
			),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'multiple'    => 99,
			'settings'    => 'page-header-archive',
			'section'     => $section,
			'label'       => __( 'Enable on content archives', 'mai-engine' ),
			'default'     => $archive_default,
			'choices'     => array_merge(
				$archives,
				[
					'search' => __( 'Search Results', 'mai-engine' ),
					'author' => __( 'Author Archives', 'mai-engine' ),
					'date'   => __( 'Date Archives', 'mai-engine' ),
				],
			),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'image',
			'settings' => 'page-header-image',
			'section'  => $section,
			'label'    => __( 'Default Image', 'mai-engine' ),
			'default'  => '',
			'choices'  => [
				'save_as' => 'id',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'dimensions',
			'settings'    => 'page-header-spacing',
			'section'     => $section,
			'label'       => __( 'Vertical Spacing', 'mai-engine' ),
			'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'default'     => [
				'top'    => '',
				'bottom' => '',
			],
			'choices'     => [
				'top'    => __( 'Top', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
			],
			'output'      => [
				[
					'choice'   => 'top',
					'element'  => ':root',
					'property' => '--page-header-padding-top',
				],
				[
					'choice'   => 'bottom',
					'element'  => ':root',
					'property' => '--page-header-padding-bottom',
				],
			],
			'input_attrs' => [
				'placeholder' => '10vw',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-text-align',
			'section'  => $section,
			'label'    => __( 'Text Alignment', 'mai-engine' ),
			'default'  => '',
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
			'output'   => [
				[
					'choice'   => [ 'start', 'center', 'end' ],
					'element'  => ':root',
					'property' => '--page-header-text-align',
				],
			],
		]
	);
}
