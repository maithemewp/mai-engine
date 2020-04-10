<?php

return [
	[
		'type'     => 'checkbox',
		'settings' => 'sticky',
		'label'    => __( 'Enable sticky header?', 'mai-engine' ),
		'default'  => current_theme_supports( 'sticky-header' ),
	],
	[
		'type'     => 'checkbox',
		'settings' => 'transparent',
		'label'    => __( 'Enable transparent header?', 'mai-engine' ),
		'default'  => current_theme_supports( 'transparent-header' ),
	],
];
