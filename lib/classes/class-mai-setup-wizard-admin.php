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

/**
 * Class Mai_Setup_Wizard_Admin
 */
class Mai_Setup_Wizard_Admin extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * Adds hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Adds the menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_menu_page() {
		$args = $this->get_menu_defaults();

		if ( isset( $args['parent_slug'] ) && $args['parent_slug'] ) {
			unset( $args['icon_url'] );

			add_submenu_page( ...array_values( $args ) );

		} else {
			unset( $args['parent_slug'] );

			add_menu_page( ...array_values( $args ) );
		}
	}

	/**
	 * Returns menu defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_menu_defaults() {
		return apply_filters(
			'mai_setup_wizard_menu',
			[
				'parent_slug' => 'themes.php',
				'page_title'  => $this->name,
				'menu_title'  => $this->name,
				'capability'  => 'manage_options',
				'menu_slug'   => $this->slug,
				'function'    => [ $this, 'render_admin_page' ],
				'icon_url'    => '',
				'position'    => null,
			]
		);
	}

	/**
	 * Renders admin page markup.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div class="setup-wizard">
			<h1><?php echo esc_html( $this->name ); ?></h1>
			<?php
			do_action( 'mai_setup_wizard_before_steps' );

			$steps   = $this->steps->get_steps();
			$counter = 1;

			/**
			 * Steps.
			 *
			 * @var Mai_Setup_Wizard_Steps $step Step object.
			 */
			foreach ( $steps as $step ) {
				$this->steps->render( $step, $steps, $counter );
				$counter++;
			}

			do_action( 'mai_setup_wizard_after_steps' );
			?>
			<p>
				<a href="<?php echo admin_url(); ?>"><?php esc_html_e( 'Return to dashboard', 'mai-engine' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Checks if were on the setup wizard page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_setup_wizard_screen() {
		$base = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$current = get_current_screen();
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

	/**
	 * Loads setup wizard scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_scripts() {
		if ( ! $this->is_setup_wizard_screen() ) {
			return;
		}

		$file = 'assets/js/setup-wizard.js';
		$demo = $this->demos->get_chosen_demo();

		wp_enqueue_script(
			$this->slug,
			mai_get_url() . $file,
			[ 'jquery' ],
			$this->get_asset_version( $file ),
			true
		);

		wp_localize_script(
			$this->slug,
			'setupWizardData',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'currentStep' => ( isset( $_GET['step'] ) ? esc_attr( $_GET['step'] ) : 'welcome' ),
				'chosenDemo'  => $demo,
				'nonce'       => wp_create_nonce( $this->slug ),
			]
		);
	}

	/**
	 * Returns modified time asset version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Path to file.
	 *
	 * @return bool|int
	 */
	private function get_asset_version( $file ) {
		$version = mai_get_version();

		if ( ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) || ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ) {
			$version = filemtime( mai_get_dir() . $file );
		}

		return $version;
	}
}
