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

add_action( 'after_setup_theme', 'mai_add_hide_elements_metabox' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_hide_elements_metabox() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$post_types = array_keys( get_post_types() );
	$locations  = [];

	foreach ( $post_types as $post_type ) {
		$locations[] = [
			[
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $post_type,
			],
		];
	}

	acf_add_local_field_group(
		[
			'key'                   => 'hide_elements',
			'title'                 => __( 'Hide Elements', 'mai-engine' ),
			'menu_order'            => 10,
			'position'              => 'side',
			'style'                 => 'seamless',
			'label_placement'       => 'left',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'location'              => $locations,
			'fields'                => [
				[
					'key'               => 'hide_elements',
					'name'              => 'hide_elements',
					'type'              => 'checkbox',
					'instructions'      => __( 'Select elements to hide on this page.', 'mai-engine' ),
					'required'          => 0,
					'conditional_logic' => 0,
					'allow_custom'      => 0,
					'default_value'     => [],
					'layout'            => 'vertical',
					'toggle'            => 0,
					'return_format'     => 'value',
					'save_custom'       => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'choices'           => [
						'before_header'  => __( 'Before Header', 'mai-engine' ),
						'site_header'    => __( 'Site Header', 'mai-engine' ),
						'page_header'    => __( 'Page Header', 'mai-engine' ),
						'breadcrumbs'    => __( 'Breadcrumbs', 'mai-engine' ),
						'entry_title'    => __( 'Entry Title', 'mai-engine' ),
						'featured_image' => __( 'Featured Image', 'mai-engine' ),
						'before_footer'  => __( 'Before Footer', 'mai-engine' ),
						'footer_widgets' => __( 'Footer Widgets', 'mai-engine' ),
						'footer_credits' => __( 'Footer Credits', 'mai-engine' ),
					],
				],
			],
		]
	);
}
