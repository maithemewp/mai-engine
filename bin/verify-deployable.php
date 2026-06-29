<?php
/**
 * Verify the committed composer autoloader is deployable as-is.
 *
 * mai-engine is deployed by checking out / copying a branch with no composer
 * build step, so the committed vendor must be self-consistent: every file the
 * production files-autoloader eagerly `require`s must be COMMITTED (git-tracked).
 * If `vendor/composer/autoload_files.php` was regenerated with dev dependencies
 * installed, it references dev-only packages (e.g. symfony/* pulled by
 * php-cs-fixer/phpunit) that `.gitignore` excludes from the commit, and a
 * raw-branch deploy then fatals on load.
 *
 * The check is against git-tracked status, NOT file_exists: in a dev checkout the
 * dev-only files are physically present (built but git-ignored), so file_exists
 * would give a false pass. Tracked-status is what actually ships in a branch.
 *
 * Fix when it fails:  composer dump-autoload --no-dev
 *
 * Run: php bin/verify-deployable.php   (exit 1 on failure)
 */

$root = dirname( __DIR__ );

$lsfiles = [];
$code    = 0;
exec( 'git -C ' . escapeshellarg( $root ) . ' ls-files', $lsfiles, $code );
if ( 0 !== $code || ! $lsfiles ) {
	fwrite( STDERR, "Could not list git-tracked files (is this a git checkout?).\n" );
	exit( 2 );
}
$tracked = array_flip( $lsfiles );

$files   = require $root . '/vendor/composer/autoload_files.php';
$missing = [];
foreach ( $files as $path ) {
	$rel = ltrim( str_replace( $root, '', $path ), '/' );
	if ( ! isset( $tracked[ $rel ] ) ) {
		$missing[] = $rel;
	}
}

if ( $missing ) {
	fwrite( STDERR, "Committed autoloader references files that are not committed (not deployable as-is):\n" );
	foreach ( $missing as $rel ) {
		fwrite( STDERR, '  - ' . $rel . "\n" );
	}
	fwrite( STDERR, "\nThe committed autoloader was likely generated with dev dependencies.\n" );
	fwrite( STDERR, "Regenerate it without them and commit:\n  composer dump-autoload --no-dev\n" );
	exit( 1 );
}

echo 'OK: all ' . count( $files ) . " files-autoload entries are committed; branch is deployable as-is.\n";
