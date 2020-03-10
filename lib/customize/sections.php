<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

/**
 * Returns array of default Customizer panels and sections.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_default_sections() {
	return [
		mai_get_handle() => [
			'archive-layout' => __( 'Archive Layout', 'mai-engine' ),
		],
	];
}

add_action( 'genesis_setup', 'mai_add_sections' );
/**
 * Adds Kirki sections.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_sections() {
	$handle = mai_get_handle();
	$panels = apply_filters( 'mai_sections', mai_get_default_sections() );

	foreach ( $panels as $panel => $sections ) {
		$priority = 10;

		foreach ( $sections as $section => $title ) {
			\Kirki::add_section(
				$handle . "_{$panel}_${section}",
				[
					'title'    => $title,
					'panel'    => $handle . "_{$panel}",
					'priority' => $priority,
				]
			);

			$priority = $priority + 10;
		}
	}
}
