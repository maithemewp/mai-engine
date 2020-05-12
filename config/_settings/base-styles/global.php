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
		'settings' => 'border-width',
		'label'    => __( 'Border width', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_variables()['border']['width'] ),
		'choices'  => [
			'min'  => 0,
			'max'  => 10,
			'step' => 1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--border-width',
				'units'    => 'px',
			],
		],
	],
	[
		'type'     => 'slider',
		'settings' => 'border-radius',
		'label'    => __( 'Border radius', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_variables()['border']['radius'] ),
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
];
