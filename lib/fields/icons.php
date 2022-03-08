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
			'label'         => esc_html__( 'Style', 'mai-engine' ),
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
	];

	return $fields;
}
