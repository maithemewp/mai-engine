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

add_action( 'init', 'mai_site_layouts_customizer_settings', 99 );
/**
 * Add base styles customizer settings.
 *
 * @since 2.4.0 Moved defaults to config.
 * @since 0.3.0
 *
 * @return void
 */
function mai_site_layouts_customizer_settings() {
	$handle   = mai_get_handle();
	$name     = 'site-layouts';
	$defaults = mai_get_config( 'settings' )['site-layouts'];
	$section  = sprintf( '%s-%s', $handle, $name );
	$options  = [
		'default' => '[site-layouts][default]',
		'archive' => '[site-layouts][archive]',
		'single'  => '[site-layouts][single]',
	];

	new \Kirki\Section(
		$section,
		[
			'title' => __( 'Site Layouts', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'boxed-container' ),
				'section'  => $section,
				'label'    => __( 'Enable boxed site container', 'mai-engine' ),
				'default'  => current_theme_supports( 'boxed-container' ),
			]
		)
	);

	new \Kirki\Field\Custom(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'defaults-layout-divider', $options['default'] ),
				'section'  => $section,
				'default'  => sprintf( '<h3>%s</h3>', __( 'Defaults', 'mai-engine' ) ),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'site', $options['default'] ),
				'section'  => $section,
				'label'    => __( 'Site Default', 'mai-engine' ),
				'default'  => $defaults['default']['site'],
				'choices'  => genesis_get_layouts_for_customizer(),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'archive', $options['default'] ),
				'section'  => $section,
				'label'    => __( 'Content Archives', 'mai-engine' ),
				'default'  => $defaults['default']['archive'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'single', $options['default'] ),
				'section'  => $section,
				'label'    => __( 'Single Content', 'mai-engine' ),
				'default'  => $defaults['default']['single'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);

	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );

	foreach ( $post_types as $name => $post_type ) {

		new \Kirki\Field\Custom(
			mai_parse_kirki_args(
				[
					'settings' => mai_get_kirki_setting( $name . '-layout-divider', $options['default'] ),
					'section'  => $section,
					'default'  => sprintf( '<h3>%s</h3>', $post_type->label ),
				]
			)
		);

		new \Kirki\Field\Select(
			mai_parse_kirki_args(
				[
					'settings' => mai_get_kirki_setting( $name, $options['single'] ),
					'section'  => $section,
					'label'    => __( 'Single', 'mai-engine' ),
					'default'  => isset( $defaults['single'][ $name ] ) ? $defaults['single'][ $name ]: '',
					'choices'  => mai_get_site_layout_choices(),
				]
			)
		);

		if ( 'post' === $name || $post_type->has_archive ) {

			new \Kirki\Field\Select(
				mai_parse_kirki_args(
					[
						'settings' => mai_get_kirki_setting( $name, $options['archive'] ),
						'section'  => $section,
						'label'    => __( 'Archive', 'mai-engine' ),
						'default'  => isset( $defaults['archive'][ $name ] ) ? $defaults['archive'][ $name ]: '',
						'choices'  => mai_get_site_layout_choices(),
					]
				)
			);
		}

		$taxonomies = get_object_taxonomies( $name, 'objects' );

		if ( $taxonomies ) {
			// Get only public taxonomies.
			$taxonomies = wp_list_filter( $taxonomies, [ 'public' => true ] );
			// Remove taxonomies we don't want.
			unset( $taxonomies['post_format'] );
			unset( $taxonomies['product_shipping_class'] );
			unset( $taxonomies['yst_prominent_words'] );
		}

		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxo_name => $taxonomy ) {

				new \Kirki\Field\Select(
					mai_parse_kirki_args(
						[
							'settings' => mai_get_kirki_setting( $taxo_name, $options['archive'] ),
							'section'  => $section,
							'label'    => $taxonomy->label,
							'default'  => isset( $defaults['archive'][ $taxo_name ] ) ? $defaults['archive'][ $taxo_name ]: '',
							'choices'  => mai_get_site_layout_choices(),
						]
					)
				);
			}
		}
	}

	new \Kirki\Field\Custom(
		mai_parse_kirki_args(
			[
				'type'     => 'custom',
				'settings' => mai_get_kirki_setting( 'misc-layout-divider', $options['default'] ),
				'section'  => $section,
				'default'  => sprintf( '<h3>%s</h3>', __( 'Miscellaneous', 'mai-engine' ) ),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'search', $options['archive'] ),
				'section'  => $section,
				'label'    => __( 'Search Results', 'mai-engine' ),
				'default'  => $defaults['archive']['search'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'author', $options['archive'] ),
				'section'  => $section,
				'label'    => __( 'Author Archives', 'mai-engine' ),
				'default'  => $defaults['archive']['author'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'date', $options['archive'] ),
				'section'  => $section,
				'label'    => __( 'Date Archives', 'mai-engine' ),
				'default'  => $defaults['archive']['date'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);

	new \Kirki\Field\Select(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( '404-page', $options['single'] ),
				'section'  => $section,
				'label'    => __( '404', 'mai-engine' ),
				'default'  => $defaults['single']['404-page'],
				'choices'  => mai_get_site_layout_choices(),
			]
		)
	);
}
