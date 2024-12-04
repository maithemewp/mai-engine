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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_performance_customizer_settings' );
/**
 * Add performance customizer settings.
 *
 * @since 2.4.0 Moved defaults to config.
 * @since 1.0.0
 *
 * @return  void
 */
function mai_performance_customizer_settings() {
	$config_id  = mai_get_handle();
	$section_id = $config_id . '-performance';
	$defaults   = mai_get_config( 'settings' )['performance'];

	new \Kirki\Section(
		$section_id,
		[
			'title' => __( 'Performance', 'mai-engine' ),
			'panel' => $config_id,
		]
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'genesis-style-trump' ),
				'label'    => esc_html__( 'Load child theme stylesheet in footer', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['genesis-style-trump'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'preload-fonts' ),
				'label'    => esc_html__( 'Preload heading and body font files', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['preload-fonts'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'remove-menu-item-classes' ),
				'label'    => esc_html__( 'Remove menu item id and additional classes', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['remove-menu-item-classes'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'remove-template-classes' ),
				'label'    => esc_html__( 'Remove additional page template body classes', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['remove-template-classes'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'disable-emojis' ),
				'label'    => esc_html__( 'Disable emojis', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['disable-emojis'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'type'     => 'checkbox',
				'settings' => mai_get_kirki_setting( 'remove-recent-comments-css' ),
				'label'    => esc_html__( 'Remove recent comments CSS', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['remove-recent-comments-css'],
			]
		)
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'type'     => 'checkbox',
				'settings' => mai_get_kirki_setting( 'remove-jquery-migrate' ),
				'label'    => esc_html__( 'Remove jQuery Migrate script', 'mai-engine' ),
				'section'  => $section_id,
				'default'  => $defaults['remove-jquery-migrate'],
			]
		)
	);

	// new \Kirki\Field\Checkbox(
	// 	mai_parse_kirki_args(
	// 		[
	// 			'type'     => 'checkbox',
	// 			'settings' => mai_get_kirki_setting( 'remove-global-styles' ),
	// 			'label'    => esc_html__( 'Remove unused global styles and inline svgs', 'mai-engine' ),
	// 			'section'  => $section_id,
	// 			'default'  => $defaults['remove-global-styles'],
	// 		]
	// 	)
	// );
}
