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

add_action( 'init', 'mai_page_header_customizer_settings' );
/**
 * Add page header customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	$handle          = mai_get_handle();
	$section         = $handle . '-page-header';
	$config          = mai_get_config( 'page-header' );
	$defaults        = $config['customizer'];
	$archive_default = [];
	$single_default  = [];
	$archives        = [];
	$singles         = [];

	if ( isset( $config['archive'] ) && ! empty( $config['archive'] && is_array( $config['archive'] ) ) ) {
		$archive_default = $config['archive'];
	}

	if ( isset( $config['single'] ) && ! empty( $config['single'] && is_array( $config['single'] ) ) ) {
		$single_default = $config['single'];
	}

	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );

	if ( $post_types ) {
		foreach ( $post_types as $post_type => $object ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $post_type;
			}

			if ( ( '*' === $config ) || ( isset( $config['single'] ) && '*' === $config['single'] ) ) {
				$single_default[] = $post_type;
			}

			if ( $object->has_archive ) {
				$archives[ $post_type ] = mai_convert_case( $post_type, 'title' );
			}
			$singles[ $post_type ]  = mai_convert_case( $post_type, 'title' );
		}
	}

	$taxonomies = get_taxonomies( [ 'public' => true ] );

	// Remove taxonomies we don't want.
	unset( $taxonomies['post_format'] );
	unset( $taxonomies['product_shipping_class'] );
	unset( $taxonomies['yst_prominent_words'] );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy => $name ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $taxonomy;
			}
			$archives[ $taxonomy ] = mai_convert_case( $taxonomy, 'title' );
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
			'type'        => 'multicheck',
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
			'type'     => 'multicheck',
			'settings' => 'page-header-archive',
			'section'  => $section,
			'label'    => __( 'Enable on content archives', 'mai-engine' ),
			'default'  => $archive_default,
			'choices'  => array_merge(
				$archives,
				[
					'search' => __( 'Search Results', 'mai-engine' ),
					'author' => __( 'Author Archives', 'mai-engine' ),
					'date'   => __( 'Date Archives', 'mai-engine' ),
				]
			),
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
			'default'     => $defaults['spacing'],
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
			'default'  => $defaults['text-align'],
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

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'page-header-divider',
			'section'  => $section,
			'label'    => __( 'Divider style', 'mai-engine' ),
			'default'  => $defaults['divider'],
			'choices'  => [
				'none'  => __( 'None', 'mai-engine' ),
				'angle' => __( 'Angle', 'mai-engine' ),
				'curve' => __( 'Curve', 'mai-engine' ),
				'wave'  => __( 'Wave', 'mai-engine' ),
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'slider',
			'settings' => 'page-header-overlay-opacity',
			'section'  => $section,
			'label'    => __( 'Overlay opacity', 'mai-engine' ),
			'default'  => $defaults['overlay-opacity'],
			'choices'  => [
				'min'  => 0,
				'max'  => 1,
				'step' => 0.01,
			],
			'output'   => [
				[
					'element'  => '.page-header-overlay',
					'property' => 'opacity',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'image',
			'settings'    => 'page-header-image',
			'section'     => $section,
			'label'       => __( 'Default Image', 'mai-engine' ),
			'description' => __( 'This can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => '',
			'choices'     => [
				'save_as' => 'id',
			],
		]
	);
}

add_filter( 'genesis_attr_page-header-overlay', 'mai_page_header_divider_class', 10, 1 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $attr
 *
 * @return array
 */
function mai_page_header_divider_class( $attr ) {
	$option = mai_get_option( 'page-header-divider', 'none' );

	if ( 'none' !== $option ) {
		$attr['class'] .= " has-$option-divider";
	}

	return $attr;
}
