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

add_action( 'genesis_before', 'mai_amp_structure' );
/**
 * Add all our AMP code here. Avoids multiple genesis_is_amp() calls.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_amp_structure() {
	if ( ! genesis_is_amp() ) {
		return;
	}

	remove_action( 'mai_header_left', 'mai_header_left_menu', 15 );
	remove_action( 'mai_header_right', 'mai_header_right_menu' );
	remove_action( 'genesis_after_header', 'mai_after_header_menu' );

	add_action( 'genesis_header', 'mai_do_amp_menu_toggle' );
	add_action( 'genesis_after_footer', 'mai_do_amp_menu_sidebar' );
}

/**
 * Render AMP menu toggle button.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_amp_menu_toggle() {
	?>
	<button role="button" on="tap:amp-menu.toggle" tabindex="0" class="amp-menu-toggle">☰</button>
	<?php
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_amp_menu_sidebar() {
	?>
	<amp-sidebar id="amp-menu" layout="nodisplay" side="left">
		<button role="button" aria-label="close sidebar" on="tap:amp-menu.toggle" tabindex="0" class="amp-menu-close">✕
		</button>
		<ul class="amp-menu">
			<?php

			$items = [];
			$menus = [
				wp_get_nav_menu_items( 'header-left' ),
				wp_get_nav_menu_items( 'header-right' ),
				wp_get_nav_menu_items( 'after-header' ),
			];

			foreach ( $menus as $menu ) {
				if ( ! empty( $menu ) ) {
					$items = array_merge_recursive( $items, $menu );
				}
			}

			/**
			 * Post.
			 *
			 * @var WP_Post $item Post object.
			 */
			foreach ( $items as $item ) {
				printf(
					'<li class="amp-menu-item"><a href="%s">%s</a></li>',
					$item->url,
					$item->title
				);
			}
			?>
		</ul>
	</amp-sidebar>
	<?php
}
