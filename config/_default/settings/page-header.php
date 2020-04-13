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

return [
	[
		'type'     => 'image',
		'settings' => 'page-header-image',
		'label'    => __( 'Page Header Image', 'mai-engine' ),
		'default'  => '',
		'choices'  => [
			'save_as' => 'id',
		],
		'active_callback'  => 'mai_has_any_page_header_types',
	],
	[
		'type'        => 'dimensions',
		'settings'    => 'page-header-spacing',
		'label'       => __( 'Page Header Spacing', 'mai-engine' ),
		'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
		'default'     => [
			'top'    => '',
			'bottom' => '',
		],
		'choices'     => [
			'top'    => __( 'Top', 'mai-engine' ),
			'bottom' => __( 'Bottom', 'mai-engine' ),
		],
		'output'      => [
			[
				'choice'   => 'top',
				'element'  => ':root',
				'property' => '--page-header-padding-top',
			],
			[
				'choice'   => 'bottom',
				'element'  => ':root',
				'property' => '--page-header-padding-bottom',
			],
		],
		'input_attrs' => [
			'placeholder' => '10vw',
		],
		'active_callback'  => 'mai_has_any_page_header_types',
	],
	[
		'type'     => 'radio-buttonset',
		'settings' => 'text_align',
		'label'    => __( 'Text Alignment', 'mai-engine' ),
		'default'  => '',
		'choices'  => [
			'start'  => __( 'Start', 'mai-engine' ),
			'center' => __( 'Center', 'mai-engine' ),
			'end'    => __( 'End', 'mai-engine' ),
		],
		'output'   => [
			[
				'choice'   => [ 'start', 'center', 'end' ],
				'element'  => ':root',
				'property' => '--page-header-text-align',
			],
		],
		'active_callback'  => 'mai_has_any_page_header_types',
	],
];
