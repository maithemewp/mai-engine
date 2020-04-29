<?php

$color_default = mai_get_colors();
$color_output  = [];
$color_choices = [
	'irisArgs' => [
		'palettes' => array_values( $color_default ),
	],
];

foreach ( $color_default as $name => $hex ) {
	$color_choices[ $name ] = mai_convert_case( $name, 'title' );
	$color_output[]         = [
		'choice'   => $name,
		'element'  => ':root',
		'property' => "--color-$name",
	];
	$color_output[]         = [
		'choice'   => $name,
		'element'  => '.edit-post-visual-editor.editor-styles-wrapper',
		'property' => "--color-$name",
		'context'  => [ 'editor' ],
	];
}

return [
	[
		'type'     => 'multicolor',
		'settings' => 'color-palette',
		'label'    => __( 'Color palette', 'kirki' ),
		'choices'  => $color_choices,
		'default'  => $color_default,
		'output'   => $color_output,
	],
	[
		'type' => 'divider',
	],
	[
		'type'     => 'slider',
		'settings' => 'border-radius',
		'label'    => __( 'Border radius', 'mai-engine' ),
		'default'  => mai_get_variables()['border']['radius'],
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
