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

$locations = [];
foreach( (array) mai_get_config( 'page-header-single' ) as $name ) {
	if ( ! post_type_exists( $name ) ) {
		continue;
	}
	$locations[] = [
		[
			'param'    => 'post_type',
			'operator' => '==',
			'value'    => $name,
		],
	];
}
$taxonomies = get_taxonomies();
foreach( (array) mai_get_config( 'page-header-archive' ) as $name ) {
	if ( isset( $taxonomies[ $name ] ) ) {
		$locations[] = [
			[
				'param'    => 'taxonomy',
				'operator' => '==',
				'value'    => $name,
			],
		];
	} elseif ( 'author' === $name ) {
		$locations[] = [
			[
				'param'    => 'user_form',
				'operator' => '==',
				'value'    => 'edit',
			],
		];
	}
}

acf_add_local_field_group(
	[
		'key'                   => 'group_5e4ebe9174ed9',
		'title'                 => 'Page Header',
		'location'              => $locations,
		'menu_order'            => 0,
		'position'              => 'side',
		'style'                 => 'seamless',
		'label_placement'       => 'left',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
		'fields'                => [
			[
				'key'           => 'field_5e4ebeb050b3e',
				'label'         => esc_html__( 'Page Header Image', 'mai-engine' ),
				'name'          => 'page_header_image',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'landscape-sm',
				'library'       => 'all',
			],
			[
				'key'           => 'field_5e4ebeb950b3f',
				'label'         => esc_html__( 'Page Header Subtitle', 'mai-engine' ),
				'name'          => 'page_header_subtitle',
				'type'          => 'textarea',
				'rows'          => '3',
			],
		],
	]
);
