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
				'settings',
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
			'content-archives' => mai_get_config( 'loop' )['archive'],
			'single-content'   => mai_get_config( 'loop' )['single'],
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
				'settings',
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
			// mai_add_customizer_sections( $panel, $sections );
		} else {
			// mai_add_customizer_sections( $handle, $panel );
		}
		mai_add_customizer_sections( $panel, $sections );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $panel Panel name.
 *
 * @return void
 */
function mai_add_customizer_panel( $panel ) {
	$handle = mai_get_handle();

	\Kirki::add_panel(
		"{$handle}-{$panel}",
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
 * @param string $panel    Panel name.
 * @param mixed  $sections Sections.
 *
 * @return void
 */
function mai_add_customizer_sections( $panel, $sections ) {
	foreach ( (array) $sections as $section ) {
		mai_add_customizer_section( $panel, $section );
		mai_add_customizer_fields( $panel, $section );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $panel   Panel name.
 * @param string $section Section name.
 *
 * @return void
 */
function mai_add_customizer_section( $panel, $section ) {
	$handle     = mai_get_handle();
	$panel_id   = "{$handle}-{$panel}";
	$section_id = "{$panel_id}-{$section}";
// vd( 'panel_id: ' . $panel_id );
// vd( 'section_id: ' . $section_id );
// vd( 'panel: ' . $panel );
// vd( 'section: ' . $section );
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
 * @param string $panel   Panel name.
 * @param string $section Section name.
 *
 * @return void
 */
function mai_add_customizer_fields( $panel, $section ) {
	$configs = mai_get_customizer_configs();
// $panel   = mai_get_handle() === $panel ? '' : $panel;
// vd( mai_get_handle() === $panel );
// vd( 'panel: ' . $panel );
// vd( 'section: ' . $section );

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
 * @param array  $field   Field data.
 * @param string $panel   Panel name.
 * @param string $section Section name.
 *
 * @return void
 */
function mai_add_customizer_field_og( $field, $panel, $section ) {
	static $counter = 1;

	$handle     = mai_get_handle();
	$panel_id   = "{$handle}-{$panel}";
	$section_id = "{$panel_id}-{$section}";
	// $field['section'] = $section_id;

	// if ( isset( $field['settings'] ) ) {
	// 	$field['settings'] = $panel . '[' . $section . '][' . $field['settings'] . ']';
	// }

	$data = mai_get_kirki_field_data( $field, $section_id );

	if ( 'divider' === $field['type'] ) {
		$field['type']     = 'custom';
		$field['settings'] = 'divider-' . $counter++;
		$field['default']  = '<hr>';
	}

	\Kirki::add_field( $handle, $field );
}

/**
 * Setup the field data from config for kirki add_field method.
 *
 * @param  array  $field   The field config data.
 * @param  string $section The Customizer section ID.
 * @param  string $name    The post or content type name.
 *
 * @return array The field data.
 */
function mai_add_customizer_field( $field, $panel, $section, $name = '' ) {
	static $counter = 1;

	$handle      = mai_get_handle();
	$panel_id    = "{$handle}-{$panel}";
	$section_id  = "{$panel_id}-{$section}";
	$option_name = $handle . '[' . $panel . '][' . $section . '][' . $field['settings'] . ']';

	$data = [
		'type'        => $field['type'],
		'label'       => $field['label'],
		'settings'    => $field['settings'],
		'section'     => $section_id,
		'priority'    => 10,
		'option_type' => 'option',
		'option_name' => $option_name,
	];

	// Maybe add description.
	if ( isset( $field['description'] ) ) {
		$data['description'] = $field['description'];
	}

	// Maybe add attributes.
	if ( isset( $field['atts'] ) ) {
		foreach ( $field['atts'] as $key => $value ) {
			$data[ $key ] = $value;
		}
	}

	// Maybe add conditional logic.
	if ( isset( $field['active_callback'] ) ) {
		if ( is_array( $field['active_callback'] ) ) {
			foreach ( $field['active_callback'] as $index => $condition ) {
				foreach ( $condition as $key => $value ) {
					if ( 'setting' === $key ) {
						$field['active_callback'][ $index ][ $key ] = "{$data['option_name']}[$value]";
					}
				}
			}
		}

		$data['active_callback'] = $field['active_callback'];
	}

	// Maybe add default.
	if ( isset( $field['default'] ) ) {
		if ( is_array( $field['default'] ) ) {
			$data['default'] = $field['default'];
		} elseif ( is_string( $field['default'] ) && is_callable( $field['default'] ) && mai_has_string( 'mai_', $field['default'] ) ) {
			$data['default'] = call_user_func_array( $field['default'], [ 'name' => $name ] );
		} else {
			$data['default'] = $field['default'];
		}
	}

	// Maybe get choices.
	if ( isset( $field['choices'] ) ) {
		if ( is_array( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];
		} elseif ( is_callable( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) ) {
			$data['choices'] = call_user_func_array( $field['choices'], [ 'name' => $name ] );
		}
	}

	// Maybe add custom sanitize function.
	if ( isset( $field['sanitize'] ) ) {
		$data['sanitize_callback'] = $field['sanitize'];
	}

	if ( 'divider' === $field['type'] ) {
		$data['type']     = 'custom';
		$data['settings'] = 'divider-' . $counter++;
		$data['default']  = '<hr>';
	}

	// vd( $data );

	\Kirki::add_field( $handle, $data );
	// return $data;
}
