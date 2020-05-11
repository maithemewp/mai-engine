<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Admin extends AbstractServiceProvider {

	public function add_hooks() {
		\add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'load_styles' ] );
	}

	public function add_menu_page() {
		$args = $this->get_menu_defaults();

		if ( isset( $args['parent_slug'] ) && $args['parent_slug'] ) {
			unset ( $args['icon_url'] );

			\add_submenu_page( ...\array_values( $args ) );

		} else {
			unset ( $args['parent_slug'] );

			\add_menu_page( ...\array_values( $args ) );
		}
	}

	private function get_menu_defaults() {
		return \apply_filters(
			'mai_setup_wizard_menu',
			[
				'parent_slug' => 'themes.php',
				'page_title'  => $this->plugin->name,
				'menu_title'  => $this->plugin->name,
				'capability'  => 'manage_options',
				'menu_slug'   => $this->plugin->slug,
				'function'    => [ $this, 'render_admin_page' ],
				'icon_url'    => '',
				'position'    => null,
			]
		);
	}

	public function render_admin_page() {
		?>
		<div class="setup-wizard">
			<h1><?php echo \esc_html( $this->plugin->name ); ?></h1>
			<?php
			\do_action( 'mai_setup_wizard_before_steps' );

			$steps   = $this->step->get_steps();
			$counter = 1;

			/**
			 * @var Step $step
			 */
			foreach ( $steps as $step ) {
				$this->step->render( $step, $steps, $counter );
				$counter++;
			}

			\do_action( 'mai_setup_wizard_after_steps' );
			?>
			<p>
				<a href="<?php echo \admin_url(); ?>"><?php esc_html_e( 'Return to dashboard', 'mai-setup-wizard' ); ?></a>
			</p>
		</div>
		<?php
	}

	private function is_setup_wizard_screen() {
		$base = false;

		if ( \function_exists( 'get_current_screen' ) ) {
			$current = \get_current_screen();
			$base    = isset( $current->base ) ? $current->base : false;
		}

		$menu   = $this->get_menu_defaults();
		$screen = '';

		if ( isset( $menu['parent_slug'] ) ) {
			$screen .= $menu['parent_slug'];
		}

		$screen .= '_page_';
		$screen .= $menu['menu_slug'];

		return $screen === $base;
	}

	public function load_scripts() {
		if ( ! $this->is_setup_wizard_screen() ) {
			print_r($this->is_setup_wizard_screen());
			return;
		}

		$file = 'resources/js/setup-wizard.js';
		$demo = $this->demo->get_chosen_demo();

		\wp_enqueue_script(
			$this->plugin->slug,
			$this->plugin->url . $file,
			[ 'jquery' ],
			$this->get_asset_version( $file ),
			true
		);

		\wp_localize_script(
			$this->plugin->slug,
			'setupWizardData',
			[
				'ajaxUrl'     => \admin_url( 'admin-ajax.php' ),
				'currentStep' => ( isset( $_GET['step'] ) ? \esc_attr( $_GET['step'] ) : 'welcome' ),
				'chosenDemo'  => $demo,
			]
		);
	}

	public function load_styles() {
		if ( ! $this->is_setup_wizard_screen() ) {
			return;
		}

		$file = 'resources/css/setup-wizard.css';

		\wp_enqueue_style(
			$this->plugin->slug,
			$this->plugin->url . $file,
			[],
			$this->get_asset_version( $file ),
			'all'
		);
	}

	private function get_asset_version( $file ) {
		$version = $this->plugin->version;

		if ( ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) || ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ) {
			$version = filemtime( $this->plugin->dir . $file );
		}

		return $version;
	}
}
