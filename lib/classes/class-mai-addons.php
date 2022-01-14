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
class Mai_Addons {
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
		add_action( 'wp_ajax_mai_addons_action', [ $this, 'ajax' ] );
		add_action( 'mai_addons_page',           [ $this, 'page' ] );
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

		check_ajax_referer( 'mai-addons', 'nonce' );

		$succes = false;
		$addons = $this->get_addons();
		$action = filter_input( INPUT_GET, 'trigger', FILTER_SANITIZE_STRING );
		$slug   = filter_input( INPUT_GET, 'slug', FILTER_SANITIZE_STRING );

		// ray( $addons );

		if ( $addons && $action && $slug ) {
			$key = sprintf( '%s/%s.php', $slug, $slug );

			if ( 'activate' === $action && $this->has_wpdi && isset( $addons[ $slug ] ) ) {
				$addon  =  $addons[ $slug ];
				$config = [ $key => $addons[ $slug ] ];

				unset( $config[ $key ]['desc'] );
				unset( $config[ $key ]['docs'] );
				unset( $config[ $key ]['settings'] );

				WP_Dependency_Installer::instance()->register( $config )->admin_init();

				wp_send_json_success(
					[
						'message' => 'Plugin activated!',
						'html'    => $this->get_deactivate_button( $slug ),
						'active'  => true,
					]
				);

			} elseif ( 'deactivate' === $action ) {
				deactivate_plugins( $key );

				wp_send_json_success(
					[
						'message' => 'Plugin deactivated!',
						'html'    => $this->get_activate_button( $slug ),
						'active'  => false,
					]
				);
			}
		}

		wp_send_json_error( [ 'error' => 'Sorry, something went wrong.' ] );

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

		$addons       = $this->get_addons();
		$can_activate = current_user_can( 'activate_plugins' );
		$can_install  = current_user_can( 'install_plugins' );
		$theme_link   = '<a target="_blank" rel="noopener" href="https://bizbudding.com/mai-theme/">Mai Theme</a>';
		$plugins_link = '<a target="_blank" rel="noopener" href="https://bizbudding.com/mai-design-pack/">Mai Design Pack</a>';

		echo '<div class="wrap">';
			echo '<h1 class="wp-heading-inline">Mai Add-on Plugins</h1>';
			printf( '<div class="mai-addons-description%s">', class_exists( 'Mai_Design_Pack' ) ? ' has-design-pack' : '' );
				echo '<div class="mai-addons-content">';
					printf( '<p>%s %s</p>',
						sprintf( esc_html__( 'The Mai Design Pack plugin provides everything you need to enhance your website once it\'s up and running on %s.', 'mai-engine' ), $theme_link ),
						sprintf( esc_html__( 'Learn more about pro add-ons and the pattern library included with the %s.', 'mai-engine' ), $plugins_link )
					);
				echo '</div>';

				if ( ! class_exists( 'Mai_Design_Pack' ) ) {
					echo '<div class="mai-addons-cta">';
						printf( '<p><a target="_blank" rel="noopener" href="https://bizbudding.com/mai-design-pack/" class="button button-primary">%s</a></p>', sprintf( '%s Mai Design Pack', esc_html__( 'Get', 'mai-engine' ) ) );
						printf( '<p><a target="_blank" rel="noopener" href="https://bizbudding.com/my-account/">%s  →</a></p>', sprintf( 'BizBudding %s', esc_html__( 'Account', 'mai-engine' ) ) );
					echo '</div>';
				}
			echo '</div>';

			echo '<div class="mai-addons">';

