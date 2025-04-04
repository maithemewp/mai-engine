<?php
/**
 * Mai Engine.
 *
 * Plugin Name:       Mai Engine
 * Plugin URI:        https://bizbudding.com/mai-theme/
 * GitHub Plugin URI: https://github.com/maithemewp/mai-engine/
 * Description:       The required plugin to power Mai child themes.
 * Version:           2.36.1-beta.2
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            BizBudding
 * Author URI:        https://bizbudding.com/
 * Text Domain:       mai-engine
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /assets/lang
 *
 * @package   BizBudding\MaiEngine
 * @author    BizBudding <info@bizbudding.com>
 * @license   GPL-2.0-or-later
 * @link      https://bizbudding.com/
 * @copyright 2020 BizBudding
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_Engine Class.
 *
 * @since 2.18.0
 */
final class Mai_Engine {

	/**
	 * @var   Mai_Engine The one true Mai_Engine
	 * @since 2.18.0
	 */
	private static $instance;

	/**
	 * Main Mai_Engine Instance.
	 *
	 * Insures that only one instance of Mai_Engine exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   2.18.0
	 * @static  var array $instance
	 * @uses    Mai_Engine::setup_constants() Setup the constants needed.
	 * @uses    Mai_Engine::includes() Include the required files.
	 * @uses    Mai_Engine::hooks() Activate, deactivate, etc.
	 * @see     Mai_Engine()
	 * @return  object | Mai_Engine The one true Mai_Engine
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Engine;
			// Methods.
			self::$instance->includes();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   2.18.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-engine' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   2.18.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-engine' ), '1.0' );
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   2.18.0
	 * @return  void
	 */
	private function includes() {
		require_once __DIR__ . '/vendor/autoload.php';
		require_once __DIR__ . '/lib/init.php';
	}
}

/**
 * The main function for that returns Mai_Engine
 *
 * The main function responsible for returning the one true Mai_Engine
 * Instance to functions everywhere.
 *
 * @since 2.18.0
 *
 * @return object|Mai_Engine The one true Mai_Engine Instance.
 */
function mai_engine() {
	return Mai_Engine::instance();
}

// Get Mai_Engine Running.
mai_engine();
