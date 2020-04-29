<?php

return [
	[
		'type'     => 'slider',
		'settings' => 'border-radius',
		'label'    => __( 'Border radius', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_variables()['button']['border-radius'] ),
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
