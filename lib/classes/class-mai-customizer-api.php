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
 * Class Mai_Customizer_API
 */
class Mai_Customizer_API {

	/**
	 * Config ID.
	 *
	 * @var string
	 */
	private $handle = '';

	/**
	 * Config paths.
	 *
	 * @var array
	 */
	private $configs = [];

	/**
	 * Panels and sections.
	 *
	 * @var array
	 */
	private $panels = [];

	/**
	 * Mai_Customizer_API constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->handle  = mai_get_handle();
		$this->configs = apply_filters( 'mai_customizer_configs', [ mai_get_dir() . 'config/_settings' ] );
		$this->panels  = apply_filters( 'mai_customizer_panels', $this->get_panels() );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_panels() {
		foreach ( $this->panels as $panel => $sections ) {
			$this->add_panel( $panel );
			$this->add_sections( $panel, $sections );
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
	private function add_panel( $panel ) {
		\Kirki::add_panel(
			"{$this->handle}-{$panel}",
			[
				'title' => mai_convert_case( $panel, 'title' ),
				'panel' => $this->handle,
			]
		);
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param string $panel    Panel name.
	 * @param array  $sections Sections.
	 *
	 * @return void
	 */
	private function add_sections( $panel, array $sections ) {
		foreach ( $sections as $section ) {
			$this->add_section( $panel, $section );
			$this->add_fields( $panel, $section );
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
	private function add_section( $panel, $section ) {
		\Kirki::add_section(
			"{$this->handle}-{$panel}-{$section}",
			[
				'title' => mai_convert_case( $section, 'title' ),
				'panel' => "{$this->handle}-{$panel}",
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
	private function add_fields( $panel, $section ) {
		foreach ( $this->configs as $config ) {
			$path     = str_replace( '//', '/', "{$config}/{$panel}/{$section}.php" );
			$fallback = str_replace( '//', '/', "{$config}/{$panel}.php" );
			$fields   = is_readable( $path ) ? require $path : ( is_readable( $fallback ) ? require $fallback : [] );

			foreach ( $fields as $field ) {
				$this->add_field( $field, $panel, $section );
			}
		}
	}

	/**
	 * Setup the field data from config for kirki add_field method.
	 *
	 * @param array  $field   The field config data.
	 * @param string $panel   The Customizer panel ID.
	 * @param string $section The Customizer section ID.
	 *
	 * @return void
	 */
	private function add_field( $field, $panel, $section ) {
		static $counter = 1;

		// Posts_per_page updates the core option value for post archives. Setting added later because Kirki only saves as associative array.
		if ( 'content-archives' === $panel && 'post' === $section && 'posts_per_page' === $field['settings'] ) {
			return;
		}

		$settings = isset( $field['settings'] ) ? $field['settings'] : '';

		$field['section'] = "{$this->handle}-{$panel}-{$section}";

		if ( $settings ) {
			$field['settings'] = $section . '-' . mai_convert_case( $settings, 'kebab' );
		}

		if ( 'divider' === $field['type'] ) {
			$field['type']     = 'custom';
			$field['default']  = '<hr>';
			$field['settings'] = 'divider-' . $counter;

			$counter++;
		}

		if ( in_array( $panel, [ 'site-layouts', 'content-archives', 'single-content' ], true ) ) {
			$field['option_type'] = 'option';
			$field['option_name'] = "$this->handle[$panel][$section]";
			$field['settings']    = $settings;

			if ( isset( $field['default'] ) && is_string( $field['default'] ) && mai_has_string( 'mai_', $field['default'] ) && is_callable( $field['default'] ) ) {
				$field['default'] = call_user_func_array( $field['default'], [ 'name' => $section ] );
			}

			if ( isset( $field['choices'] ) && is_string( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) && is_callable( $field['choices'] ) ) {
				$field['choices'] = call_user_func_array( $field['choices'], [ 'name' => $section ] );
			}
		}

		if ( isset( $field['sanitize'] ) ) {
			$field['sanitize_callback'] = $field['sanitize'];
		}

		// Set theme color palette for color controls.
		if ( isset( $field['type'] ) && 'color' === $field['type'] ) {
			$field['choices']['palettes'] = array_values( mai_get_colors() );
		}

		// Set theme color palette for multicolor controls.
		if ( isset( $field['type'] ) && 'multicolor' === $field['type'] ) {
			$field['choices']['irisArgs']['palettes'] = array_values( mai_get_colors() );
		}

		// Prevent output if value same as default.
		if ( isset( $field['output'] ) ) {
			foreach ( $field['output'] as $count => $output ) {
				if ( ! isset( $output['exclude'] ) ) {
					$field['output'][ $count ]['exclude'] = [
						$field['default'],
					];
				}
			}
		}

		// Automate editor CSS output for custom properties on root element.
		if ( isset( $field['output'] ) ) {
			foreach ( $field['output'] as $output ) {
				if ( isset( $output['element'] ) && ':root' === $output['element'] ) {
					$args = [
						'element',
						'property',
						'choice',
						'units',
						'prefix',
						'suffix',
						'media_query',
						'exclude',
						'value_pattern',
						'pattern_replace',
					];

					$editor_output['context'] = [ 'editor' ];

					foreach ( $args as $arg ) {
						if ( isset( $output[ $arg ] ) ) {
							$editor_output[ $arg ] = 'element' === $arg ? '.edit-post-visual-editor.editor-styles-wrapper' : $output[ $arg ];
						}
					}

					$field['output'][] = $editor_output;
				}
			}
		}

		// Workaround to fix active callback function with nested options.
		if ( isset( $field['active_callback'] ) ) {
			if ( is_array( $field['active_callback'] ) ) {
				foreach ( $field['active_callback'] as $index => $condition ) {
					foreach ( $condition as $key => $value ) {
						if ( 'setting' === $key ) {
							$field['active_callback'][ $index ][ $key ] = "{$this->handle}[$panel][$section][$value]";
						}

						if ( is_array( $value ) ) {
							foreach ( $value as $nested_key => $nested_value ) {
								if ( 'setting' === $nested_key ) {
									$field['active_callback'][ $index ][ $key ][ $nested_key ] = "{$this->handle}[$panel][$section][$nested_value]";
								}
							}
						}
					}
				}
			}
		}

		\Kirki::add_field( $this->handle, $field );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_panels() {
		return [
			'base-styles'      => [
				'global',
				'body',
				'headings',
				'links',
				'buttons',
				'forms',
				'blockquotes',
			],
			'header'           => [
				'site-header',
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
			'site-layouts'     => [
				'default',
				'archive',
				'single',
			],
			'content-archives' => mai_get_option( 'archive-settings', mai_get_config( 'archive-settings' ), false ),
			'single-content'   => mai_get_option( 'single-settings', mai_get_config( 'single-settings' ), false ),
			'content-area'     => [
				'main-content',
				'breadcrumbs',
				'author-box',
				'featured-image',
				'avatar',
				'sidebar',
				'search-form',
			],
			'footer'           => [
				'site-footer',
				'before-footer',
				'footer-widgets',
				'footer-credits',
			],
		];
	}
}
