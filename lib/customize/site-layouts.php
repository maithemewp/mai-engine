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

add_action( 'init', 'mai_site_layouts_customizer_settings' );
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
	$defaults = mai_get_config( 'settings' )['site-layout'];
	$section  = sprintf( '%s-%s', $handle, $name );
	$options  = [
		'default' => sprintf( '%s[%s][default]', $handle, $name ),
		'archive' => sprintf( '%s[%s][archive]', $handle, $name ),
		'single'  => sprintf( '%s[%s][single]', $handle, $name ),
	];

	\Kirki::add_section(
		$section,
		[
			'title' => __( 'Site Layouts', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'boxed-container',
			'section'  => $section,
			'label'    => __( 'Enable boxed site container', 'mai-engine' ),
			'default'  => current_theme_supports( 'boxed-container' ),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'custom',
			'settings'    => 'defaults-layout-divider',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'default'     => sprintf( '<h3>%s</h3>', __( 'Defaults', 'mai-engine' ) ),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'site',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'label'       => __( 'Site Default', 'mai-engine' ),
			'default'     => $defaults['default']['site'],
			'choices'     => genesis_get_layouts_for_customizer(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'archive',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'label'       => __( 'Content Archives', 'mai-engine' ),
			'default'     => $defaults['default']['archive'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'single',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'label'       => __( 'Single Content', 'mai-engine' ),
			'default'     => $defaults['default']['single'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );

	foreach ( $post_types as $name => $post_type ) {

		\Kirki::add_field(
			$handle,
			[
				'type'        => 'custom',
				'settings'    => $name . '-layout-divider',
				'option_type' => 'option',
				'option_name' => $options['default'],
				'section'     => $section,
				'default'     => sprintf( '<h3>%s</h3>', $post_type->label ),
			]
		);

		\Kirki::add_field(
			$handle,
			[
				'type'        => 'select',
				'settings'    => $name,
				'option_type' => 'option',
				'option_name' => $options['single'],
				'section'     => $section,
				'label'       => __( 'Single', 'mai-engine' ),
				'default'     => isset( $defaults['single'][ $name ] ) ? $defaults['single'][ $name ] : '',
				'choices'     => mai_get_site_layout_choices(),
			]
		);

		if ( 'post' === $name || $post_type->has_archive ) {

			\Kirki::add_field(
				$handle,
				[
					'type'        => 'select',
					'settings'    => $name,
					'option_type' => 'option',
					'option_name' => $options['archive'],
					'section'     => $section,
					'label'       => __( 'Archive', 'mai-engine' ),
					'default'     => isset( $defaults['archive'][ $name ] ) ? $defaults['archive'][ $name ] : '',
					'choices'     => mai_get_site_layout_choices(),
				]
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
				\Kirki::add_field(
					$handle,
					[
						'type'        => 'select',
						'settings'    => $taxo_name,
						'option_type' => 'option',
						'option_name' => $options['archive'],
						'section'     => $section,
						'label'       => $taxonomy->label,
						'default'     => isset( $defaults['archive'][ $taxo_name ] ) ? $defaults['archive'][ $taxo_name ] : '',
						'choices'     => mai_get_site_layout_choices(),
					]
				);
			}
		}
	}

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'custom',
			'settings'    => 'misc-layout-divider',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'default'     => sprintf( '<h3>%s</h3>', __( 'Miscellaneous', 'mai-engine' ) ),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'search',
			'option_type' => 'option',
			'option_name' => $options['archive'],
			'section'     => $section,
			'label'       => __( 'Search Results', 'mai-engine' ),
			'default'     => $defaults['archive']['search'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'author',
			'option_type' => 'option',
			'option_name' => $options['archive'],
			'section'     => $section,
			'label'       => __( 'Author Archives', 'mai-engine' ),
			'default'     => $defaults['archive']['author'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => 'date',
			'option_type' => 'option',
			'option_name' => $options['archive'],
			'section'     => $section,
			'label'       => __( 'Date Archives', 'mai-engine' ),
			'default'     => $defaults['archive']['date'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'select',
			'settings'    => '404-page',
			'option_type' => 'option',
			'option_name' => $options['single'],
			'section'     => $section,
			'label'       => __( '404', 'mai-engine' ),
			'default'     => $defaults['single']['404-page'],
			'choices'     => mai_get_site_layout_choices(),
		]
	);
}
