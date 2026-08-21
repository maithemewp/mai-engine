<?php
/**
 * Loads the pieces of mai-engine under test, on muplugins_loaded.
 *
 * The plugin is deliberately NOT activated. lib/init.php expects Genesis as the parent
 * theme, and Genesis is neither in this repo nor composer-installable, so a real activation
 * cannot run here or in CI. That makes this suite "WordPress-loaded tests" rather than
 * full-plugin integration: real WP_HTML_Tag_Processor, WP_Query, options and posts, but no
 * Genesis-dependent code path.
 *
 * Add files here as tests need them, and keep the list curated. Requiring all of lib/ would
 * drag in the Genesis dependency this design exists to avoid.
 */

$plugin_root = dirname( __DIR__, 3 );

// The plugin's committed runtime autoloader. Note this is not inert: it files-autoloads
// plugin-update-checker (registers an autoloader and factory versions) and mai-cache
// (defines Mai_Cache_Bootstrap, registers an autoloader). This suite therefore depends on
// the committed autoloader being valid, which deployable-guard enforces.
require_once $plugin_root . '/vendor/autoload.php';

require_once $plugin_root . '/lib/functions/helpers.php';
require_once $plugin_root . '/lib/blocks/general.php';

// Only registers hooks and declares functions at load. Its init callback bails on
// ! is_admin(), so the Genesis-dependent service providers are never constructed here.
require_once $plugin_root . '/lib/admin/setup-wizard.php';

// Mirrors the runtime autoloader in lib/functions/autoload.php without loading it, because
// that file depends on mai_get_dir() from lib/init.php, which drags in Genesis. Same mapping:
// Mai_Grid -> lib/classes/class-mai-grid.php.
spl_autoload_register(
	static function ( $class ) use ( $plugin_root ) {
		if ( ! str_starts_with( $class, 'Mai_' ) ) {
			return;
		}

		$file = $plugin_root . '/lib/classes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// Mai_Grid::__construct() calls get_sanitized_args() and get_defaults(), which need the
// display, layout and query field helpers. These declare functions and register ACF hooks;
// they do not need Genesis.
require_once $plugin_root . '/lib/fields/grid-display.php';
require_once $plugin_root . '/lib/fields/grid-layout.php';
require_once $plugin_root . '/lib/fields/wp-query.php';

// MUST come before query-cache.php. That file hooks Mai_Query_Cache::on_delete to
// deleted_post, and wp-phpunit's bootstrap calls _delete_all_posts() while setting up, which
// fires that hook and calls mai_cache(). Without this require the whole bootstrap dies with
// "Call to undefined function mai_cache()" before a single test runs, including the two that
// already pass today. Measured, not theoretical.
require_once $plugin_root . '/lib/functions/cache.php';

// Registers Mai_Query_Cache on posts_pre_query and the_posts. Hooks on init, so it has to be
// required here on muplugins_loaded; requiring it from inside a test is too late.
require_once $plugin_root . '/lib/functions/query-cache.php';
