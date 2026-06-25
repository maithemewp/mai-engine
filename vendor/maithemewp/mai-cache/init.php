<?php
/**
 * Mai Cache - bootstrap.
 *
 * Loaded automatically by Composer (via "autoload": { "files": [...] })
 * when each plugin's vendor/autoload.php is required.
 *
 * Registers this plugin's bundled Mai\Cache version into a shared registry.
 * On first request for any Mai\Cache\* class, the autoloader picks the highest
 * registered version's src/ directory and loads from there.
 *
 * Bootstrap protocol - FROZEN. Never change Mai_Cache_Bootstrap::register()'s
 * signature. Old plugins out in the wild call the original signature on
 * whichever bootstrap loaded first.
 *
 * @see https://github.com/maithemewp/mai-logger - the original pattern.
 *
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Mai_Cache_Bootstrap', false ) ) {
	/**
	 * Tiny registry for Mai\Cache versions across plugins.
	 *
	 * First plugin to load defines this class. All subsequent plugins
	 * call register() on this same class.
	 *
	 * @since 0.1.0
	 */
	class Mai_Cache_Bootstrap {

		/**
		 * Registered versions: [ '0.1.0' => '/abs/path/to/src', ... ].
		 *
		 * @since 0.1.0
		 *
		 * @var array<string,string>
		 */
		private static array $versions = [];

		/**
		 * Whether the autoloader has been registered yet.
		 *
		 * @since 0.1.0
		 *
		 * @var bool
		 */
		private static bool $autoloader_registered = false;

		/**
		 * Register a bundled Mai\Cache version + path to its src/ directory.
		 *
		 * Signature is frozen; do not change.
		 *
		 * @since 0.1.0
		 *
		 * @param string $version  Semver version string of the bundled library.
		 * @param string $src_path Absolute path to the src/ directory.
		 */
		public static function register( string $version, string $src_path ): void {
			self::$versions[ $version ] = rtrim( $src_path, '/' );

			if ( self::$autoloader_registered ) {
				return;
			}

			self::$autoloader_registered = true;

			spl_autoload_register( static function ( string $class ): void {
				// Only handle Mai\Cache\* classes.
				if ( ! str_starts_with( $class, 'Mai\\Cache\\' ) ) {
					return;
				}

				if ( empty( self::$versions ) ) {
					return;
				}

				// Pick the highest registered version's src/ dir.
				uksort( self::$versions, 'version_compare' );
				$src = end( self::$versions );

				// PSR-4 style: Mai\Cache\Cache → $src/Cache.php
				$relative = substr( $class, strlen( 'Mai\\Cache\\' ) );
				$file     = $src . '/' . str_replace( '\\', '/', $relative ) . '.php';

				if ( is_readable( $file ) ) {
					require $file;
				}
			} );
		}
	}
}

// Register THIS plugin's bundled version. Bump the string when releasing.
Mai_Cache_Bootstrap::register( '0.2.0', __DIR__ . '/src' );
