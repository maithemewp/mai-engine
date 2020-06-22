<?php

$config  = mai_get_custom_properties( 'body', true );
$default = [];
$output  = [];

foreach ( $config as $key => $value ) {
	if ( 'color' === $key ) {
		$default[ $key ] = mai_get_color( $value );
	} else {
		$default[ $key ] = $value;
	}
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
