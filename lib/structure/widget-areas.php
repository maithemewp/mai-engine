<?php


add_action( 'mai_before_header_wrap', 'mai_before_header_widget' );
/**
 * Displays the before header widget area.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_before_header_widget() {
	genesis_widget_area(
		'before-header',
		[
			'before' => '<div class="before-header"><div class="wrap">',
			'after'  => '</div></div>',
		]
	);
}

add_action( 'genesis_footer', 'mai_before_footer_widget', 5 );
/**
 * Displays before footer widget area.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_before_footer_widget() {
	genesis_widget_area(
		'before-footer',
		[
			'before' => '<div class="before-footer"><div class="wrap">',
			'after'  => '</div></div>',
		]
	);
}

add_filter( 'genesis_markup_entry-title_content', 'mai_feature_posts_widget_entry_title_link' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_feature_posts_widget_entry_title_link( $default ) {
	$permalink = get_permalink();
	$search    = sprintf( '<a href="%s">', $permalink );
	$replace   = sprintf( '<a href="%s" class="entry-title-link">', $permalink );

	return str_replace( $search, $replace, $default );
}
