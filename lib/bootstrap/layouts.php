<?php

add_filter( 'genesis_initial_layouts', 'mai_initial_layouts' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_initial_layouts( $url ) {
	return [
		'content-sidebar'         => [
			'label'   => __( 'Content, Sidebar', 'mai-engine' ),
			'img'     => $url . 'cs.gif',
			'default' => is_rtl() ? false : true,
			'type'    => [ 'site' ],
		],
		'sidebar-content'         => [
			'label'   => __( 'Sidebar, Content', 'mai-engine' ),
			'img'     => $url . 'sc.gif',
			'default' => is_rtl() ? true : false,
			'type'    => [ 'site' ],
		],
		'wide-content'      => [
			'label' => __( 'Wide Content', 'mai-engine' ),
			'img'   => $url . 'c.gif',
			'type'  => [ 'site' ],
		],
	];
}
