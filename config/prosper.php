<?php

return [
	'demos'         => [
		'corporate' => 'https://demo.bizbudding.com/prosper-corporate/wp-content/uploads/sites/16/mai-engine/',
		// 'medical'   => 15,
		// 'corporate' => 16,
		// 'author'    => 17,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f6f8fa',
			'body'      => '#4d5968',
			'heading'   => '#2a3139',
			'link'      => '#0072ff',
			'primary'   => '#009cff',
			'secondary' => '#c6cace',
		],
		'fonts'  => [
			'body'    => 'Nunito Sans:400,700',
			'heading' => 'Nunito Sans:700',
		],
	],
	'theme-support' => [
		'add' => [
			'editor-gradient-presets' => [
				[
					'name'     => __( 'Corporate', 'mai-engine' ),
					'gradient' => 'linear-gradient(to bottom right, var(--color-primary) 0%, var(--color-link) 100%)',
					'slug'     => 'corporate',
				],
			],
		],
	],
	'settings'      => [
		'page-header' => [
			'archive'          => '*',
			'single'           => '*',
			'image'            => '',
			'background-color' => 'heading',
			'text-color'       => 'light',
			'divider-color'    => 'white',
			'spacing'          => [
				'top'    => '5vw',
				'bottom' => '5vw',
			],
		],
	],
];
