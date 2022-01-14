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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

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
		'data:image/svg+xml;base64,' . base64_encode( file_get_contents( mai_get_dir() . 'assets/svg/mai-logo-icon.svg' ) ),
		'58.995' // This only works as a string for some reason.
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
		esc_html__( 'Content Areas', 'mai-engine' ),
		esc_html__( 'Content Areas', 'mai-engine' ),
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
		'mai-theme',
		'Mai Pattern Library',
		esc_html__( 'Patterns', 'mai-engine' ),
		'edit_posts',
		'mai-patterns',
		'mai_render_admin_patterns_menu_page',
		25
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

add_action( 'init', 'mai_plugins_setup' );
/**
 * Setup plugins admin page class.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_plugins_setup() {
	$page = new Mai_Plugins;
}

/**
 * Renders admin plugins page markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	do_action( 'mai_plugins_page' );
	// echo '<style>
	// :root {
	// 	--mai-admin-toolbar: 32px;
	// 	--mai-admin-content-left: 20px;
	// }
	// @media screen and (max-width: 782px) {
	// 	:root {
	// 		--mai-admin-toolbar: 46px;
	// 	}
	// 	.auto-fold {
	// 		--mai-admin-content-left: 10px;
	// 	}
	// }
	// </style>';
	// echo '<iframe style="display:block;width:calc(100% + var(--mai-admin-content-left));height:calc(100vh - var(--mai-admin-toolbar));position:absolute;top:0;left:calc(var(--mai-admin-content-left) * -1);z-index: 9999;" width="400" height="800" frameborder="0" scrolling="yes" seamless="seamless" src="https://bizbudding.com/mai-engine-admin/"></iframe>';
}

/**
 * Renders admin patterns page markup.
 *
 * @since TBD
 *
 * @return void
 */
function mai_render_admin_patterns_menu_page() {
	?>
	<style>
	.mai-patterns {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr 1fr;
		gap: 48px 24px;
		margin: 60px 0;
	}
	.mai-pattern {
		padding: 24px;
		background: white;
		text-align: center;
		box-shadow: 0 2px 16px 2px rgba(0, 0, 0, 0.05);
		border-radius: 3px;
	}
	.mai-pattern-category {
		display: block;
		margin: 0 0 16px;
		font-size: 1.1rem;
	}
	.mai-patterns .mai-icon {
		width: 2rem;
		height: 2rem;
	}
	.mai-patterns .mai-icon svg {
		width: 1.25rem;
		height: 1.25rem;
	}
	</style>
	<?php
	$patterns = [
		[
			'icon' => 'pencil',
			'name' => esc_html__( 'Contact Forms', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/contact-forms/',
		],
		[
			'icon' => 'mouse-pointer',
			'name' => esc_html__( 'CTAs', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/ctas/',
		],
		[
			'icon' => 'lightbulb-on',
			'name' => esc_html__( 'Features', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/features/',
		],
		[
			'icon' => 'border-bottom',
			'name' => esc_html__( 'Footers', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/footers/',
		],
		[
			'icon' => 'image',
			'name' => esc_html__( 'Hero Sections', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/hero/',
		],
		[
			'icon' => 'desktop',
			'name' => esc_html__( 'Homepages', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/homepages/',
		],
		[
			'icon' => 'mobile',
			'name' => esc_html__( 'Landing Pages', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/landing-pages/',
		],
		[
			'icon' => 'images',
			'name' => esc_html__( 'Logos', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/logos/',
		],
		[
			'icon' => 'microphone-alt',
			'name' => esc_html__( 'Podcasts', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/podcasts/',
		],
		[
			'icon' => 'folder-open',
			'name' => esc_html__( 'Portfolios', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/portfolios/',
		],
		[
			'icon' => 'badge-dollar',
			'name' => esc_html__( 'Pricing Tables', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/pricing-tables/',
		],
		[
			'icon' => 'id-card',
			'name' => esc_html__( 'Team Pages', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/team/',
		],
		[
			'icon' => 'quote-left',
			'name' => esc_html__( 'Testimonials', 'mai-engine' ),
			'link' => 'https://bizbudding.com/pattern-category/testimonials/',
		],
	];

	echo '<div class="wrap">';
		printf( '<h1 class="wp-heading-inline">Mai Pattern %s</h1>', esc_html__( 'Library', 'mai-engine' ) );
		echo '<p>';
			printf( esc_html__( 'Create a website design just like the pros using pre-built patterns that provide the ability to add complex sections and layouts to your website. The %s is included with the %s and is included for our %s customers.', 'mai-engine' ), 'Mai Pattern Library', 'Mai Design Pack', 'Mai Solution' );
		echo '</p>';
		echo '<p>';
			printf( esc_html__( 'These patterns are designed to work seamlessly with %s.', 'mai-engine' ), 'Mai Theme' );
		echo '</p>';
		echo '<p>';
			printf( '%s <a target="_blank" rel="noopener" href="https://bizbudding.com/my-account/">%s</a> %s', esc_html( 'Log in to your', 'mai-engine' ), sprintf( esc_html__( '%s account', 'mai-engine' ), 'BizBudding', 'mai-engine' ), esc_html__( 'to get instant access.', 'mai-engine' ) );
		echo '</p>';
		echo '<p>';
			esc_html__( 'Includes patterns to help you create:', 'mai-engine' );
		echo '</p>';
		echo '<ul class="mai-patterns">';
			$text = esc_html__( 'View Patterns', 'mai-engine' );

			foreach ( $patterns as $pattern ) {
				$icon = $pattern['icon'];
				$name = $pattern['name'];
				$link = $pattern['link'];
				$icon = $icon && function_exists( 'mai_get_icon' ) ? mai_get_icon(
					[
						'icon'             => $icon,
						'style'            => 'light',
						'margin_top'       => '-48px',
						'margin_right'     => 'auto',
						'margin_bottom'    => '0',
						'margin_left'      => 'auto',
						'padding'          => '12px 12px 6px',
						'color_background' => '#fff',
						'border_radius'    => '50%',
					]
				) : '';
				$icon = str_replace( '--icon-', '', $icon );

				printf( '<li class="mai-pattern">%s<p class="mai-pattern-category">%s</p><a class="button button-secondary" target="_blank" rel="noopener" href="%s">%s</a></li>', $icon, $name, $link, $text );
			}
		echo '</ul>';
	echo '</div>';
}

add_action( 'admin_menu', 'mai_admin_menu_subpages', 30 );
/**
 * Add docs and support admin submenu items to end of submenu.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_admin_menu_subpages() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

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

add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_add_plugins_link', 10, 4 );
/**
 * Return the plugin action links. This will only be called if the plugin is active.
 *
 * @since TBD
 *
 * @param array  $actions     Associative array of action names to anchor tags
 * @param string $plugin_file Plugin file name, ie my-plugin/my-plugin.php
 * @param array  $plugin_data Associative array of plugin data from the plugin file headers
 * @param string $context     Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
 *
 * @return array Associative array of plugin action links
 */
function mai_add_plugins_link( $actions, $plugin_file, $plugin_data, $context ) {
	$actions['settings'] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=mai-theme' ), __( 'Plugins', 'mai-engine' ) );

	return $actions;
}
