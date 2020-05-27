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

$layouts = mai_get_config( 'site-layouts' );

return [
	[
		'type'     => 'checkbox',
		'settings' => 'boxed-container',
		'label'    => __( 'Enable boxed site container', 'mai-engine' ),
		'default'  => '',
	],
	[
		'type'     => 'select',
		'settings' => 'site',
		'label'    => __( 'Site Default', 'mai-engine' ),
		'default'  => isset( $layouts['default']['site'] ) && ! empty( $layouts['default']['site'] ) ? $layouts['default']['site'] : 'standard-content',
		'choices'  => mai_get_site_layout_choices(),
	],
	[
		'type'     => 'select',
		'settings' => 'archive',
		'label'    => __( 'Content Archives', 'mai-engine' ),
		'default'  => isset( $layouts['default']['archive'] ) && ! empty( $layouts['default']['archive'] ) ? $layouts['default']['archive'] : 'wide-content',
		'choices'  => mai_get_site_layout_choices(),
	],
	[
		'type'     => 'select',
		'settings' => 'single',
		'label'    => __( 'Single Content', 'mai-engine' ),
		'default'  => isset( $layouts['default']['single'] ) && ! empty( $layouts['default']['single'] ) ? $layouts['default']['single'] : '',
		'choices'  => mai_get_site_layout_choices(),
	],
];
