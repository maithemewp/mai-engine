<?php

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> mai_name(),
		'menu_title'	=> mai_name(),
		'menu_slug' 	=> mai_handle(),
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Addons',
		'menu_title'	=> 'Addons',
		'parent_slug'	=> mai_handle(),
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Courses',
		'menu_title'	=> 'Courses',
		'parent_slug'	=> mai_handle(),
	));

}
