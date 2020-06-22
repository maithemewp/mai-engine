<?php

return [
	[
		'type'     => 'slider',
		'settings' => 'input-border-radius',
		'label'    => __( 'Input border radius', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_custom_property( 'input-border-radius' ) ),
		'choices'  => [
			'min'  => 0,
			'max'  => 100,
			'step' => 1,
		],
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--input-border-radius',
				'units'    => 'px',
			],
		],
	],
	[
		'type'     => 'slider',
		'settings' => 'button-border-radius',
		'label'    => __( 'Button border radius', 'mai-engine' ),
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
];
