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

// Override demo importer constants.
if ( ! defined( 'PT_OCDI_PATH' ) ) {
	define( 'PT_OCDI_PATH', plugin_dir_path( dirname( __DIR__ ) ) . 'vendor/wpackagist-plugin/one-click-demo-import/' );
}

if ( ! defined( 'PT_OCDI_URL' ) ) {
	define( 'PT_OCDI_URL', plugin_dir_url( dirname( __DIR__ ) ) . 'vendor/wpackagist-plugin/one-click-demo-import/' );
}

// Dependency installer labels.
add_filter( 'wp_dependency_dismiss_label', 'mai_get_name' );
add_filter( 'wp_dependency_required_row_meta', '__return_false' );

// Disable branding.
add_filter( 'pt-ocdi/disable_pt_branding', '__return_true' );

// Remove intro text.
add_filter( 'pt-ocdi/plugin_intro_text', '__return_empty_string' );

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Change plugin dependency text.
 *
 * @since 1.0.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Theme Dependency', 'mai-engine' )
	);

	return $actions;
}

add_filter( 'pt-ocdi/plugin_page_setup', 'mai_demo_import_plugin_page' );
/**
 * Modify the one click demo import plugin page settings.
 *
 * @since 1.0.0
 *
 * @param array $defaults Default settings to override.
 *
 * @return array
 */
function mai_demo_import_plugin_page( $defaults ) {
	$defaults['menu_slug'] = 'mai-demo-import';

	return $defaults;
}

add_action( 'pt-ocdi/before_content_import', 'mai_before_content_import', 9, 1 );
/**
 * Install and activate required plugins.
 *
 * @since 1.0.0
 *
 * @param array $selected_import Selected demo data.
 *
 * @return void
 */
function mai_before_content_import( $selected_import ) {
	$theme        = mai_get_active_theme();
	$name         = isset( $selected_import['import_file_name'] ) ? $selected_import['import_file_name'] : '';
	$demo         = str_replace( $theme . ' ', '', mai_convert_case( $name, 'lower' ) );
	$dependencies = mai_get_config( 'required-plugins' );

	foreach ( $dependencies as $step => $dependency ) {
		$demos = isset( $dependency['demos'] ) ? $dependency['demos'] : [];

		if ( in_array( $demo, $demos, true ) || is_string( $demos ) && '*' === $demos ) {
			genesis_onboarding_install_dependencies( $dependencies, $step );
		}
	}
}

add_filter( 'pt-ocdi/import_files', 'mai_demo_import_files' );
/**
 * Add theme demo config to one click demo import.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_demo_import_files() {
	$url   = mai_get_url();
	$dir   = mai_get_dir();
	$theme = mai_get_active_theme();
	$demos = glob( "$dir/config/$theme/demos/*", GLOB_ONLYDIR );

	foreach ( $demos as $path ) {
		$name = basename( $path );

		$demos[] = [
			'import_file_name'             => mai_convert_case( "$theme $name", 'title' ),
			'local_import_file'            => "$path/content.xml",
			'local_import_widget_file'     => "$path/widgets.wie",
			'local_import_customizer_file' => "$path/customizer.dat",
			'import_preview_image_url'     => "$url/config/$theme/demos/$name/screenshot.png",
			'import_notice'                => '',
			'preview_url'                  => "https://demo.bizbudding.com/$theme-$name/",
		];
	}

	return apply_filters( 'mai_theme_demos', $demos );
}

add_filter( 'pt-ocdi/after_all_import_execution', 'mai_after_demo_import', 100 );
/**
 * Set default pages after demo import.
 *
 * Automatically creates and sets the Static Front Page and the Page for Posts
 * upon theme activation, only if these pages don't already exist and only
 * if the site does not already display a static page on the homepage.
 *
 * @since  1.0.0
 *
 * @return void
 */
function mai_after_demo_import() {

	// Assign menus to their locations.
	$locations['header-left']  = get_term_by( 'name', 'Header Left Menu', 'nav_menu' );
	$locations['header-right'] = get_term_by( 'name', 'Header Right Menu', 'nav_menu' );
	$locations['after-header'] = get_term_by( 'name', 'After Header Menu', 'nav_menu' );
	$locations['footer']       = get_term_by( 'name', 'Footer Menu', 'nav_menu' );

	foreach ( $locations as $location => $menu ) {
		if ( $menu ) {
			$menus[ $location ] = $menu->term_id;
		}
	}

	if ( isset( $menus ) && $menus ) {
		set_theme_mod( 'nav_menu_locations', $menus );
	}

	// Assign front page and posts page (blog page).
	$home = get_page_by_title( 'Home' );
	$blog = get_page_by_title( 'Blog' );

	if ( $home && $blog ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
		update_option( 'page_for_posts', $blog->ID );
	}

	// Set the WooCommerce shop page.
	$shop = get_page_by_title( 'Shop' );

	if ( $shop ) {
		update_option( 'woocommerce_shop_page_id', $shop->ID );
	}

	// Trash "Hello World" post.
	wp_delete_post( 1 );

	/**
	 * WP Rewrite object.
	 *
	 * @var WP_Rewrite $wp_rewrite WP Rewrite object.
	 */
	global $wp_rewrite;

	// Update permalink structure.
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	$wp_rewrite->flush_rules();
}

add_filter( 'pt-ocdi/plugin_intro_text', 'mai_demo_import_intro_text' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $intro_text
 *
 * @return mixed
 */
function mai_demo_import_intro_text( $intro_text ) {
	$intro = sprintf(
		'<p>%s %s.</p>',
		__( 'Hi! Thanks for choosing Mai', 'mai-engine' ),
		mai_convert_case( mai_get_active_theme(), 'title' )
	);

	$intro .= sprintf(
		'<p>%s <a href="%s"><strong>%s</strong></a> %s</p>',
		__( 'Choose a One Click Demo Import to load pages, posts, and images that match the corresponding Mai Sparkle demo website. Loading the demo content will not overwrite any of your existing content. If you do not need the demo content, simply', 'mai-engine' ),
		get_admin_url(),
		__( 'skip', 'mai-engine' ),
		__( 'this step.', 'mai-engine' )
	);

	$intro .= sprintf(
		'<p>%s <a href="%s" target="_blank"><strong>%s</strong></a> %s</p>',
		__( 'Please contact', 'mai-engine' ),
		'https://support.bizbudding.com',
		__( 'support', 'mai-engine' ),
		__( 'for assistance. As with any update, be sure to back up your site first!', 'mai-engine' )
	);

	return $intro_text . $intro;
}

add_filter( 'mai_plugin_dependencies', 'mai_require_genesis_connect', 10, 1 );
/**
 * Recommend Genesis Connect if WooCommerce or EDD installed.
 *
 * @since 1.0.0
 *
 * @param array $plugins List of plugin dependencies.
 *
 * @return array
 */
function mai_require_genesis_connect( $plugins ) {
	if ( class_exists( 'WooCommerce' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for WooCommerce',
			'host'     => 'wordpress',
			'slug'     => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'      => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'optional' => false,
		];
	}

	if ( class_exists( 'Easy_Digital_Downloads' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for EDD',
			'host'     => 'wordpress',
			'slug'     => 'easy-digital-downloads/easy-digital-downloads.php',
			'uri'      => 'https://wordpress.org/plugins/easy-digital-downloads/',
			'optional' => false,
		];
	}

	return $plugins;
}
