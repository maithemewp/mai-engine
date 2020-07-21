<?php

return [
	'demos'         => [
		// 'medical'   => 15,
		'corporate' => 16,
		// 'author'    => 17,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f6f8fa',
			'body'      => '#4d5968',
			'heading'   => '#2a3139',
			'link'      => '#0072ff',
			'primary'   => '#009cff',
			'secondary' => '#00c6ff',
		],
		'fonts'  => [
			'body'    => 'Nunito Sans:400,700',
			'heading' => 'Nunito Sans:700',
		],
		'extra'  => [
			'border-radius' => '9rem',
		],
	],
	'theme-support' => [
		'add' => [
			'editor-gradient-presets' => [
				[
					'name'     => __( 'Corporate', 'mai-engine' ),
					'gradient' => 'linear-gradient(135deg,#00c6ff 0%,#0072ff 100%)',
					'slug'     => 'corporate',
				],
			],
		],
	],
];
