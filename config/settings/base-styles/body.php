<?php

$config  = mai_get_variables()['body'];
$default = [];
$output  = [];

foreach ( $config as $key => $value ) {
	if ( 'color' === $key ) {
		$default[ $key ] = mai_get_color( $value );
	} else {
		$default[ $key ] = $value;
	}

	$output[] = [
		'element'  => ':root',
		'property' => '--body-' . $key,
		'choice'   => $key,
	];
}

return [
	[
		'type'     => 'typography',
		'settings' => 'body-typography',
		'label'    => __( 'Typography', 'mai-engine' ),
		'default'  => $default,
		'output'   => [
			[
				'element' => 'body',
			],
		],
	],
];
