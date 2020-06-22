<?php

$button_elements = [
	'.button',
	'[type=button]',
	'[type=reset]',
	'[type=submit]',
	'button',
];

$button_elements_hover = [];

foreach ( $button_elements as $button_element ) {
	$button_elements_hover[] = $button_element . ':hover';
	$button_elements_hover[] = $button_element . ':focus';
}

return [];
