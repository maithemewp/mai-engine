<?php
declare(strict_types=1);

/**
 * Plugin Name: Mai grid optimizer probe (temporary)
 * Description: Dev tool, never shipped. Records the SQL of every Mai grid query on a request that asks for it.
 *
 * Writes one JSON line per marked grid query (a grid's own query or Mai's ID-only copy), whatever
 * the optimizer's checks decided, to /tmp/mai-optimizer-statements-<site>.jsonl, where <site> is
 * the host of home_url(), so two sites never share a file. Each line holds
 * { "role", "statement", "queries", "posts", "terms" }: the statement as it stands after every
 * posts_request callback, the tax filters WordPress used, and the two table names. The query is
 * recorded and the statement is returned unchanged.
 *
 * Usage (local sites only, headless):
 *   1. Copy this file to wp-content/mu-plugins/zzz-mai-optimizer-probe.php.
 *   2. Make Mai's cache able to store, so grids defer and copies run: back up wp-config.php, set
 *      WP_DEVELOPMENT_MODE '' and SCRIPT_DEBUG false, and empty Mai's grid cache with
 *      wp eval 'mai_cache( "grid" )->flush();'.
 *   3. Request each page with the flag:
 *        curl -sk -o /dev/null "https://site.test/some-page/?mai_optimizer_probe=1"
 *   4. Restore wp-config.php with cp, check it with cmp, and delete the mu-plugin.
 *   5. Turn the statements into pairs with bin/grid-optimizer-pairs.php.
 *
 * The file is appended to, never emptied. Delete it before a fresh capture. The pairs script reads
 * the file for the site it runs on. A line that cannot be encoded or written is reported in the PHP
 * error log.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Only a request that asks for it is recorded.
if ( ! isset( $_GET['mai_optimizer_probe'] ) || '1' !== $_GET['mai_optimizer_probe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return;
}

// Registered late, so this runs after the optimizer's own posts_request callback (init, 9).
add_action(
	'init',
	static function (): void {
		add_filter(
			'posts_request',
			static function ( mixed $request, mixed $query = null ): mixed {
				global $wpdb;

				if ( ! is_string( $request ) || ! $query instanceof WP_Query || ! isset( $query->mai_optimize ) ) {
					return $request;
				}

				$line = wp_json_encode(
					[
						'role'      => $query->mai_optimize,
						'statement' => $request,
						'queries'   => $query->tax_query instanceof WP_Tax_Query ? $query->tax_query->queries : [],
						'posts'     => $wpdb->posts,
						'terms'     => $wpdb->term_relationships,
					]
				);

				// The same name the pairs script builds from the host.
				$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
				$site = trim( (string) preg_replace( '/[^a-z0-9.-]+/', '-', $host ), '-' );
				$site = '' !== $site ? $site : 'site';
				$file = "/tmp/mai-optimizer-statements-{$site}.jsonl";

				if ( ! is_string( $line ) ) {
					error_log( 'Mai grid optimizer probe: could not encode a ' . $query->mai_optimize . ' statement as JSON: ' . json_last_error_msg() );
				} elseif ( false === file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX ) ) {
					error_log( "Mai grid optimizer probe: could not write to {$file}." );
				}

				return $request;
			},
			PHP_INT_MAX,
			2
		);
	},
	20
);
