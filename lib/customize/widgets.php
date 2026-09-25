<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_widgets_customizer_settings' );
/**
 * Adds the widgets customizer settings.
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_widgets_customizer_settings() {
	$config_id  = mai_get_handle();
	$section_id = $config_id . '-widgets';

	new \Kirki\Section(
		$section_id,
		[
			'title' => __( 'Widgets', 'mai-engine' ),
			'panel' => $config_id,
		]
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'widgets-block-editor' ),
				'label'       => esc_html__( 'Use blocks in widget areas', 'mai-engine' ),
				'description' => esc_html__( 'Turn off to use the classic widget screen.', 'mai-engine' ),
				'section'     => $section_id,
				'default'     => mai_get_widgets_block_editor_default(),
			]
		)
	);
}
