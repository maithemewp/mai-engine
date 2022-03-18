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

add_filter( 'acf/load_field/key=mai_icon_choices', 'mai_load_icon_choices' );
/**
 * Load the icon field, getting choices from our icons directory.
 * Uses sprite for performance of loading choices in the field.
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field.
 *
 * @return array
 */
function mai_load_icon_choices( $field ) {
	// Bail if editing the field group.
	if ( 'acf-field-group' === get_post_type() ) {
		return $field;
	}

	$field['choices'] = mai_get_icon_choices( 'light' );

	return $field;
}

add_filter( 'acf/load_field/key=mai_icon_brand_choices', 'mai_load_icon_brand_choices' );
/**
 * Add icon brand choices.
 *
 * @since 0.1.0
 *
 * @param array $field Field args.
 *
 * @return mixed
 */
function mai_load_icon_brand_choices( $field ) {
	// Bail if editing the field group.
	if ( 'acf-field-group' === get_post_type() ) {
		return $field;
	}

	$field['choices'] = mai_get_icon_choices( 'brands' );

	return $field;
}

/**
 * Get icon svg choices.
 *
 * @since 1.0.0
 *
 * @link https://css-tricks.com/on-xlinkhref-being-deprecated-in-svg/
 *
 * @param string $style Icon style.
 *
 * @return array
 */
function mai_get_icon_choices( $style ) {
	$choices = [];
	$dir     = mai_get_icons_dir();
	$url     = mai_get_icons_url();

	if ( ! ( $dir && $url ) ) {
		return $choices;
	}

	$dir .= sprintf( '/svgs/%s', $style );
	$url .= sprintf( '/sprites/%s', $style );

	foreach ( glob( $dir . '/*.svg' ) as $file ) {
		$name             = basename( $file, '.svg' );
		$choices[ $name ] = sprintf(
			'<svg class="mai-icon-svg" width="32" height="32"><use href="%s.svg#%s"></use></svg><span class="mai-icon-name">%s</span>',
			$url,
			$name,
			$name
		);
	}

	return $choices;
}

/**
 * Gets fields for acf field group.
 *
 * @access private
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_icons_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$fields = [
		[
			'key'           => 'mai_icon_style',
			'name'          => 'style',
			'label'         => esc_html__( 'Icon Style', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => 'light',
			'choices'       => [
				'light'   => esc_html__( 'Light', 'mai-engine' ),
				'regular' => esc_html__( 'Regular', 'mai-engine' ),
				'solid'   => esc_html__( 'Solid', 'mai-engine' ),
				'brands'  => esc_html__( 'Brands', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'               => 'mai_icon_choices',
			'name'              => 'icon',
			'label'             => esc_html__( 'Icon', 'mai-engine' ) . sprintf( ' (%s <a href="https://fontawesome.com/v5/search/">Font Awesome</a>)', esc_html__( 'full search via', 'mai-engine' ) ),
			'type'              => 'select',
			'default_value'     => 'heart',
			'allow_null'        => 1, // These fields are cloned in Mai Notices and other blocks so we need to allow null.
			'multiple'          => 0,
			'ui'                => 1,
			'ajax'              => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_style',
					'operator' => '!=',
					'value'    => 'brands',
				],
			],
			'wrapper'           => [
				'class' => 'mai-icon-select',
			],
		],
		[
			'key'               => 'mai_icon_brand_choices',
			'name'              => 'icon_brand',
			'label'             => esc_html__( 'Icon (Brands)', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => 'wordpress-simple',
			'allow_null'        => 1, // These fields are cloned in Mai Notices, Mai Lists, etc. so we need to allow null.
			'multiple'          => 0,
			'ui'                => 1,
			'ajax'              => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_style',
					'operator' => '==',
					'value'    => 'brands',
				],
			],
			'wrapper'           => [
				'class' => 'mai-icon-select',
			],
		],
		[
			'key'     => 'mai_icon_color',
			'label'   => esc_html__( 'Icon Color', 'mai-engine' ),
			'name'    => 'color_icon',
			'type'    => 'radio',
			'choices' => mai_get_radio_color_choices(),
			'wrapper' => [
				'class' => 'mai-block-colors',
			],
		],
		[
			'key'               => 'mai_icon_color_custom',
			'name'              => 'color_icon_custom',
			'type'              => 'color_picker',
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_color',
					'operator' => '==',
					'value'    => 'custom',
				],
			],
		],
		[
			'key'     => 'mai_icon_background',
			'label'   => esc_html__( 'Background Color', 'mai-engine' ),
			'name'    => 'color_background',
			'type'    => 'radio',
			'choices' => mai_get_radio_color_choices(),
			'wrapper' => [
				'class' => 'mai-block-colors',
			],
		],
		[
			'key'               => 'mai_icon_background_custom',
			'name'              => 'color_background_custom',
			'type'              => 'color_picker',
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_background',
					'operator' => '==',
					'value'    => 'custom',
				],
			],
		],
	];

	return $fields;
}
