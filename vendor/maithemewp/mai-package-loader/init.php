<?php
/**
 * Mai Package Loader.
 *
 * Loads the newest copy of each shared mai library, whichever plugin loads
 * first. See docs/specs/2026-10-03-mai-package-loader.md for the why.
 *
 * Loaded by Composer through this package's "files" entry. Composer runs that
 * entry once per request however many plugins bundle this package, so the
 * first plugin's copy of this class serves the whole request, unless a newer
 * copy takes over (see takeOver()). That is why the public API only ever
 * grows: an old copy may be the one in charge.
 *
 * PHP 8.1, the lowest floor of any plugin that bundles it.
 */

declare( strict_types=1 );

// No ABSPATH guard. This file only defines a class and registers an
// autoloader, and an exit here would end the whole request for any site that
// loads Composer before WordPress, with nothing logged.

if ( ! class_exists( 'Mai_Package_Loader', false ) ) {
	final class Mai_Package_Loader {
		/** This copy's version. Compared against other copies to take over. */
		public const VERSION = '0.1.0';

		/** The file a shared library ships to declare itself. */
		private const DECLARATION = 'mai-package.php';

		/**
		 * The file a newer loader ships to take over from an older one. Its
		 * name and contract are frozen: see takeOver().
		 */
		private const TAKEOVER = 'takeover.php';

		/** Every class a shared library may own starts with one of these. */
		private const PREFIXES = [ 'Mai\\', 'Mai_' ];

		/** Only libraries published under this vendor are looked for. */
		private const VENDOR = 'maithemewp';

		/** This package's own Composer name. */
		private const NAME = 'maithemewp/mai-package-loader';

		/** Composer's loader class, named once. */
		private const COMPOSER = 'Composer\Autoload\ClassLoader';

		/**
		 * Library name to its copies, newest first. Null until the first
		 * request for a Mai class.
		 *
		 * @var array<string, array<int, array{name: string, version: string, dir: string, namespace: ?string, path: string, classes: array<string, string>}>>|null
		 */
		private static ?array $libraries = null;

		/**
		 * Vendor folders already read, so looking again only reads new ones.
		 *
		 * @var array<string, true>
		 */
		private static array $scanned = [];

		/** How many Composer loaders there were at the last look. */
		private static int $composerCount = -1;

		/**
		 * Copies of this loader newer than this one, folder to version, from
		 * Composer's record. Only these are ever checked on disk.
		 *
		 * @var array<string, string>
		 */
		private static array $newer = [];

		/**
		 * Copies that were skipped, declaration file to the reason, so a
		 * "class not found" can be traced without reading this code.
		 *
		 * @var array<string, string>
		 */
		private static array $rejected = [];

		/** Set once Composer's list of vendor folders turned out to be missing. */
		private static bool $noRegistry = false;

		/**
		 * Libraries checked for classes an old bootstrap already loaded.
		 *
		 * @var array<string, true>
		 */
		private static array $mixChecked = [];

		/** Set while discovery runs, so anything it triggers cannot restart it. */
		private static bool $discovering = false;

		/** The autoloader this copy registered, so a newer copy can replace it. */
		private static ?Closure $autoloader = null;

		/** Set once a newer copy has taken over. This copy then does nothing. */
		private static bool $retired = false;

		/** The newer copy's autoloader, once one has taken over. */
		private static mixed $successor = null;

		/**
		 * Puts the loader first in line, so it answers before an old library
		 * bootstrap that appended its own autoloader.
		 */
		public static function boot(): void {
			if ( null !== self::$autoloader ) {
				return;
			}

			self::$autoloader = self::load( ... );

			spl_autoload_register( self::$autoloader, true, true );

			// Any plugin, theme or must-use plugin that has loaded is in
			// Composer's list, and load() reads new entries as they appear.
			// These two look again for what has not loaded yet: active plugins
			// once WordPress can read its lists, which a drop-in asks before;
			// and the theme once it is chosen, which a preview changes after
			// plugins load and before the theme's own files run.
			$refresh = self::refresh( ... );

			self::hook( 'muplugins_loaded', $refresh, PHP_INT_MIN );
			self::hook( 'setup_theme', $refresh, PHP_INT_MAX );

			// Without Composer's list, a new vendor folder is not noticed on
			// each class request, so these two stages look again too. With
			// the list they find nothing new and cost almost nothing.
			self::hook( 'plugins_loaded', $refresh, PHP_INT_MIN );
			self::hook( 'after_setup_theme', $refresh, PHP_INT_MIN );
		}

		/**
		 * What was found, for diagnostics: each library's versions, newest
		 * first. Null until discovery has run.
		 *
		 * @return array<string, array<int, string>>|null
		 */
		public static function discovered(): ?array {
			if ( null === self::$libraries ) {
				return null;
			}

			return array_map(
				static fn( array $copies ): array => array_column( $copies, 'version' ),
				self::$libraries,
			);
		}

		/**
		 * Copies that were found and skipped, declaration file to the reason.
		 *
		 * @return array<string, string>
		 */
		public static function rejected(): array {
			return self::$rejected;
		}

		/**
		 * Adds a hook, before WordPress's hook functions exist if need be.
		 *
		 * A site may load Composer from wp-config.php. WordPress picks up
		 * hooks left in $wp_filter when it loads its plugin API.
		 */
		private static function hook( string $name, Closure $callback, int $priority ): void {
			if ( function_exists( 'add_action' ) ) {
				add_action( $name, $callback, $priority );

				return;
			}

			$GLOBALS['wp_filter'][ $name ][ $priority ][] = [
				'function'      => $callback,
				'accepted_args' => 1,
			];
		}

		private static function load( string $class ): void {
			if ( self::$retired || ! self::isMaiName( $class ) ) {
				return;
			}

			// Something discovery triggered, such as a filter on a list it
			// reads, asks for a Mai class. It gets the copies already loaded,
			// rather than a second discovery inside the first.
			if ( self::$discovering ) {
				self::loadFrom( self::merge( [], self::scan( self::composerVendors() ) ), $class );

				return;
			}

			if ( null === self::$libraries ) {
				self::discover();
			} elseif ( self::composerCount() !== self::$composerCount ) {
				// A plugin, theme or must-use plugin has loaded since the last
				// look, perhaps one being activated right now. Read its copies
				// before answering.
				self::discover( self::composerVendors() );
			}

			// A newer copy may have taken over. It owns this class now, and
			// PHP will not reliably call an autoloader registered in the
			// middle of a lookup, so it is asked here.
			if ( self::$retired ) {
				( self::$successor )( $class );

				return;
			}

			self::loadFrom( self::$libraries, $class, true );
		}

		/**
		 * Requires a class from the newest copy that has it.
		 *
		 * @param array<string, array<int, array<string, mixed>>> $libraries
		 * @param bool $prune Drop newer copies that lack a file an older copy
		 *                    has, so the rest of the library comes from one
		 *                    copy, not a mix.
		 */
		private static function loadFrom( array $libraries, string $class, bool $prune = false ): void {
			foreach ( $libraries as $name => $copies ) {
				$lacking = [];

				foreach ( $copies as $index => $copy ) {
					$file = self::fileFor( $copy, $class );

					// Not this library's class. Every copy of a library owns
					// the same names, so there is no point asking the rest.
					if ( null === $file ) {
						break;
					}

					if ( ! @is_file( $file ) ) {
						$lacking[ $index ] = $file;

						continue;
					}

					// Newest first. A newer copy lacking a file an older one
					// has is damaged, since a library's classes only ever
					// grow; say a plugin was deleted mid-request. It is
					// dropped for the rest of the request. When no copy has
					// the file, the class simply does not exist, as a
					// class_exists() check may well expect, and nothing is
					// dropped.
					if ( $prune && [] !== $lacking ) {
						foreach ( $lacking as $gone => $missing ) {
							self::reject( $copies[ $gone ]['dir'] . '/' . self::DECLARATION, 'it lacks ' . $missing . ', which an older copy has, so it was dropped for the rest of this request' );
							unset( self::$libraries[ $name ][ $gone ] );
						}

						self::$libraries[ $name ] = array_values( self::$libraries[ $name ] );
					}

					if ( $prune ) {
						self::checkMix( $name, self::$libraries[ $name ] );
					}

					require $file;

					return;
				}
			}
		}

		/**
		 * Looks again for copies not read yet. Does nothing if discovery has
		 * not run, since it will see them when it does.
		 */
		private static function refresh(): void {
			if ( null !== self::$libraries && ! self::$retired && ! self::$discovering ) {
				self::discover();
			}
		}

		/**
		 * Reads copies from the given vendor folders, or from everywhere,
		 * skipping folders read before, and hands over to a newer loader if
		 * one has arrived.
		 *
		 * Finding the folders is inside the guard too: it reads WordPress's
		 * lists, and a filter on those lists may itself use a Mai class.
		 *
		 * @param array<int, string>|null $vendors
		 */
		private static function discover( ?array $vendors = null ): void {
			self::$discovering = true;

			try {
				self::$composerCount = self::composerCount();

				$vendors ??= self::roots();
				$fresh     = [];

				foreach ( $vendors as $vendor ) {
					if ( ! isset( self::$scanned[ $vendor ] ) ) {
						self::$scanned[ $vendor ] = true;
						$fresh[]                  = $vendor;
					}
				}

				self::$libraries = self::merge( self::$libraries ?? [], self::scan( $fresh ) );
			} finally {
				self::$discovering = false;
			}

			if ( self::$noRegistry ) {
				// Recorded, not logged: it works, and a log line on every page of
				// every site running an old plugin would teach people to ignore
				// the log.
				self::note( 'Composer', 'the first Composer autoloader loaded is from Composer 1, which keeps no list of vendor folders, so they were found from each plugin\'s ComposerAutoloaderInit class instead', false );
			}

			self::takeOver();
		}

		/**
		 * Hands this request to a newer copy of the loader, if one ships the
		 * means to take over.
		 *
		 * Without this, whichever copy loads first serves the request, so a
		 * fix to the loader itself would only reach a site once every plugin
		 * bundling it had updated.
		 *
		 * Frozen contract: a copy may ship takeover.php in its root. It is
		 * included once, given nothing, and must return an autoloader,
		 * callable(string): void. That autoloader replaces this one and is
		 * handed the class being loaded, if any. A copy no newer than this
		 * one, a file that throws, or anything else returned is ignored.
		 *
		 * Also frozen: init.php declares its version on a line of its own as
		 * public const VERSION = '1.2.3'; which is read without Composer's
		 * record.
		 */
		private static function takeOver(): void {
			if ( [] === self::$newer ) {
				return;
			}

			// Newest first, and each checked on disk once.
			uasort( self::$newer, static fn( string $a, string $b ): int => version_compare( $b, $a ) );

			$newest = null;

			foreach ( self::$newer as $dir => $version ) {
				if ( @is_file( $dir . '/' . self::TAKEOVER ) ) {
					$newest = $dir;

					break;
				}
			}

			self::$newer = [];

			if ( null === $newest ) {
				return;
			}

			$file = $newest . '/' . self::TAKEOVER;

			try {
				$next = ( static fn( string $path ): mixed => include $path )( $file );
			} catch ( Throwable $e ) {
				self::reject( $file, 'it threw ' . $e::class . ': ' . $e->getMessage() . ', so this older loader carried on' );

				return;
			}

			if ( ! is_callable( $next ) ) {
				self::reject( $file, 'it did not return an autoloader, so this older loader carried on' );

				return;
			}

			self::$retired   = true;
			self::$successor = $next;

			spl_autoload_unregister( self::$autoloader );
			spl_autoload_register( $next, true, true );
		}

		/**
		 * The vendor folders to look in.
		 *
		 * Composer's list covers everything already loaded. WordPress's own
		 * lists of what this request will load are what let a library be used
		 * before most plugins have loaded. Each is read only until its part of
		 * the site has loaded; from then on Composer's list is the truth, so
		 * a plugin WP-CLI skips, or recovery mode pauses, never counts.
		 *
		 * Never anything named in the request. WordPress loads every plugin
		 * before it knows who is asking, so a folder named there could be
		 * chosen by a logged-out visitor, and its code would load.
		 *
		 * @return array<int, string>
		 */
		private static function roots(): array {
			$roots = self::composerVendors();

			if ( self::optionsReady() ) {
				if ( function_exists( 'did_action' ) && ! did_action( 'plugins_loaded' ) ) {
					foreach ( self::pluginFiles() as $file ) {
						$folder = dirname( $file );

						// A single-file plugin has no folder of its own.
						if ( rtrim( $folder, '/' ) !== rtrim( WP_PLUGIN_DIR, '/' ) ) {
							$roots[] = $folder . '/vendor';
						}
					}
				}

				if ( function_exists( 'did_action' ) && ! did_action( 'after_setup_theme' ) ) {
					foreach ( self::themeDirs() as $theme ) {
						$roots[] = $theme . '/vendor';
					}
				}
			}

			// Composer's list and WordPress's name the same folders, so one
			// entry per path. Resolving every path to catch symlinks cost more
			// than everything else here put together; a symlinked folder read
			// twice is harmless, because merge() counts each copy once by its
			// real folder.
			$unique = [];

			foreach ( $roots as $root ) {
				$unique[ rtrim( $root, '/' ) ] = true;
			}

			return array_keys( $unique );
		}

		/**
		 * Every vendor folder Composer has loaded, and always this loader's own.
		 *
		 * Composer 2 keeps a list. Composer 1's ClassLoader has none, and when
		 * a Composer 1 plugin loads first its ClassLoader is the one every
		 * plugin shares, so the list is missing for the whole request. Each
		 * plugin's generated ComposerAutoloaderInit class still lives in its
		 * vendor/composer folder, under either version, so the folders are
		 * found from those instead.
		 *
		 * @return array<int, string>
		 */
		private static function composerVendors(): array {
			$vendors = [ dirname( __DIR__, 2 ) ];

			if ( self::hasRegistry() ) {
				foreach ( array_keys( ( self::COMPOSER )::getRegisteredLoaders() ) as $vendor ) {
					$vendors[] = rtrim( (string) $vendor, '/' );
				}

				return $vendors;
			}

			self::$noRegistry = true;

			foreach ( get_declared_classes() as $class ) {
				if ( str_starts_with( $class, 'ComposerAutoloaderInit' ) ) {
					$file = ( new ReflectionClass( $class ) )->getFileName();

					if ( is_string( $file ) ) {
						$vendors[] = dirname( $file, 2 );
					}
				}
			}

			return $vendors;
		}

		/**
		 * How many vendor folders Composer has registered, to notice new ones
		 * cheaply on every class request. Without Composer's list, finding
		 * folders means reading every declared class, too costly to do per
		 * request for a class, so it stays constant and the stage hooks look
		 * again instead.
		 */
		private static function composerCount(): int {
			return self::hasRegistry() ? count( ( self::COMPOSER )::getRegisteredLoaders() ) : 0;
		}

		private static function hasRegistry(): bool {
			return class_exists( self::COMPOSER, false ) && method_exists( self::COMPOSER, 'getRegisteredLoaders' );
		}

		/**
		 * Whether WordPress can answer for this site safely yet.
		 *
		 * Not in a drop-in such as object-cache.php, which runs before the
		 * object cache exists, and not on multisite before WordPress knows
		 * which site this is, as in sunrise.php. There, Composer's list is
		 * all there is.
		 */
		private static function optionsReady(): bool {
			if ( ! function_exists( 'get_option' ) || ! defined( 'WP_PLUGIN_DIR' ) || ! isset( $GLOBALS['wpdb'], $GLOBALS['wp_object_cache'] ) ) {
				return false;
			}

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				return function_exists( 'did_action' ) && did_action( 'ms_loaded' ) > 0;
			}

			return true;
		}

		/**
		 * The plugin files WordPress will load this request, network-active
		 * ones first, the way WordPress loads them.
		 *
		 * WordPress's own lists, so a plugin that WP-CLI skips, that recovery
		 * mode pauses, or that a damaged option names, is left out exactly as
		 * WordPress leaves it out.
		 *
		 * @return array<int, string> Absolute paths to plugin files.
		 */
		private static function pluginFiles(): array {
			$files = [];

			if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'wp_get_active_network_plugins' ) ) {
				$files = wp_get_active_network_plugins();
			}

			if ( function_exists( 'wp_get_active_and_valid_plugins' ) ) {
				$files = array_merge( $files, wp_get_active_and_valid_plugins() );
			}

			return array_values( array_filter( $files, 'is_string' ) );
		}

		/**
		 * The theme this request uses and its parent.
		 *
		 * Through get_stylesheet() and get_template(), so a theme previewed
		 * in the Customizer or the site editor counts, once the preview has
		 * set itself up.
		 *
		 * @return array<int, string>
		 */
		private static function themeDirs(): array {
			$dirs = [];

			foreach ( [ 'stylesheet', 'template' ] as $which ) {
				$getter = 'get_' . $which;
				$slug   = function_exists( $getter ) ? $getter() : get_option( $which );

				if ( ! is_string( $slug ) || '' === $slug || str_contains( $slug, '..' ) ) {
					continue;
				}

				// WordPress's own answer. With one themes folder, the usual
				// case, it reads no options; reading stylesheet_root directly
				// cost a database query per page, since it rarely exists.
				$root = function_exists( 'get_theme_root' ) ? get_theme_root( $slug ) : WP_CONTENT_DIR . '/themes';

				$dirs[] = rtrim( (string) $root, '/' ) . '/' . $slug;
			}

			return $dirs;
		}

		/**
		 * Reads every declaration under each vendor folder.
		 *
		 * @param array<int, string> $vendors
		 * @return array<int, array<string, mixed>>
		 */
		private static function scan( array $vendors ): array {
			$copies = [];

			foreach ( $vendors as $vendor ) {
				foreach ( self::declarations( $vendor ) as $file => $package ) {
					$copy = self::read( $file, $package );

					if ( null !== $copy ) {
						$copies[] = $copy;
					}
				}
			}

			return $copies;
		}

		/**
		 * The declaration files in one vendor folder, each with the Composer
		 * package it belongs to.
		 *
		 * Read from Composer's own record of what it installed and where.
		 * With opcache that record costs next to nothing to include, where
		 * listing folders costs a directory read per plugin on every page.
		 * It also finds a package Composer installed somewhere custom. Copies
		 * of this loader are noted on the way, for takeOver().
		 *
		 * The @ on file checks keeps open_basedir from filling a log when a
		 * folder is symlinked from outside the allowed paths.
		 *
		 * @return array<string, string> Declaration file to package name.
		 */
		private static function declarations( string $vendor ): array {
			$record = $vendor . '/composer/installed.php';

			// Composer 1 wrote no such record, and plugins that commit vendor/
			// often leave it out. Listing the folder still works, and the
			// folder is named after the package.
			if ( ! @is_file( $record ) ) {
				$files = [];

				foreach ( @glob( $vendor . '/' . self::VENDOR . '/*/' . self::DECLARATION ) ?: [] as $file ) {
					$files[ $file ] = self::VENDOR . '/' . basename( dirname( $file ) );
				}

				// With no record to say a loader copy is newer, only one that
				// ships takeover.php can be, and its own file says its version.
				$loader = $vendor . '/' . self::NAME;

				if ( @is_file( $loader . '/' . self::TAKEOVER ) ) {
					$version = self::versionIn( $loader . '/init.php' );

					if ( null !== $version && version_compare( $version, self::VERSION, '>' ) ) {
						self::$newer[ $loader ] = $version;
					}
				}

				return $files;
			}

			// is_readable() would answer this up front, but PHP cannot cache
			// it, and it cost eight times is_file() across forty plugins. An
			// unreadable record fails here instead, quietly.
			try {
				$installed = ( static fn( string $path ): mixed => @include $path )( $record );
			} catch ( Throwable ) {
				$installed = false;
			}

			if ( ! is_array( $installed ) ) {
				self::reject( $record, 'Composer\'s record of installed packages could not be read' );

				return [];
			}

			$files = [];

			foreach ( is_array( $installed['versions'] ?? null ) ? $installed['versions'] : [] as $name => $package ) {
				if ( ! is_string( $name ) || ! str_starts_with( $name, self::VENDOR . '/' ) || ! is_array( $package ) ) {
					continue;
				}

				// Early Composer 2 records had no install path. The default
				// place is right for a library.
				$path = is_string( $package['install_path'] ?? null ) ? $package['install_path'] : $vendor . '/' . $name;

				// A copy of this loader. Composer's record holds its version, so
				// only a newer one, the rare case, costs a look on disk later.
				// A development install reports a branch, not a number, and
				// never takes over.
				if ( self::NAME === $name ) {
					$version = $package['version'] ?? null;

					if ( is_string( $version ) && preg_match( '/^\d+(\.\d+){0,3}\z/', $version ) && version_compare( $version, self::VERSION, '>' ) ) {
						self::$newer[ $path ] = $version;
					}

					continue;
				}

				$file = $path . '/' . self::DECLARATION;

				if ( @is_file( $file ) ) {
					$files[ $file ] = $name;
				}
			}

			return $files;
		}

		/**
		 * Adds copies to what is known, keeping each library newest first.
		 *
		 * @param array<string, array<int, array<string, mixed>>> $known
		 * @param array<int, array<string, mixed>>                $copies
		 * @return array<string, array<int, array<string, mixed>>>
		 */
		private static function merge( array $known, array $copies ): array {
			foreach ( $copies as $copy ) {
				// The same folder found twice, through two lists, is one copy.
				foreach ( $known[ $copy['name'] ] ?? [] as $existing ) {
					if ( $existing['dir'] === $copy['dir'] ) {
						continue 2;
					}
				}

				$known[ $copy['name'] ][] = $copy;
			}

			foreach ( $known as $name => $list ) {
				// Stable since PHP 8.0, so the same version found twice keeps
				// the one found first.
				usort( $list, static fn( array $a, array $b ): int => version_compare( $b['version'], $a['version'] ) );

				$known[ $name ] = $list;
			}

			return $known;
		}

		/**
		 * One declaration, or null if it cannot be trusted.
		 *
		 * Anything unexpected skips the copy rather than guessing, and records
		 * why. A newer declaration may carry keys this copy does not know, and
		 * those are ignored.
		 *
		 * @param string $package The Composer package the file came from. A
		 *                        declaration naming any other is skipped, so a
		 *                        file copied from another library cannot pass
		 *                        for it.
		 * @return array<string, mixed>|null
		 */
		private static function read( string $file, string $package ): ?array {
			try {
				$data = ( static fn( string $path ): mixed => include $path )( $file );
			} catch ( Throwable $e ) {
				return self::reject( $file, 'it threw ' . $e::class . ': ' . $e->getMessage() );
			}

			if ( ! is_array( $data ) ) {
				return self::reject( $file, 'it does not return an array' );
			}

			$name    = $data['name'] ?? null;
			$version = $data['version'] ?? null;

			if ( $package !== $name ) {
				return self::reject( $file, 'its name is not ' . $package );
			}

			// Plain numbers only. Anything else sorts unpredictably against
			// real versions in version_compare().
			if ( ! is_string( $version ) || ! preg_match( '/^\d+(\.\d+){0,3}\z/', $version ) ) {
				return self::reject( $file, 'its version is not plain numbers like 0.6.0' );
			}

			$namespace = $data['namespace'] ?? null;
			$namespace = is_string( $namespace ) && self::isMaiName( $namespace ) ? rtrim( $namespace, '\\' ) . '\\' : null;

			$classes = [];

			foreach ( is_array( $data['classes'] ?? null ) ? $data['classes'] : [] as $class => $relative ) {
				if ( is_string( $class ) && self::isMaiName( $class ) && is_string( $relative ) && ! str_contains( $relative, '..' ) ) {
					$classes[ $class ] = $relative;
				}
			}

			if ( null === $namespace && [] === $classes ) {
				return self::reject( $file, 'it declares no namespace or class starting Mai\\ or Mai_' );
			}

			$path = $data['path'] ?? '';
			$dir  = @realpath( dirname( $file ) );

			if ( false === $dir || ! is_string( $path ) || str_contains( $path, '..' ) ) {
				return self::reject( $file, 'its path is not a folder inside it' );
			}

			return [
				'name'      => $name,
				'version'   => $version,
				'dir'       => $dir,
				'namespace' => $namespace,
				'path'      => trim( $path, '/' ),
				'classes'   => $classes,
			];
		}

		/**
		 * A loader copy's version, read from its init.php, for when Composer's
		 * record is missing. Only read for a copy shipping takeover.php, so
		 * rarely. Null for anything but plain numbers.
		 */
		private static function versionIn( string $file ): ?string {
			$code = @file_get_contents( $file );

			if ( is_string( $code ) && preg_match( "/^\s*public const VERSION = '(\d+(?:\.\d+){0,3})';/m", $code, $match ) ) {
				return $match[1];
			}

			return null;
		}

		/**
		 * Records why a copy was skipped, and logs it when the site is
		 * debugging, so nobody has to read this file to find out.
		 */
		private static function reject( string $file, string $reason ): ?array {
			if ( ! isset( self::$rejected[ $file ] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Mai Package Loader skipped %s: %s.', $file, $reason ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			self::$rejected[ $file ] = $reason;

			return null;
		}

		/**
		 * Records something the site should know that is not a skipped copy,
		 * and logs it when debugging, once per request, if it can go wrong.
		 */
		private static function note( string $key, string $message, bool $log = true ): void {
			if ( $log && ! isset( self::$rejected[ $key ] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Mai Package Loader: %s.', $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			self::$rejected[ $key ] = $message;
		}

		/**
		 * Warns once per library when an old copy's own bootstrap already
		 * loaded some of its classes, before this loader existed.
		 *
		 * Those stay at the old version for the request, and the rest come
		 * from the newest copy, so a newer method can be missing. It only
		 * happens while old copies are still bundled somewhere, and nothing
		 * else would say so.
		 *
		 * @param array<int, array<string, mixed>> $copies
		 */
		private static function checkMix( string $name, array $copies ): void {
			if ( isset( self::$mixChecked[ $name ] ) || [] === $copies ) {
				return;
			}

			self::$mixChecked[ $name ] = true;

			$dirs = array_column( $copies, 'dir' );

			// Only this library's own names. One preg_grep() over the declared
			// classes, rather than a PHP loop across the two thousand or so a
			// WordPress page has by now.
			$loaded = array_keys( array_filter( $copies[0]['classes'], static fn( $file, string $class ): bool => class_exists( $class, false ), ARRAY_FILTER_USE_BOTH ) );

			if ( null !== $copies[0]['namespace'] ) {
				$loaded = array_merge( $loaded, preg_grep( '/^' . preg_quote( $copies[0]['namespace'], '/' ) . '/', get_declared_classes() ) ?: [] );
			}

			foreach ( $loaded as $declared ) {
				$file = ( new ReflectionClass( $declared ) )->getFileName();

				if ( ! is_string( $file ) ) {
					continue;
				}

				$real = realpath( $file ) ?: $file;

				foreach ( $dirs as $dir ) {
					if ( str_starts_with( $real, $dir . '/' ) ) {
						continue 2;
					}
				}

				self::note( $name . ' mixed', sprintf( '%s was already loaded from %s, an older copy with its own bootstrap, so %s is split across two versions for this request', $declared, $file, $name ) );

				return;
			}
		}

		/**
		 * Whether a name could belong to a shared library: `Mai\...` or
		 * `Mai_...`. Not just "Mai", which would also catch MailPoet,
		 * Mailchimp and MainWP.
		 */
		private static function isMaiName( string $name ): bool {
			foreach ( self::PREFIXES as $prefix ) {
				if ( str_starts_with( $name, $prefix ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Where a copy keeps a class, or null if the class is not its.
		 *
		 * @param array<string, mixed> $copy
		 */
		private static function fileFor( array $copy, string $class ): ?string {
			if ( isset( $copy['classes'][ $class ] ) ) {
				return $copy['dir'] . '/' . ltrim( $copy['classes'][ $class ], '/' );
			}

			if ( null !== $copy['namespace'] && str_starts_with( $class, $copy['namespace'] ) ) {
				$relative = str_replace( '\\', '/', substr( $class, strlen( $copy['namespace'] ) ) );
				$base     = '' === $copy['path'] ? $copy['dir'] : $copy['dir'] . '/' . $copy['path'];

				return $base . '/' . $relative . '.php';
			}

			return null;
		}
	}
}

Mai_Package_Loader::boot();
