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
 * @since 0.3.0
 *
 * @return void
 */
function mai_site_layouts_customizer_settings() {
	$handle  = mai_get_handle();
	$name    = 'site-layouts';
	$section = sprintf( '%s-%s', $handle, $name  );
	$options  = [
		'default' => sprintf( '%s[%s][default]', $handle, $name  ),
		'archive' => sprintf( '%s[%s][archive]', $handle, $name  ),
		'single'  => sprintf( '%s[%s][single]', $handle, $name  ),
	];

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Site Layouts', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'checkbox',
			'settings'    => 'boxed-container',
			'option_type' => 'option',
			'option_name' => $options['default'],
			'section'     => $section,
			'label'       => __( 'Enable boxed site container', 'mai-engine' ),
			'default'     => current_theme_supports( 'boxed-container' ),
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
			'default'     => sprintf( '<h3>%s</h3>', esc_html__( 'Defaults', 'mai-engine' ) ),
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
			'default'     => isset( $layouts['default']['site'] ) && ! empty( $layouts['default']['site'] ) ? $layouts['default']['site']: 'standard-content',
			'choices'     => mai_get_site_layout_choices(),
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
			'default'     => isset( $layouts['default']['archive'] ) && ! empty( $layouts['default']['archive'] ) ? $layouts['default']['archive']: 'wide-content',
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
			'default'     => isset( $layouts['default']['single'] ) && ! empty( $layouts['default']['single'] ) ? $layouts['default']['single']: '',
			'choices'     => mai_get_site_layout_choices(),
		]
	);

	$layouts    = mai_get_config( 'site-layouts' );
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
				'label'       => esc_html__( 'Single', 'mai-engine' ),
				'default'     => isset( $layouts['single'][ $name ] ) && ! empty( $layouts['single'][ $name ] ) ? $layouts['single'][ $name ]: '',
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
					'label'       => esc_html__( 'Archive', 'mai-engine' ),
					'default'     => isset( $layouts['archive'][ $name ] ) && ! empty( $layouts['archive'][ $name ] ) ? $layouts['archive'][ $name ]: '',
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
						'default'     => isset( $layouts['archive'][ $taxo_name ] ) && ! empty( $layouts['archive'][ $taxo_name ] ) ? $layouts['archive'][ $taxo_name ]: '',
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
			'default'     => sprintf( '<h3>%s</h3>', esc_html__( 'Miscellaneous', 'mai-engine' ) ),
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
			'label'       => esc_html__( 'Search Results', 'mai-engine' ),
			'default'     => isset( $layouts['archive']['search'] ) && ! empty( $layouts['archive']['search'] ) ? $layouts['archive']['search']: '',
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
			'label'       => esc_html__( 'Author Archives', 'mai-engine' ),
			'default'     => isset( $layouts['archive']['author'] ) && ! empty( $layouts['archive']['author'] ) ? $layouts['archive']['author']: '',
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
			'label'       => esc_html__( 'Date Archives', 'mai-engine' ),
			'default'     => isset( $layouts['archive']['date'] ) && ! empty( $layouts['archive']['date'] ) ? $layouts['archive']['date']: '',
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
			'label'       => esc_html__( '404', 'mai-engine' ),
			'default'     => isset( $layouts['single']['404-page'] ) && ! empty( $layouts['single']['404-page'] ) ? $layouts['single']['404-page']: '',
			'choices'     => mai_get_site_layout_choices(),
		]
	);
}
