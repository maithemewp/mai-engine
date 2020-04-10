<?php

return [
	[
		'type'     => 'radio-buttonset',
		'settings' => 'alignment',
		'label'    => __( 'Alignment', 'mai-engine' ),
		'default'  => 'start',
		'choices'  => [
			'flex-start'  => __( 'Left', 'mai-engine' ),
			'center' => __( 'Center', 'mai-engine' ),
			'flex-end'    => __( 'Right', 'mai-engine' ),
		],
		'output' => [
			[
				'element'  => '.nav-after-header',
				'property' => '--menu-justify-content',
			],
		],
	],
];
