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
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_customizer_sections() {
	return apply_filters(
		'mai_customizer_sections',
		[
			'base-styles'      => [
				'global',
				'body',
				'headings',
				'links',
				'buttons',
				'inputs',
				'blockquotes',
				'code',
				'lists',
				'tables',
			],
			'site-header'      => [
				'header-settings',
				'title-area',
				'header-left',
				'header-right',
			],
			'navigation-menus' => [
				'header-left',
				'header-right',
				'after-header',
				'mobile-menu',
				'menu-toggle',
				'sub-menu',
				'sub-menu-toggle',
			],
			'page-header'      => [],
			'content-area'     => [
				'main-content',
				'breadcrumbs',
				'author-box',
				'featured-image',
				'avatar',
				'sidebar',
				'search-form',
			],
			'site-footer'      => [
				'site-footer',
				'before-footer',
				'footer-widgets',
				'footer-credits',
			],
		]
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_customizer_configs() {
	return apply_filters(
		'mai_customizer_configs',
		[
			mai_get_dir() . 'config/_default/settings',
			mai_get_dir() . 'config/' . mai_get_active_theme() . '/settings',
		]
	);
}

add_action( 'after_setup_theme', 'mai_add_customizer_panels' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_customizer_panels() {
	$handle = mai_get_handle();
	$panels = mai_get_customizer_sections();

	foreach ( $panels as $panel => $sections ) {
		if ( $sections ) {
			mai_add_customizer_panel( $panel );
			mai_add_customizer_sections( $panel, $sections );

		} else {
			mai_add_customizer_sections( $handle, $sections );
		}
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $panel
 *
 * @return void
 */
function mai_add_customizer_panel( $panel ) {
	$handle = mai_get_handle();

	\Kirki::add_panel(
		$handle . "-{$panel}",
		[
			'title' => mai_convert_case( $panel, 'title' ),
			'panel' => $handle,
		]
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $panel
 * @param $sections
 *
 * @return void
 */
function mai_add_customizer_sections( $panel, $sections ) {
	$sections = is_array( $sections ) ? $sections : $sections = [ $sections ];

	foreach ( $sections as $section ) {
		mai_add_customizer_section( $panel, $section );
		mai_add_customizer_fields( $panel, $section );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $panel
 * @param $section
 *
 * @return void
 */
function mai_add_customizer_section( $panel, $section ) {
	$handle     = mai_get_handle();
	$panel_id   = $handle === $panel ? $panel : "{$handle}-{$panel}";
	$section_id = $handle === $panel ? "{$handle}-{$section}" : "{$panel_id}-{$section}";

	\Kirki::add_section(
		$section_id,
		[
			'title' => mai_convert_case( $section, 'title' ),
			'panel' => $panel_id,
		]
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $panel
 * @param $section
 *
 * @return void
 */
function mai_add_customizer_fields( $panel, $section ) {
	$configs = mai_get_customizer_configs();
	$panel   = mai_get_handle() === $panel ? '' : $panel;

	foreach ( $configs as $config ) {
		$path   = str_replace( '//', '/', "{$config}/{$panel}/{$section}.php" );
		$fields = is_readable( $path ) ? require $path : [];

		foreach ( $fields as $field ) {
			mai_add_customizer_field( $field, $panel, $section );
		}
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $field
 * @param $panel
 * @param $section
 *
 * @return void
 */
function mai_add_customizer_field( $field, $panel, $section ) {
	static $counter = 1;

	$handle     = mai_get_handle();
	$panel_id   = $handle . "-{$panel}";
	$section_id = str_replace(
		'--',
		'-',
		"{$panel_id}-{$section}"
	);

	$field['section'] = $section_id;

	$settings = false;

	if ( isset( $field['settings'] ) ) {
		$settings = $field['settings'];
	} elseif ( isset( $field['name'] ) ) {
		$settings = $field['name'];
	}

	$field['settings'] = $panel . '-' . $section . '-' . $settings;

	if ( 'divider' === $field['type'] ) {
		$field['type']     = 'custom';
		$field['settings'] = 'divider-' . $counter++;
		$field['default']  = '<hr>';
	}

	\Kirki::add_field( $handle, $field );
}
