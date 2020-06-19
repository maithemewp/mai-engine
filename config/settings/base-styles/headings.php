<?php


$config  = mai_get_variables()['heading'];
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
		'property' => '--heading-' . $key,
		'choice'   => $key,
	];
}

return [
	[
		'type'     => 'typography',
		'settings' => 'heading-typography',
		'label'    => __( 'Typography', 'mai-engine' ),
		'default'  => $default,
		'output'   => $output,
	],
];
