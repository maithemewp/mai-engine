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
 * @since 0.3.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	$handle          = mai_get_handle();
	$section         = $handle . '-page-header';
	$config          = mai_get_config( 'page-header' );
	$archives        = mai_get_content_type_choices( $archive = true );
	$singles         = mai_get_content_type_choices( $archive = false );
	$archive_default = [];
	$single_default  = [];

	if ( isset( $config['archive'] ) && ! empty( $config['archive'] && is_array( $config['archive'] ) ) ) {
		$archive_default = $config['archive'];
	}

	if ( isset( $config['single'] ) && ! empty( $config['single'] && is_array( $config['single'] ) ) ) {
		$single_default = $config['single'];
	}

	if ( $archives ) {
		foreach ( $archives as $name => $object ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $name;
			}
		}
	}

	if ( $singles ) {
		foreach ( $singles as $name => $object ) {
			if ( ( '*' === $config ) || ( isset( $config['single'] ) && '*' === $config['single'] ) ) {
				$single_default[] = $name;
			}
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
			'choices'     => $singles,
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
			'choices'  => $archives,
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
			'default'     => $config['spacing'],
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
			'default'  => $config['text-align'],
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
			'type'        => 'image',
			'settings'    => 'page-header-image',
			'section'     => $section,
			'label'       => __( 'Default image', 'mai-engine' ),
			'description' => __( 'This can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $config['image'],
			'choices'     => [
				'save_as' => 'id',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'color',
			'settings'        => 'page-header-background-color',
			'section'         => $section,
			'label'           => esc_html__( 'Default background color', 'mai-engine' ),
			'default'         => $config['background-color'],
			'choices'         => [
				'palettes' => array_values( mai_get_colors() ),
			],
			'output'          => [
				[
					'element'  => '.page-header',
					'property' => '--page-header-background-color',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'slider',
			'settings' => 'overlay-opacity',
			'section'  => $section,
			'label'    => __( 'Overlay opacity', 'mai-engine' ),
			'default'  => $config['overlay-opacity'],
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
			'active_callback' => [
				[
					'setting'  => 'page-header-image',
					'operator' => '!==',
					'value'    => '',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'settings' => 'page-header-text-color',
			'section'  => $section,
			'label'    => esc_html__( 'Default text color', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'default'  => $config['text-color'],
			'choices'  => [
				'light' => __( 'Light', 'mai-engine' ),
				'dark'  => __( 'Dark', 'mai-engine' ),
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
			'default'  => $config['divider'],
			'choices'  => [
				''      => __( 'None', 'mai-engine' ),
				'angle' => __( 'Angle', 'mai-engine' ),
				'curve' => __( 'Curve', 'mai-engine' ),
				'point' => __( 'Point', 'mai-engine' ),
				'round' => __( 'Round', 'mai-engine' ),
				'wave'  => __( 'Wave', 'mai-engine' ),
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-horizontal',
			'section'         => $section,
			'label'           => __( 'Flip divider horizontally', 'mai-engine' ),
			'default'         => $config['divider-flip-horizontal'],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'point',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'round',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-vertical',
			'section'         => $section,
			'label'           => __( 'Flip divider vertically', 'mai-engine' ),
			'default'         => $config['divider-flip-vertical'],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'angle',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'wave',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'color',
			'settings'        => 'page-header-divider-color',
			'section'         => $section,
			'label'           => esc_html__( 'Divider color', 'mai-engine' ),
			'description'     => esc_html__( 'Leave blank to show the page header background', 'mai-engine' ),
			'default'         => $config[ 'divider-color' ],
			'choices'         => [
				'palettes' => array_values( mai_get_colors() ),
			],
			'active_callback' => [
				[
					[
						'setting'  => 'page-header-divider',
						'operator' => '!==',
						'value'    => '',
					],
				]
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

add_filter( 'genesis_attr_page-header', 'mai_add_page_header_content_type_css' );
/**
 * Removes custom properties CSS output when they are the same as the defaults.
 * Skips defaults set in the child theme.
 *
 * @since 0.1.0
 *
 * @param array $attr Kirki CSS output.
 *
 * @return array
 */
function mai_add_page_header_content_type_css( $attr ) {
	$args   = mai_get_template_args();
	$styles = '';

	if ( isset( $args['page-header-background-color'] ) && $args['page-header-background-color'] !== mai_get_color( 'medium' ) ) {
		$styles .= "--background-color:{$args['page-header-background-color']};";
	}

	if ( isset( $args['page-header-overlay-color'] ) && $args['page-header-overlay-color'] !== mai_get_color( 'medium' ) ) {
		$styles .= "--overlay-color:{$args['page-header-overlay-color']};";
	}

	if ( $styles ) {
		$attr['styles'] = $styles;
	}

	return $attr;
}

add_filter( 'body_class', 'mai_add_page_header_text_color_class' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $classes
 *
 * @return mixed
 */
function mai_add_page_header_text_color_class( $classes ) {
	$global = mai_get_option( 'page-header-text-color', 'light' );
	$class  = 'has-page-header-' . $global;
	$args   = mai_get_template_args();

	if ( isset( $args['page-header-text-color'] ) && 'light' === $args['page-header-text-color'] ) {
		$class = 'has-page-header-dark';
	}

	if ( isset( $args['page-header-text-color'] ) && 'dark' === $args['page-header-text-color'] ) {
		$class = 'has-page-header-light';
	}

	$classes[] = $class;

	return $classes;
}
