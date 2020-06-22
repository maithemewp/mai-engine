<?php

$button_elements = [
	'.button',
	'[type=button]',
	'[type=reset]',
	'[type=submit]',
	'button',
];

$button_elements_hover = [];

foreach ( $button_elements as $button_element ) {
	$button_elements_hover[] = $button_element . ':hover';
	$button_elements_hover[] = $button_element . ':focus';
}

return [
	[
		'settings' => 'button-hover-color',
		'label'    => __( 'Button Hover Color', 'mai-engine' ),
		'type'     => 'radio-buttonset',
		'default'  => 'dark',
		'choices'  => [
			'dark'  => __( 'Dark', 'mai-engine' ),
			'light' => __( 'Light', 'mai-engine' ),
		],
	],
	[
		'type'     => 'color',
		'settings' => 'primary-background',
		'label'    => __( 'Primary Background', 'mai-customizer' ),
		'default'  => mai_get_color( 'primary' ),
		'output'   => [
			[
				'element'       => $button_elements,
				'property'      => 'background',
				'value_pattern' => 'var(--button-background, $)',
			],
			[
				'element'       => $button_elements_hover,
				'property'      => 'background',
				'value_pattern' => sprintf(
					'var(--button-background-hover, %s)',
					mai_get_color_variant( mai_get_option( 'buttons-primary-background', mai_get_color( 'primary' ) ), mai_get_option( 'buttons-hover-color', 'dark' ) )
				),
			],
		],
	],
	[
		'type'     => 'color',
		'settings' => 'secondary-background',
		'label'    => __( 'Secondary Background', 'mai-customizer' ),
		'default'  => mai_get_color( 'secondary' ),
		'output'   => [
			[
				'element'  => '.button-secondary',
				'property' => 'background',
			],
			[
				'element'       => [
					'.button-secondary:hover',
					'.button-secondary:focus',
				],
				'property'      => 'background',
				'value_pattern' => sprintf(
					'var(--button-background-hover, %s)',
					mai_get_color_variant( mai_get_option( 'buttons-secondary-background', mai_get_color( 'secondary' ) ), mai_get_option( 'buttons-hover-color', 'dark' ) )
				),
			],
		],
	],
	[
		'type'     => 'color',
		'settings' => 'outline-color',
		'label'    => __( 'Outline Button Color', 'mai-customizer' ),
		'default'  => mai_get_color( 'primary' ),
		'output'   => [
			[
				'element'  => '.button-outline',
				'property' => 'color',
			],
			[
				'element'       => '.button-outline',
				'property'      => 'background-color',
				'value_pattern' => 'transparent',
			],
			[
				'element'  => [
					'.button-outline:hover',
					'.button-outline:focus',
				],
				'property' => 'background-color',
			],
			[
				'element'  => [
					'.button-outline:hover',
					'.button-outline:focus',
				],
				'property' => 'border-color',
			],
		],
	],
];
