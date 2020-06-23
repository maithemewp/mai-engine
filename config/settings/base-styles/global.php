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

return [
	[
		'type'     => 'multicolor',
		'settings' => 'color-palette',
		'label'    => __( 'Color palette', 'mai-engine' ),
		'choices'  => $color_choices,
		'default'  => $color_default,
		'output'   => $color_output,
	],
	[
		'type' => 'divider',
	],
	[
		'type'     => 'slider',
		'settings' => 'base-font-size',
		'label'    => __( 'Base Font Size', 'mai-engine' ),
		// TODO: Add description?
		'default'  => mai_get_custom_property( 'text-md' ),
		'choices'  => [
			'min'  => 10,
			'max'  => 100,
			'step' => 1,
		],
		'output'   => [
			[
				'element'       => ':root',
				'property'      => '--text-md',
				'value_pattern' => 'calc($ * 1px)',
			],
		],
	],
	[
		'type'     => 'slider',
		'settings' => 'text-scale-ratio',
		'label'    => __( 'Text Scale Ratio', 'mai-engine' ),
		// TODO: Add description about what this does.
		'default'  => mai_get_custom_property( 'text-scale-ratio' ),
		'choices'  => [
			'min'  => 0,
			'max'  => 3,
			'step' => 0.1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--text-scale-ratio',
			],
		],
	],
	[
		'type'     => 'slider',
		'settings' => 'text-responsive-scale',
		'label'    => __( 'Text Responsive Scale', 'mai-engine' ),
		// TODO: Add description about what this does.
		'default'  => mai_get_custom_property( 'text-responsive-ratio' ),
		'choices'  => [
			'min'  => 0,
			'max'  => 10,
			'step' => 0.1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--text-responsive-scale',
			],
		],
	],
	[
		'type' => 'divider',
	],
	[
		'type'     => 'slider',
		'settings' => 'border-width',
		'label'    => __( 'Border width', 'mai-engine' ),
		// TODO: Add description about what elements these settings generally affect.
		'default'  => mai_get_integer_value( mai_get_custom_property( 'border-width' ) ),
		'choices'  => [
			'min'  => 0,
			'max'  => 10,
			'step' => 1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--border-size',
				'units'    => 'px',
			],
		],
	],
	[
		'type'     => 'slider',
		'settings' => 'border-radius',
		'label'    => __( 'Border radius', 'mai-engine' ),
		// TODO: Add description about what elements these settings generally affect. (entry borders, etc).
		'default'  => mai_get_integer_value( mai_get_custom_property( 'border-radius' ) ),
		'choices'  => [
			'min'  => 0,
			'max'  => 100,
			'step' => 1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--border-radius',
				'units'    => 'px',
			],
		],
	],
	[
		'type'     => 'color',
		'settings' => 'border-color',
		'label'    => __( 'Border Color', 'mai-customizer' ),
		// TODO: Add description about what elements these settings generally affect.
		'default'  => mai_get_color( 'lightest' ),
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--border-color',
			],
		],
	],
];