				foreach ( $addons as $slug => $addon ) {
					$addon = wp_parse_args( $addon,
						[
							'desc'     => '',
							'docs'     => '',
							'settings' =>  '',
						]
					);

					$plugin_slug  = sprintf( '%s/%s.php', $slug, $slug );
					$is_installed = $this->is_installed( $plugin_slug );
					$is_active    = $this->is_active( $plugin_slug );
					$class        = 'mai-addon';
					$class       .= $is_active ? ' mai-addon-is-active' : '';

					printf( '<div class="%s">', $class );

						printf( '<h2 class="mai-addon-name">%s</h2>', $addon['name'] );
						printf( '<p>%s</p>', $addon['desc'] );
						echo '<p class="mai-addon-actions">';

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

						echo '<p class="mai-addon-links">';

							if ( $addon['settings'] && $is_installed ) {
								printf( '<a class="mai-addon-settings" href="%s"><span class="dashicons dashicons-admin-generic"></span> %s</a>', $addon['settings'], __( 'Settings', 'mai-engine' ) );
							}

							if ( $addon['docs'] ) {
								printf( '<a class="mai-addon-docs" target="_blank" rel="noopener" href="%s"><span class="dashicons dashicons-media-document"></span> %s</a>', $addon['docs'], __( 'Documentation', 'mai-engine' ) );
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
		$disabled = $this->is_disabled() ? ' disabled' : '';
		$html     = sprintf( '<span class="mai-addon-active">%s</span>', __( 'Active', 'mai-engine' ) );
		$html     .= sprintf( '<button class="mai-addon-deactivate button button-secondary" data-action="deactivate" data-slug="%s"%s>%s</button>', $slug, $disabled, __( 'Deactivate', 'mai-engine' ) );
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
		$disabled = $this->is_disabled() ? ' disabled' : '';
		return sprintf( '<button class="mai-addon-activate button button-primary" data-action="activate" data-slug="%s"%s>%s</button>', $slug, $disabled, __( 'Activate', 'mai-engine' ) );
	}

	/**
	 * Gets install button markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_install_button( $slug ) {
		$disabled = $this->is_disabled() ? ' disabled' : '';
		return sprintf( '<button class="mai-addon-install button button-primary" data-action="activate" data-slug="%s"%s>%s</button>', $slug, $disabled, __( 'Install & Activate', 'mai-engine' ) );
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
	 * Gets a dependency array data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	// function get_addon( $slug ) {
	// 	$dependencies = $this->get_addons();
	// 	return isset( $dependencies[ $slug ] ) ? $dependencies[ $slug ] : [];
	// }

	/**
	 * Gets all dependency data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_addons() {
		static $addons = null;

		if ( ! is_null( $addons ) ) {
			return $addons;
		}

		$addons = [
			'mai-icons' => [
				'name'     => 'Mai Icons',
				'host'     => 'github',
				'slug'     => 'mai-icons/mai-icons.php',
				'uri'      => 'maithemewp/mai-icons',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Include unique icons on your website with the Mai Icons plugin. There are over 7000 icons to choose from! Customization options include size, color, spacing, and more.', 'mai-engine' ),
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
				'desc'     => __( 'With Mai Testimonials, show off all the great things your customers have to say about you, while building credibility and increasing conversions.', 'mai-engine' ),
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
				'desc'     => __( 'Use Mai Favorites to give your visitors a way to easily browse collections of your favorite things such as affiliate products, recommendations, services, and more.', 'mai-engine' ),
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
				'desc'     => __( 'Mai Portfolio is a versatile and lightweight portfolio plugin for Mai Theme. It creates a custom post type called “Portfolio” that has all of our Customizer layout settings ready to customize.', 'mai-engine' ),
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
				'desc'     => __( 'Mai Accordion is perfect for displaying expandable FAQs, transcripts, resources, and even research. Add a title/question, then easily insert any block you want into the answer section.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/147-mai-accordian',
			],
			'mai-notices' => [
				'name'     => 'Mai Notices',
				'host'     => 'github',
				'slug'     => 'mai-notices/mai-notices.php',
				'uri'      => 'maithemewp/mai-notices',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Use our Mai Notices plugin to display custom callout notices to grab attention and share special information in any content area on your posts, pages, and products.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/142-mai-notices',
			],
			'mai-table-of-contents' => [
				'name'     => 'Mai Table of Contents',
				'host'     => 'github',
				'slug'     => 'mai-table-of-contents/mai-table-of-contents.php',
				'uri'      => 'maithemewp/mai-table-of-contents',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Add the Mai Table of Contents to the beginning of your posts or pages to improve readability. The table is auto-created from your heading structure so readers can jump to the section they want easily.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/145-mai-table-of-content',
				'settings' => admin_url( 'admin.php?page=mai-table-of-contents' ),
			],
			'mai-custom-content-areas' => [
				'name'     => 'Mai Custom Content Areas',
				'host'     => 'github',
				'slug'     => 'mai-custom-content-areas/mai-custom-content-areas.php',
				'uri'      => 'maithemewp/mai-custom-content-areas',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Mai Custom Content Areas is a game changer when it comes to creating a conversion marketing strategy on your website. Easily display calls to action and other custom content in different locations on posts, pages, and custom post types conditionally by category, tag, taxonomy, keyword, and more.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/192-mai-custom-content-areas',
			],
			'mai-ads-extra-content' => [
				'name'     => 'Mai Ads & Extra Content',
				'host'     => 'github',
				'slug'     => 'mai-ads-extra-content/mai-ads-extra-content.php',
				'uri'      => 'maithemewp/mai-ads-extra-content',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Boost your sales by easily embedding CTAs, display ads, and more, anywhere on your site, all from one simple to manage spot with Mai Ads & Extra Content. ', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/146-mai-ads-extra-content',
				'settings' => admin_url( 'edit.php?post_type=mai_template_part' ),
			],
			'mai-archive-pages' => [
				'name'     => 'Mai Archive Pages',
				'host'     => 'github',
				'slug'     => 'mai-archive-pages/mai-archive-pages.php',
				'uri'      => 'maithemewp/mai-archive-pages',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Mai Archive Pages plugin allows you to build robust and SEO-friendly archive pages with blocks. Customize the content before and after your archive content to strategically build out your archive pages for SEO.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/148-mai-archive-pages',
			],
			'mai-display-taxonomy' => [
				'name'     => 'Mai Display Taxonomy',
				'host'     => 'github',
				'slug'     => 'mai-display-taxonomy/mai-display-taxonomy.php',
				'uri'      => 'maithemewp/mai-display-taxonomy',
				'branch'   => 'master',
				'required' => true,
				'desc'     => __( 'Mai Display Taxonomy is a utility plugin that creates a category to use with Mai Post Grid. It gives you total control over your grid content in various areas of your website.', 'mai-engine' ),
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
				'desc'     => __( 'A developer plugin to help set custom defaults for a custom Mai Theme. The config.php file is the “default settings” for your site. If you install your custom theme, or site managers change any of the Customizer settings, the defaults will now come from your custom config.', 'mai-engine' ),
				'docs'     => 'https://help.bizbudding.com/article/193-mai-config-generator',
				'settings' => admin_url( 'admin.php?page=mai-config-generator' ),
			],
		];

		$addons = apply_filters( 'mai_addons', $addons );

		return $addons;
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
