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
