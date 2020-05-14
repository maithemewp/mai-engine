<?php

return [
	[
		'type'              => 'text',
		'label'             => __( 'Breakpoint', 'mai-engine' ),
		'description'       => __( 'The largest screen width at which the mobile menu becomes active, in pixels.', 'mai-engine' ),
		'settings'          => 'breakpoint',
		'sanitize_callback' => 'absint',
		'default'           => mai_get_breakpoint(),
	],
];
