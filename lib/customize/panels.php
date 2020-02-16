<?php

/**
 * Returns array of default Customizer panels.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_default_panels() {
	return [
		mai_get_handle() => mai_get_name(),
	];
}

add_action( 'genesis_setup', 'mai_add_panels', 20 );
/**
 * Adds Kirki panels.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_panels() {
	$priority = 10;
	$handle   = mai_get_handle();
	$panels   = apply_filters( 'mai_panels', mai_get_default_panels() );

	foreach ( $panels as $panel => $title ) {
		\Kirki::add_panel( $handle . "_{$panel}", [
			'title'    => $title,
			'priority' => $priority + 10,
			'panel'    => $handle,
		] );
	}
}
