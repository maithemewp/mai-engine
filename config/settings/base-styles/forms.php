<?php

return [
	[
		'type'     => 'slider',
		'settings' => 'input-border-radius',
		'label'    => __( 'Input border radius', 'mai-engine' ),
		'default'  => mai_get_integer_value( mai_get_variables()['border']['radius'] ),
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
];
