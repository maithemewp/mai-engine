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

add_action( 'admin_menu', 'mai_admin_menu_pages', 0 );
/**
 * Registers plugin admin menu pages.
 * Exposes Reusable Blocks UI in backend.
 *
 * @link  https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_admin_menu_pages() {
	add_menu_page(
		esc_html__( 'Mai Theme', 'mai-engine' ),
		esc_html__( 'Mai Theme', 'mai-engine' ),
		'manage_options',
		'mai-theme',
		'mai_render_admin_menu_page',
		mai_get_url() . 'assets/svg/mai-icon-white.svg',
		59
	);

	// Changes first menu name. Otherwise above has Mai Theme as the first child too.
	add_submenu_page(
		'mai-theme',
		esc_html__( 'Plugins', 'mai-engine' ),
		esc_html__( 'Plugins', 'mai-engine' ),
		'edit_posts',
		'mai-theme',
		'',
		null
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Template Parts', 'mai-engine' ),
		esc_html__( 'Template Parts', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_template_part',
		'',
		null
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		null
	);

	add_submenu_page(
		'themes.php',
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		22
	);
}

/**
 * Renders admin settings page markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	echo '<div class="wrap">';
		printf( '<h1>%s</h1>', __( 'Mai Theme', 'mai-engine' ) );

		printf( '<div class="notice notice-success"><p>%s</p></div>', __( 'Supercharge your website with our custom add-on plugins!', 'mai-engine' ) );

		printf( '<h2 style="margin-top:40px;">%s</h2>', __( 'Add-on Plugins', 'mai-engine' ) );

		$image_base = sprintf( '%s/assets/img', untrailingslashit( mai_get_url() ) );
		$plugins    = [
			[
				'link'  => 'https://bizbudding.com/products/mai-favorites/',
				'image' => sprintf( '%s/mai-favorites.jpg', $image_base ),
				'title' => __( 'Mai Favorites', 'mai-engine' ),
				'desc'  => __( 'Give your visitors a way to easily browse collections of your favorite things, whether that be affiliate products, recommendations, or anything you want. Your favorites are easily displayed on a page via Mai Post Grid block so you can change the layout, organize by Favorite Category, etc.', 'mai-engine' ),
				'hide'  => class_exists( 'Mai_Favorites_Setup' ),
			],
			[
				'link'  => 'https://bizbudding.com/products/mai-testimonials/',
				'image' => sprintf( '%s/mai-testimonials.jpg', $image_base ),
				'title' => __( 'Mai Testimonials', 'mai-engine' ),
				'desc'  => __( 'Show off all the great things your customers/clients have to say about you. Manage your testimonials in one place, and easily hide them with Mai Post Grid block.', 'mai-engine' ),
				'hide'  => class_exists( 'Mai_Testimonials' ),
			],
			[
				'link'  => 'https://bizbudding.com/products/mai-ads-extra-content/',
				'image' => sprintf( '%s/mai-ads.jpg', $image_base ),
				'title' => __( 'Mai Ads & Extra Content', 'mai-engine' ),
				'desc'  => __( 'Boost your sales with display ads or content across a multitude of areas on your site all from one simple-to-manage spot. Pairs perfectly with the lead generation tool, ConvertFlow, for embedding targeted, dynamic CTAs.', 'mai-engine' ),
				'hide'  => class_exists( 'Mai_AEC' ),
			],
			[
				'link'  => 'https://bizbudding.com/products/mai-effects/',
				'image' => sprintf( '%s/mai-effects.jpg', $image_base ),
				'title' => __( 'Mai Effects', 'mai-engine' ),
				'desc'  => __( '<strong>Coming soon for v2!</strong><br />Add stand-out fadein animation effects, once only possible with developer intervention! Make your page header and sections pop.', 'mai-engine' ),
				'hide'  => class_exists( 'Mai_Effects' ),
			],
			[
				'link'  => 'https://bizbudding.com/products/mai-theme-plugin-pack/',
				'image' => sprintf( '%s/mai-plugin-pack.jpg', $image_base ),
				'title' => __( 'Mai Theme Plugin Pack', 'mai-engine' ),
				'desc'  => __( 'Get all of our add-on plugins at a big discounted rate.', 'mai-engine' ),
				'hide'  => false,
			],
		];

		echo '<ul class="mai-plugins">';

			foreach ( $plugins as $plugin ) {
				if ( $plugin['hide'] ) {
					continue;
				}

				echo '<li class="mai-plugin">';
					printf( '<a class="mai-plugin-image-link" href="%s">', $plugin['link'] );
						printf( '<img class="mai-plugin-image" src="%s" alt="%s %s">', $plugin['image'], $plugin['title'], __( 'product image', 'mai-theme' ) );
					echo '</a>';
					echo '<div class="mai-plugin-content">';
						echo '<h3 class="mai-plugin-title">';
							printf( '<a class="mai-plugin-title-link" href="%s" target="_blank" rel="noopener nofollow">', $plugin['link'] );
								echo $plugin['title'];
							echo '</a>';
						echo '</h3>';
						echo '<p class="mai-plugin-description">';
							echo $plugin['desc'];
						echo '</p>';
						echo '<p class="mai-plugin-more-link-wrap">';
							printf( '<a class="mai-plugin-more-link button button-primary" href="%s" target="_blank" rel="noopener nofollow">', $plugin['link'] );
								echo __( 'Learn More', 'mai-engine' );
							echo '</a>';
						echo '</p>';
					echo '</div>';
				echo '</li>';
			}

		echo '<ul>';

	echo '</div>';
}

/**
 * Add docs and support admin submenu items.
 *
 * @since 2.6.0
 *
 * @return void
 */
add_action( 'admin_menu', 'mai_admin_menu_subpages', 12 );
function mai_admin_menu_subpages() {
	global $submenu;

	$submenu['mai-theme'][] = [
		__( 'Documentation', 'mai-engine' ),
		'edit_posts',
		'https://docs.bizbudding.com/',
	];

	$submenu['mai-theme'][] = [
		__( 'Support', 'mai-engine' ),
		'edit_posts',
		'https://docs.bizbudding.com/support/',
	];
}
