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
		'name'     => 'image',
		'label'    => __( 'Default Image', 'mai-engine' ),
		'type'     => 'image',
		'sanitize' => 'absint',
		'default'  => '',
		'choices'  => [
			'save_as' => 'id',
		],
	],
	[
		'name'     => 'show',
		'label'    => __( 'Show', 'mai-engine' ),
		'type'     => 'sortable',
		'sanitize' => 'esc_html',
		'default'  => [
			'image',
			'genesis_entry_header',
			'title',
			'header_meta',
			'genesis_before_entry_content',
			'excerpt',
			'genesis_entry_content',
			'more_link',
			'genesis_after_entry_content',
			'genesis_entry_footer',
		],
		'choices'  => 'mai_get_archive_show_choices',
	],
	[
		'type'     => 'image',
		'settings' => 'page-header-image',
		'label'    => __( 'Page Header Image', 'mai-engine' ),
		'default'  => '',
		'choices'  => [
			'save_as' => 'id',
		],
	],
	[
		'type'        => 'dimensions',
		'settings'    => 'page-header-spacing',
		'label'       => __( 'Page Header Spacing', 'mai-engine' ),
		'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
		'default'     => [
			'top'    => '10vw',
			'bottom' => '10vw',
		],
		'choices'     => [
			'labels' => [
				'top'    => __( 'Top', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
			],
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
	],
	[
		'type'     => 'radio-buttonset',
		'settings' => 'text_align',
		'label'    => __( 'Text Alignment', 'mai-engine' ),
		'default'  => 'center',
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
	],
];
