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

$fields  = [];
$layouts = mai_get_config( 'site-layouts' );

foreach ( mai_get_content_type_choices( $archive = true ) as $type => $label ) {

	$fields[] = [
		'type'     => 'select',
		'settings' => $type,
		'label'    => $label,
		'default'  => isset( $layouts['archive'][ $type ] ) && ! empty( $layouts['archive'][ $type ] ) ? $layouts['archive'][ $type ] : '',
		'choices'  => mai_get_site_layout_choices(),
	];

}

return $fields;

