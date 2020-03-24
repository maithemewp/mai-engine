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
		'label'    => esc_html__( 'Default Image', 'mai-engine' ),
		'type'     => 'image',
		'sanitize' => 'absint',
		'default'  => '',
		'choices'  => [
			'save_as' => 'id',
		],
	],
	[
		'name'     => 'show',
		'label'    => esc_html__( 'Show', 'mai-engine' ),
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
];
