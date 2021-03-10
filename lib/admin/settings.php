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

add_action( 'admin_menu', 'mai_admin_menu_pages' );
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
		'edit_posts',
		'mai-theme',
		'mai_render_admin_menu_page',
		'data:image/svg+xml;base64,' . base64_encode( file_get_contents( mai_get_dir() . 'assets/svg/admin-menu-icon.svg' ) ),
		'58.995' // This only works as a string for some reason.
	);

	// Changes first menu name. Otherwise above has Mai Theme as the first child too.
	add_submenu_page(
		'mai-theme',
		esc_html__( 'Mai Plugins', 'mai-engine' ),
		esc_html__( 'Mai Plugins', 'mai-engine' ),
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
		'edit.php?post_type=mai_template_part',
		'',
		10
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		20
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

		?>
		<div class="mai-admin-cta">
			<div class="mai-admin-cta-inner">
				<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 512 512">
					<path d="M494.59 164.52c-1.98-1.63-22.19-17.91-46.59-37.53V96c0-17.67-14.33-32-32-32h-46.47c-4.13-3.31-7.71-6.16-10.2-8.14C337.23 38.19 299.44 0 256 0c-43.21 0-80.64 37.72-103.34 55.86-2.53 2.01-6.1 4.87-10.2 8.14H96c-17.67 0-32 14.33-32 32v30.98c-24.52 19.71-44.75 36.01-46.48 37.43A48.002 48.002 0 0 0 0 201.48V464c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V201.51c0-14.31-6.38-27.88-17.41-36.99zM256 32c21.77 0 44.64 16.72 63.14 32H192.9c18.53-15.27 41.42-32 63.1-32zM96 96h320v173.35c-32.33 26-65.3 52.44-86.59 69.34-16.85 13.43-50.19 45.68-73.41 45.31-23.21.38-56.56-31.88-73.41-45.32-21.29-16.9-54.24-43.33-86.59-69.34V96zM32 201.48c0-4.8 2.13-9.31 5.84-12.36 1.24-1.02 11.62-9.38 26.16-21.08v75.55c-11.53-9.28-22.51-18.13-32-25.78v-16.33zM480 464c0 8.82-7.18 16-16 16H48c-8.82 0-16-7.18-16-16V258.91c42.75 34.44 99.31 79.92 130.68 104.82 20.49 16.36 56.74 52.53 93.32 52.26 36.45.26 72.27-35.46 93.31-52.26C380.72 338.8 437.24 293.34 480 258.9V464zm0-246.19c-9.62 7.75-20.27 16.34-32 25.79v-75.54c14.44 11.62 24.8 19.97 26.2 21.12 3.69 3.05 5.8 7.54 5.8 12.33v16.3zm-251.09 22.77l45 13.5c5.16 1.55 8.77 6.78 8.77 12.73 0 7.27-5.3 13.19-11.8 13.19h-28.11c-4.56 0-8.96-1.29-12.82-3.72-3.24-2.03-7.36-1.91-10.13.73l-11.75 11.21c-3.53 3.37-3.33 9.21.57 12.14 9.1 6.83 20.08 10.77 31.37 11.35V328c0 4.42 3.58 8 8 8h16c4.42 0 8-3.58 8-8v-16.12c23.62-.63 42.67-20.54 42.67-45.07 0-19.97-12.98-37.81-31.58-43.39l-45-13.5c-5.16-1.55-8.77-6.78-8.77-12.73 0-7.27 5.3-13.19 11.8-13.19h28.11c4.56 0 8.96 1.29 12.82 3.72 3.24 2.03 7.36 1.91 10.13-.73l11.75-11.21c3.53-3.37 3.33-9.21-.57-12.14-9.1-6.83-20.08-10.77-31.37-11.35V136c0-4.42-3.58-8-8-8h-16c-4.42 0-8 3.58-8 8v16.12c-23.62.63-42.67 20.55-42.67 45.07 0 19.97 12.98 37.81 31.58 43.39z"></path>
				</svg>
				<h3><?php echo __( 'Save $$$ on all themes & all plugins!', 'mai-engine' ); ?></h3>
				<p><?php echo __( 'Get all of our designs and extend the functionality of your Mai Theme powered website with the Mai Plugins. Designed to work seamlessly with Mai Theme so you can launch and grow your website quickly.', 'mai-engine' ); ?>
				<br /><strong><em><?php echo __( 'Valid on unlimited sites.', 'mai-engine' ); ?></strong></em></p>
				<a class="button mai-admin-cta-button" href="https://bizbudding.com/products/mai-theme/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-theme"><?php echo __( 'View The Bundle', 'mai-engine' ); ?></a>
			</div>
		</div>
		<?php

		$image_base      = sprintf( '%s/assets/img', untrailingslashit( mai_get_url() ) );
		$plugins = [
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-archive-pages/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-archive-pages',
				'image' => sprintf( '%s/mai-archive-pages.jpg', $image_base ),
				'title' => __( 'Mai Archive Pages', 'mai-engine' ),
				'desc'  => __( 'Build robust and SEO-friendly archive page intro content with blocks.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Archive_Pages_Plugin' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-favorites/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-favorites',
				'image' => sprintf( '%s/mai-favorites.jpg', $image_base ),
				'title' => __( 'Mai Favorites', 'mai-engine' ),
				'desc'  => __( 'Easily and beautifully display collections of your favorite external things (affiliate products, recommendations, etc.) with the Mai Post Grid block.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Favorites_Setup' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-testimonials/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-testimonials',
				'image' => sprintf( '%s/mai-testimonials.jpg', $image_base ),
				'title' => __( 'Mai Testimonials', 'mai-engine' ),
				'desc'  => __( 'Show off all the great things your customers/clients have to say about you. Manage your testimonials in one place, and easily hide them with the Mai Post Grid block.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Testimonials' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-notices/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-notices',
				'image' => sprintf( '%s/mai-notices.jpg', $image_base ),
				'title' => __( 'Mai Notices', 'mai-engine' ),
				'desc'  => __( 'Display custom callout notices with icons to grab attention, show info, notes, ideas, errors, and more.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Accordian' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-ads-extra-content/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-ads-extra-content',
				'image' => sprintf( '%s/mai-ads.jpg', $image_base ),
				'title' => __( 'Mai Ads & Extra Content', 'mai-engine' ),
				'desc'  => __( 'Boost your sales with display ads or content across a multitude of areas on your site all from one simple-to-manage spot. Pairs perfectly with the lead generation tool, ConvertFlow, for embedding targeted, dynamic CTAs.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_AEC' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-effects/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-effects',
				'image' => sprintf( '%s/mai-effects.jpg', $image_base ),
				'title' => __( 'Mai Effects', 'mai-engine' ),
				'desc'  => __( '<strong>Coming soon for v2!</strong><br />Add stand-out fadein animation effects, once only possible with developer intervention! Make your page header and sections pop.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Effects' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-accordion/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-accordion',
				'image' => sprintf( '%s/mai-accordion.jpg', $image_base ),
				'title' => __( 'Mai Accordion', 'mai-engine' ),
				'desc'  => __( 'Create custom accordion block for content toggles, transcripts, FAQ’s, and more. Super fast and easy to use. No Javascript means no slowing down your pages.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Accordian' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-display-taxonomy/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-display-taxonomy',
				'image' => sprintf( '%s/mai-display-taxonomy.jpg', $image_base ),
				'title' => __( 'Mai Display Taxonomy', 'mai-engine' ),
				'desc'  => __( 'A utility plugin that creates a private, backend-only “Display” taxonomy for use with the Mai Post Grid block to have total control over your grid content in various areas of your website.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Display_Taxonomy' ),
			],
			[
				'link'  => 'https://bizbudding.com/mai-theme/plugins/mai-config-generator/?utm_source=engine&utm_medium=dashboard&utm_campaign=mai-config-generator',
				'image' => sprintf( '%s/mai-config-generator.jpg', $image_base ),
				'title' => __( 'Mai Config Generator', 'mai-engine' ),
				'desc'  => __( 'A developer-focused plugin to generate config.php content for setting defaults in a custom Mai Theme. If you install your custom theme, or site managers change any of the Customizer settings, the defaults will now come from this config.', 'mai-engine' ),
				// 'hide'  => class_exists( 'Mai_Config_Generator' ),
			],
		];

		// $has_plugins = wp_list_pluck( $plugins, 'hide' );
		// $has_plugins = in_array( false, $has_plugins, true );

		// if ( $has_plugins ) {
			printf( '<h2 style="margin-top:40px;font-size:2em;">%s</h2>', __( 'Mai Add-on Plugins', 'mai-engine' ) );

			echo '<ul class="mai-plugins">';

				foreach ( $plugins as $plugin ) {
					// if ( $plugin['hide'] ) {
					// 	continue;
					// }

					mai_do_plugin_list_item_html( $plugin );
				}

			echo '</ul>';
		// }

	echo '</div>';
}

/**
 * Displays the plugin list item HTML.
 *
 * @access private
 * @since 2.8.0
 *
 * @param array $plugin The plugin data.
 *
 * @return void
 */
function mai_do_plugin_list_item_html( $plugin ) {
	echo '<li class="mai-plugin">';
		printf( '<a class="mai-plugin-image-link" href="%s" target="_blank" rel="noopener nofollow">', $plugin['link'] );
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
