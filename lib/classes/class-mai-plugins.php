<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2022 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Main Mai_Design_Pack Class.
 *
 * @since 0.1.0
 */
class Mai_Plugins {
	/**
	 * Entry.
	 *
	 * @var bool $has_wpdi
	 */
	protected $has_wpdi;

	/**
	 * Get is started.
	 */
	function __construct() {
		$this->has_wpdi = class_exists( 'WP_Dependency_Installer' );
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_ajax_mai_plugins_action', [ $this, 'ajax' ] );
		add_action( 'mai_plugins_page',           [ $this, 'page' ] );
	}

	/**
	 * Runs ajax to install, activate, or deactivate plugins.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function ajax() {
		if ( ! $this->has_wpdi ) {
			return;
		}

		check_ajax_referer( 'mai-plugins', 'nonce' );

		$succes = false;
		$plugins = $this->get_plugins();
		$action = filter_input( INPUT_GET, 'trigger', FILTER_SANITIZE_STRING );
		$slug   = filter_input( INPUT_GET, 'slug', FILTER_SANITIZE_STRING );

		if ( $plugins && $action && $slug ) {
			$key = sprintf( '%s/%s.php', $slug, $slug );

			if ( in_array( $action, [ 'install', 'activate' ] ) && $this->has_wpdi && isset( $plugins[ $slug ] ) ) {
				$plugin  =  $plugins[ $slug ];
				$config = [ $key => $plugins[ $slug ] ];

				unset( $config[ $key ]['desc'] );
				unset( $config[ $key ]['docs'] );
				unset( $config[ $key ]['settings'] );

				WP_Dependency_Installer::instance()->register( $config )->admin_init();

				wp_send_json_success(
					[
						'message' => esc_html__( 'Plugin activated!', 'mai-engine' ),
						'html'    => $this->get_deactivate_button( $slug ),
						'active'  => true,
					]
				);

			} elseif ( 'deactivate' === $action ) {
				deactivate_plugins( $key );

				wp_send_json_success(
					[
						'message' => esc_html__( 'Plugin deactivated!', 'mai-engine' ),
						'html'    => $this->get_activate_button( $slug ),
						'active'  => false,
					]
				);
			}
		}

		wp_send_json_error( [ 'error' => esc_html__( 'Sorry, something went wrong.', 'mai-engine' ) ] );

		wp_die();
	}

	/**
	 * Renders admin menu page content.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function page() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins      = $this->get_plugins();
		$can_activate = current_user_can( 'activate_plugins' );
		$can_install  = current_user_can( 'install_plugins' );
		$plugins_url  = add_query_arg(
			[
				'utm_source'    => 'engine',
				'utm_medium'    => 'mai-design-pack',
				'utm_campaign'  => 'mai-design-pack',
			],
			'https://bizbudding.com/mai-design-pack/'
		);
		$theme_link   = '<a target="_blank" rel="noopener" href="https://bizbudding.com/mai-theme/">Mai Theme</a>';
		$plugins_link = sprintf( '<a target="_blank" rel="noopener" href="%s">Mai Design Pack</a>', $plugins_url );

		echo '<div class="wrap">';
			echo '<h1 class="wp-heading-inline">Mai Plugins</h1>';
			printf( '<div class="mai-plugins-description%s">', class_exists( 'Mai_Design_Pack' ) ? ' has-design-pack' : '' );
				echo '<div class="mai-plugins-content">';
					printf( '<p>%s %s</p>',
						sprintf( esc_html__( 'The Mai Design Pack plugin provides everything you need to enhance your website once it\'s up and running on %s.', 'mai-engine' ), $theme_link ),
						sprintf( esc_html__( 'Learn more about pro plugins and the pattern library included with the %s.', 'mai-engine' ), $plugins_link )
					);
				echo '</div>';

				if ( ! class_exists( 'Mai_Design_Pack' ) ) {
					echo '<div class="mai-plugins-cta">';
						printf( '<p><a target="_blank" rel="noopener" href="%s" class="button button-primary">%s</a></p>', $plugins_url, sprintf( '%s Mai Design Pack', esc_html__( 'Get', 'mai-engine' ) ) );
						printf( '<p><a target="_blank" rel="noopener" href="https://bizbudding.com/my-account/">%s  →</a></p>', sprintf( 'BizBudding %s', esc_html__( 'Account', 'mai-engine' ) ) );
					echo '</div>';
				}
			echo '</div>';

			echo '<div class="mai-plugins">';

				foreach ( $plugins as $slug => $plugin ) {
					$plugin = wp_parse_args( $plugin,
						[
							'desc'     => '',
							'docs'     => '',
							'settings' =>  '',
						]
					);

					$plugin_slug  = sprintf( '%s/%s.php', $slug, $slug );
					$is_installed = $this->is_installed( $plugin_slug );
					$is_active    = $this->is_active( $plugin_slug );
					$class        = 'mai-plugin';
					$class       .= $is_active ? ' mai-plugin-is-active' : '';

					printf( '<div class="%s">', $class );

						printf( '<h2 class="mai-plugin-name">%s</h2>', $plugin['name'] );
						printf( '<p>%s</p>', $plugin['desc'] );
						echo '<p class="mai-plugin-actions">';

							if ( $this->has_wpdi ) {
								if ( $is_installed ) {
									if ( $can_activate ) {
										if ( $is_active ) {
											echo $this->get_deactivate_button( $slug );
										} else {
											echo $this->get_activate_button( $slug );
										}
									}
								} else {
									if ( $can_install ) {
										echo $this->get_install_button( $slug );
									}
								}
							}

						echo '</p>';

						echo '<p class="mai-plugin-links">';

							if ( $plugin['settings'] && $is_installed ) {
								printf( '<a class="mai-plugin-settings" href="%s"><span class="dashicons dashicons-admin-generic"></span> %s</a>', $plugin['settings'], __( 'Settings', 'mai-engine' ) );
							}

							if ( $plugin['docs'] ) {
								printf( '<a class="mai-plugin-docs" target="_blank" rel="noopener" href="%s"><span class="dashicons dashicons-media-document"></span> %s</a>', $plugin['docs'], __( 'Documentation', 'mai-engine' ) );
							}

						echo '</p>';

					echo '</div>';
				}

			echo '</div>';
		echo '</div>'; /* .wrap */
	}

	/**
	 * Gets deactivate button markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_deactivate_button( $slug ) {
		$html  = sprintf( '<span class="mai-plugin-active">%s</span>', __( 'Active', 'mai-engine' ) );
		$html .= $this->get_button( 'deactivate', 'secondary', __( 'Deactivate', 'mai-engine' ), $slug );
		return $html;
	}

	/**
	 * Gets activate button markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_activate_button( $slug ) {
		return $this->get_button( 'activate', 'primary', __( 'Activate', 'mai-engine' ), $slug );
	}

	/**
	 * Gets install button markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_install_button( $slug ) {
		return $this->get_button( 'install', 'primary', __( 'Install & Activate', 'mai-engine' ), $slug );
	}

	/**
	 * Gets button markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_button( $action, $class, $text, $slug ) {
		$data_disabled = $this->is_disabled() ? sprintf( ' data-disabled="Mai Design Pack %s."', esc_html__( 'required', 'mai-engine' ) ) : '';
		$disabled      = $this->is_disabled() ? ' disabled' : '';

		return sprintf( '<button class="mai-plugin-%s button button-%s" data-action="%s" data-slug="%s"%s%s>%s</button>',
			$action,
			$class,
			$action,
			$slug,
			$data_disabled,
			$disabled,
			$text
		);
	}

	/**
	 * Checks if a plugin is installed.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function is_installed( $plugin_slug ) {
		$plugins = $this->get_installed_plugins();
		return array_key_exists( $plugin_slug, $plugins ) || in_array( $plugin_slug, $plugins, true );
	}

	/**
	 * Checks if buttons should be disabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function is_disabled() {
		static $bool = null;

		if ( ! is_null( $bool ) ) {
			return $bool;
		}

		$bool = ! ( $this->has_wpdi && class_exists( 'Mai_Design_Pack' ) );

		return $bool;
	}

	/**
	 * Checks if a plugin is active.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	function is_active( $plugin_slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_slug );
	}

	/**
	 * Gets all installed plugins.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_installed_plugins() {
		static $plugins = null;

		if ( ! is_null( $plugins ) ) {
			return $plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		return $plugins;
	}

	/**
	 * Gets all dependency data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_plugins() {
		static $plugins = null;

		if ( ! is_null( $plugins ) ) {
			return $plugins;
		}

		$plugins = [
			'mai-icons' => [
				'name'     => 'Mai Icons',
				'host'     => 'github',
				'slug'     => 'mai-icons/mai-icons.php',
				'uri'      => 'maithemewp/mai-icons',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Include unique icons on your website with the Mai Icons plugin. There are over 7000 icons to choose from! Customization options include size, color, spacing, and more.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/143-mai-icons',
				'settings' => '',
			],
			'mai-testimonials' => [
				'name'     => 'Mai Testimonials',
				'host'     => 'github',
				'slug'     => 'mai-testimonials/mai-testimonials.php',
				'uri'      => 'maithemewp/mai-testimonials',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'With Mai Testimonials, show off all the great things your customers have to say about you, while building credibility and increasing conversions.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/141-mai-testimonials',
				'settings' => admin_url( 'edit.php?post_type=testimonial' ),
			],
			'mai-favorites' => [
				'name'     => 'Mai Favorites',
				'host'     => 'github',
				'slug'     => 'mai-favorites/mai-favorites.php',
				'uri'      => 'maithemewp/mai-favorites',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Use Mai Favorites to give your visitors a way to easily browse collections of your favorite things such as affiliate products, recommendations, services, and more.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/144-mai-favorites',
				'settings' => admin_url( 'edit.php?post_type=favorite' ),
			],
			'mai-portfolio' => [
				'name'     => 'Mai Portfolio',
				'host'     => 'github',
				'slug'     => 'mai-portfolio/mai-portfolio.php',
				'uri'      => 'maithemewp/mai-portfolio',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Portfolio is a versatile and lightweight portfolio plugin for Mai Theme. It creates a custom post type called “Portfolio” that has all of our Customizer layout settings ready to customize.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/191-mai-portfolio',
				'settings' => admin_url( 'edit.php?post_type=portfolio' ),
			],
			'mai-accordion' => [
				'name'     => 'Mai Accordion',
				'host'     => 'github',
				'slug'     => 'mai-accordion/mai-accordion.php',
				'uri'      => 'maithemewp/mai-accordion',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Accordion is perfect for displaying expandable FAQs, transcripts, resources, and even research. Add a title/question, then easily insert any block you want into the answer section.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/147-mai-accordian',
			],
			'mai-galleries' => [
				'name'     => 'Mai Galleries',
				'host'     => 'github',
				'slug'     => 'mai-galleries/mai-galleries.php',
				'uri'      => 'maithemewp/mai-galleries',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Galleries allows you to easily create responsive image galleries with optional image lightbox.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/207-mai-galleries',
			],
			'mai-lists' => [
				'name'     => 'Mai Lists',
				'host'     => 'github',
				'slug'     => 'mai-lists/mai-lists.php',
				'uri'      => 'maithemewp/mai-lists',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Lists is a versatile block to create simple and beautiful icon or numbered lists and responsive icon feature grids.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/208-mai-lists',
			],
			'mai-notices' => [
				'name'     => 'Mai Notices',
				'host'     => 'github',
				'slug'     => 'mai-notices/mai-notices.php',
				'uri'      => 'maithemewp/mai-notices',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Use our Mai Notices plugin to display custom callout notices to grab attention and share special information in any content area on your posts, pages, and products.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/142-mai-notices',
			],
			'mai-table-of-contents' => [
				'name'     => 'Mai Table of Contents',
				'host'     => 'github',
				'slug'     => 'mai-table-of-contents/mai-table-of-contents.php',
				'uri'      => 'maithemewp/mai-table-of-contents',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Add the Mai Table of Contents to the beginning of your posts or pages to improve readability. The table is auto-created from your heading structure so readers can jump to the section they want easily.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/145-mai-table-of-contents',
				'settings' => admin_url( 'admin.php?page=mai-table-of-contents' ),
			],
			'mai-custom-content-areas' => [
				'name'     => 'Mai Custom Content Areas',
				'host'     => 'github',
				'slug'     => 'mai-custom-content-areas/mai-custom-content-areas.php',
				'uri'      => 'maithemewp/mai-custom-content-areas',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Custom Content Areas is a game changer when it comes to creating a conversion marketing strategy on your website. Easily display calls to action and other custom content in different locations on posts, pages, and custom post types conditionally by category, tag, taxonomy, keyword, and more.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/192-mai-custom-content-areas',
			],
			'mai-archive-pages' => [
				'name'     => 'Mai Archive Pages',
				'host'     => 'github',
				'slug'     => 'mai-archive-pages/mai-archive-pages.php',
				'uri'      => 'maithemewp/mai-archive-pages',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Archive Pages plugin allows you to build robust and SEO-friendly archive pages with blocks. Customize the content before and after your archive content to strategically build out your archive pages for SEO.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/148-mai-archive-pages',
			],
			'mai-display-taxonomy' => [
				'name'     => 'Mai Display Taxonomy',
				'host'     => 'github',
				'slug'     => 'mai-display-taxonomy/mai-display-taxonomy.php',
				'uri'      => 'maithemewp/mai-display-taxonomy',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'Mai Display Taxonomy is a utility plugin that creates a category to use with Mai Post Grid. It gives you total control over your grid content in various areas of your website.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/149-mai-display-taxonomy',
				'settings' => admin_url( 'admin.php?page=mai-display-taxonomy' ),
			],
			'mai-config-generator' => [
				'name'     => 'Mai Config Generator',
				'host'     => 'github',
				'slug'     => 'mai-config-generator/mai-config-generator.php',
				'uri'      => 'maithemewp/mai-config-generator',
				'branch'   => 'master',
				'required' => true,
				'desc'     => esc_html__( 'A developer plugin to help set custom defaults for a custom Mai Theme. The config.php file is the “default settings” for your site. If you install your custom theme, or site managers change any of the Customizer settings, the defaults will now come from your custom config.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/193-mai-config-generator',
				'settings' => admin_url( 'admin.php?page=mai-config-generator' ),
			],
			// 'mai-ads-extra-content' => [
			// 	'name'     => sprintf( 'Mai Ads & Extra Content (%s)', esc_html( 'legacy', 'mai-engine' ) ),
			// 	'host'     => 'github',
			// 	'slug'     => 'mai-ads-extra-content/mai-ads-extra-content.php',
			// 	'uri'      => 'maithemewp/mai-ads-extra-content',
			// 	'branch'   => 'master',
			// 	'required' => true,
			// 	'desc'     => sprintf( esc_html__( 'Boost your sales by easily embedding CTAs, display ads, and more, anywhere on your site, all from one simple to manage location.%s', 'mai-engine' ), sprintf( '<br><br><strong>%s</strong>', sprintf( esc_html__( 'Note: This plugin has been replaced with %s', 'mai-engine' ), 'Mai Custom Content Areas' ) ) ),
			// 	'docs'     => 'https://help.bizbudding.com/article/146-mai-ads-extra-content',
			// 	'settings' => admin_url( 'admin.php?page=mai_aec' ),
			// ],
		];

		$plugins = apply_filters( 'mai_plugins', $plugins );

		return $plugins;
	}

	/**
	 * Return the plugin action links. This will only be called if the plugin is active.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $actions     Associative array of action names to anchor tags
	 * @param string $plugin_file Plugin file name, ie my-plugin/my-plugin.php
	 * @param array  $plugin_data Associative array of plugin data from the plugin file headers
	 * @param string $context     Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
	 *
	 * @return array associative array of plugin action links
	 */
	function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		$url                 = admin_url( sprintf( '%s.php?page=mai-theme', 'admin' ) );
		$link                = sprintf( '<a href="%s">%s</a>', $url, __( 'Settings', 'mai-engine' ) );
		$actions['settings'] = $link;

		return $actions;
	}
}
