<?php

$color_default = mai_get_colors();
$color_output  = [];
$color_choices = [];

foreach ( $color_default as $name => $hex ) {
	$color_choices[ $name ] = mai_convert_case( $name, 'title' );
	$color_output[]         = [
		'choice'   => $name,
		'element'  => ':root',
		'property' => "--color-$name",
	];
}

$body_default = $heading_default = [
	'font-family'    => '',
	'variant'        => '',
	// 'font-size'      => '', // Don't use, we have fluid scaling.
	// 'line-height'    => '', // Don't use, we have fluid scaling.
	'letter-spacing' => '',
	'color'          => '',
	'text-transform' => '',
	// 'text-align'     => '', // Don't use, too aggressive.
];

foreach ( mai_get_custom_properties( 'body', true ) as $key => $value ) {
	if ( 'color' === $key ) {
		$body_default[ $key ] = mai_get_color( $value );
	} else {
		$body_default[ $key ] = $value;
	}
}

$heading_output = [];

foreach ( mai_get_custom_properties( 'heading', true ) as $key => $value ) {
	if ( 'color' === $key ) {
		$heading_default[ $key ] = mai_get_color( $value );
	} else {
		$heading_default[ $key ] = $value;
	}

	$heading_output[] = [
		'element'  => ':root',
		'property' => '--heading-' . $key,
		'choice'   => $key,
	];
}

return [
	[
		'type'     => 'typography',
		'settings' => 'body-typography',
		'label'    => esc_html__( 'Body Typography', 'mai-engine' ),
		'default'  => $body_default,
		'output'   => [
			[
				'element' => 'body',
			],
		],
	],
	[
		'type'     => 'typography',
		'settings' => 'heading-typography',
		'label'    => esc_html__( 'Heading Typography', 'mai-engine' ),
		'default'  => $heading_default,
		'output'   => $heading_output,
	],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'input-border-radius',
	// 	'label'    => esc_html__( 'Input border radius', 'mai-engine' ),
	// 	'default'  => mai_get_integer_value( mai_get_custom_property( 'input-border-radius' ) ),
	// 	'choices'  => [
	// 		'min'  => 0,
	// 		'max'  => 100,
	// 		'step' => 1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--input-border-radius',
	// 			'units'    => 'px',
	// 		],
	// 	],
	// ],
	[
		'type'     => 'slider',
		'settings' => 'button-border-radius',
		'label'    => esc_html__( 'Button border radius', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_custom_property( 'button-border-radius' ) ),
		'choices'  => [
			'min'  => 0,
			'max'  => 100,
			'step' => 1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--button-border-radius',
				'units'    => 'px',
			],
		],
	],
	// [
	// 	'type'     => 'multicolor',
	// 	'settings' => 'color-palette',
	// 	'label'    => esc_html__( 'Color palette', 'mai-engine' ),
	// 	'choices'  => $color_choices,
	// 	'default'  => $color_default,
	// 	'output'   => $color_output,
	// ],
	// [
	// 	'type' => 'divider',
	// ],
	[
		'type'         => 'repeater',
		'settings'     => 'color-palette',
		'label'        => esc_html__( 'Color palette', 'mai-engine' ),
		'row_label' => [
			'type'     => 'color',
			'value'    => esc_html__( 'Color', 'mai-engine' ),
		],
		'button_label' => esc_html__( 'Add new color', 'mai-engine' ),
		'fields'       => [
			'color' => [
				'type'    => 'color',
				'label'   => '',
				'default' => '',
			],
		],
		// 'choices'   => $color_choices,
		// 'default'   => $color_default,
		// 'output'    => $color_output,
	],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'base-font-size',
	// 	'label'    => esc_html__( 'Base Font Size', 'mai-engine' ),
	// 	// TODO: Add description?
	// 	'default'  => mai_get_custom_property( 'text-md' ),
	// 	'choices'  => [
	// 		'min'  => 10,
	// 		'max'  => 100,
	// 		'step' => 1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'       => ':root',
	// 			'property'      => '--text-md',
	// 			'value_pattern' => 'calc($ * 1px)',
	// 		],
	// 	],
	// ],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'text-scale-ratio',
	// 	'label'    => esc_html__( 'Text Scale Ratio', 'mai-engine' ),
	// 	// TODO: Add description about what this does.
	// 	'default'  => mai_get_custom_property( 'text-scale-ratio' ),
	// 	'choices'  => [
	// 		'min'  => 0,
	// 		'max'  => 3,
	// 		'step' => 0.1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--text-scale-ratio',
	// 		],
	// 	],
	// ],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'text-responsive-scale',
	// 	'label'    => esc_html__( 'Text Responsive Scale', 'mai-engine' ),
	// 	// TODO: Add description about what this does.
	// 	'default'  => mai_get_custom_property( 'text-responsive-ratio' ),
	// 	'choices'  => [
	// 		'min'  => 0,
	// 		'max'  => 10,
	// 		'step' => 0.1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--text-responsive-scale',
	// 		],
	// 	],
	// ],
	// [
	// 	'type' => 'divider',
	// ],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'border-width',
	// 	'label'    => esc_html__( 'Border width', 'mai-engine' ),
	// 	// TODO: Add description about what elements these settings generally affect.
	// 	'default'  => mai_get_integer_value( mai_get_custom_property( 'border-width' ) ),
	// 	'choices'  => [
	// 		'min'  => 0,
	// 		'max'  => 10,
	// 		'step' => 1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--border-size',
	// 			'units'    => 'px',
	// 		],
	// 	],
	// ],
	// [
	// 	'type'     => 'slider',
	// 	'settings' => 'border-radius',
	// 	'label'    => esc_html__( 'Border radius', 'mai-engine' ),
	// 	// TODO: Add description about what elements these settings generally affect. (entry borders, etc).
	// 	'default'  => mai_get_integer_value( mai_get_custom_property( 'border-radius' ) ),
	// 	'choices'  => [
	// 		'min'  => 0,
	// 		'max'  => 100,
	// 		'step' => 1,
	// 	],
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--border-radius',
	// 			'units'    => 'px',
	// 		],
	// 	],
	// ],
	// [
	// 	'type'     => 'color',
	// 	'settings' => 'border-color',
	// 	'label'    => esc_html__( 'Border Color', 'mai-customizer' ),
	// 	// TODO: Add description about what elements these settings generally affect.
	// 	'default'  => mai_get_color( 'lightest' ),
	// 	'output'   => [
	// 		[
	// 			'element'  => ':root',
	// 			'property' => '--border-color',
	// 		],
	// 	],
	// ],
];
