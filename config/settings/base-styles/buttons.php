<?php

return [
	[
		'type'     => 'color',
		'settings' => 'overlay-color',
		'label'    => __( 'Overlay Color', 'mai-customizer' ),
		'default'  => mai_get_color( 'darkest' ),
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--button-overlay-color',
			],
		],
	],
	[
		'type'     => 'color',
		'settings' => 'background-color',
		'label'    => __( 'Background Color', 'mai-customizer' ),
		'default'  => mai_get_color( 'primary' ),
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--button-overlay-color',
			],
		],
	],
	[
		'type'     => 'color',
		'settings' => 'color',
		'label'    => __( 'Text Color', 'mai-customizer' ),
		'default'  => mai_get_color( 'lightest' ),
		'output'   => [
			[
				'element'  => ':root',
				'property' => '--button-overlay-color',
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
				'property' => '--button-border-radius',
				'units'    => 'px',
			],
		],
	],
];
