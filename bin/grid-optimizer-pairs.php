<?php
/**
 * Mai grid optimizer pairs (dev tool, not shipped).
 *
 * Writes the statement pairs that bin/grid-optimizer-replay.php times and compares. Each pair is
 * today's statement and the swapped EXISTS form, built with the optimizer's own rebuild(),
 * condition() and swap(), once with the MySQL hint and once without (MariaDB does not read it).
 * A statement the optimizer would not cover is logged and skipped: rebuild() or swap() returns
 * null, or the ORDER BY fails Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok() (a RAND() grid, for
 * one). Without that last check such a grid would report IDs that differ, since random order
 * differs from one run to the next.
 *
 * Some skips are expected: a statement with no tax filter, the one synthetic shape that is not
 * covered on purpose, a sort orderby_ok() refuses, and any captured statement the optimizer would
 * not cover. A synthetic statement meant to be covered that is skipped is not expected, and stops
 * the run with an error, as do no pairs at all, a failed write and a failed term read.
 *
 * Two sources:
 *   1. The statements bin/grid-optimizer-probe.php captured from real page views, from
 *      /tmp/mai-optimizer-statements-<site>.jsonl, where <site> is the host of home_url(). A
 *      grid's own statement is written in the ID-only form WordPress sends when it splits the
 *      query, since that is what the database receives.
 *   2. Synthetic statements for the site's real terms, found with SQL on the term tables
 *      (counts from term_taxonomy.count for category and post_tag): the biggest term, a mid-size
 *      one (10 to 50% of published posts, the nearest to 25%), a small one (under 20 posts) and
 *      an old one (newest post over a year old, the biggest such term). Each size is crossed with
 *      the eight sorts, ascending and descending, with Mai's ID tiebreaker. Only date, author and
 *      ID pass orderby_ok(); the others are logged as skipped. Then come tag, custom taxonomy,
 *      AND, OR, IN with NOT IN, 29 terms, no children, posts and pages, publish and private (as
 *      the first administrator, nothing is saved, and skipped with a warning on a site with no
 *      administrator) and other public post types. One shape is not covered on purpose, to show
 *      the skip.
 *
 * Every statement is written at its own LIMIT, at LIMIT 0, 2, at LIMIT 0, 32 and with no LIMIT,
 * each as its own pair line:
 *   { "name", "original", "swapped_mysql", "swapped_mariadb" }
 * A statement text that an earlier pair already has, from another statement or from the same one
 * at a LIMIT that matches, is written once, so the replay never times it twice.
 *
 * Output: /tmp/mai-optimizer-pairs-<site>.jsonl, with <site> as above. The file is replaced on
 * every run. Nothing is written to the database.
 *
 * Usage (from the WP root, local sites only):
 *   wp eval-file wp-content/plugins/mai-engine/bin/grid-optimizer-pairs.php
 *   wp eval-file wp-content/plugins/mai-engine/bin/grid-optimizer-pairs.php /path/to/statements.jsonl
 *
 * There is no declare(strict_types=1) here, on purpose: wp eval-file evaluates the file, and PHP
 * rejects the declaration in evaluated code.
 *
 * @package BizBudding\MaiEngine
 */

namespace BizBudding\MaiEngine\Tools\GridOptimizerPairs;

use Mai_Post_Grid_Query_Optimizer_Database;
use Mai_Post_Grid_Query_Optimizer_Sql;
use WP_CLI;
use WP_Query;
use WP_Tax_Query;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( 'Mai_Post_Grid_Query_Optimizer_Sql' ) || ! class_exists( 'Mai_Post_Grid_Query_Optimizer_Database' ) ) {
	WP_CLI::error( 'Mai Engine with the grid query optimizer is not loaded on this site.' );
}

/**
 * The tax filter for one taxonomy and its terms, written the way Mai's grid writes it.
 *
 * @param string $taxonomy The taxonomy.
 * @param array  $ids      The term IDs.
 * @param string $operator IN or NOT IN.
 * @param bool   $children Whether to include child terms.
 *
 * @return array
 */
function tax_filter( string $taxonomy, array $ids, string $operator = 'IN', bool $children = true ): array {
	$filter = [
		'taxonomy' => $taxonomy,
		'field'    => 'id',
		'terms'    => array_map( 'intval', $ids ),
		'operator' => $operator,
	];

	if ( ! $children ) {
		$filter['include_children'] = false;
	}

	return $filter;
}

/**
 * Stops the run when the last statement failed, so a broken read never passes for an empty site.
 *
 * @param string $what What was read, for the message.
 *
 * @return void
 */
function stop_on_db_error( string $what ): void {
	global $wpdb;

	if ( '' !== (string) $wpdb->last_error ) {
		WP_CLI::error( "Could not read {$what}: {$wpdb->last_error}" );
	}
}

/**
 * The first administrator's ID, or 0 when the site has none.
 *
 * @return int
 */
function first_admin(): int {
	return (int) ( get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] )[0] ?? 0 );
}

/**
 * Finds the site's terms by size, with SQL on the term tables.
 *
 * @return array<string,array{id:int,taxonomy:string,count:int}> Keys: big, mid, small, old,
 *                                                               tag, tag_small, custom. Missing
 *                                                               when the site has no such term.
 */
function find_terms(): array {
	global $wpdb;

	$published = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'" );

	stop_on_db_error( 'the published post count' );

	$rows = (array) $wpdb->get_results(
		"SELECT term_id, taxonomy, count FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ( 'category', 'post_tag' ) AND count > 0 ORDER BY count DESC, term_id ASC",
		ARRAY_A
	);

	stop_on_db_error( 'the categories and tags' );

	$pick = static fn( array $row ): array => [
		'id'       => (int) $row['term_id'],
		'taxonomy' => (string) $row['taxonomy'],
		'count'    => (int) $row['count'],
	];

	$categories = array_values( array_filter( $rows, static fn( array $row ): bool => 'category' === $row['taxonomy'] ) );
	$tags       = array_values( array_filter( $rows, static fn( array $row ): bool => 'post_tag' === $row['taxonomy'] ) );
	$terms      = [];

	if ( $categories ) {
		$terms['big'] = $pick( $categories[0] );

		// Mid-size: 10 to 50% of the published posts, nearest to 25%.
		$middle = array_filter( $categories, static fn( array $row ): bool => (int) $row['count'] >= 0.1 * $published && (int) $row['count'] <= 0.5 * $published );

		if ( $middle ) {
			usort( $middle, static fn( array $a, array $b ): int => abs( (int) $a['count'] - 0.25 * $published ) <=> abs( (int) $b['count'] - 0.25 * $published ) );

			$terms['mid'] = $pick( $middle[0] );
		}

		// Small: the biggest term under 20 posts.
		foreach ( $categories as $row ) {
			if ( (int) $row['count'] < 20 ) {
				$terms['small'] = $pick( $row );
				break;
			}
		}
	}

	if ( $tags ) {
		$terms['tag'] = $pick( $tags[0] );

		foreach ( $tags as $row ) {
			if ( (int) $row['count'] < 20 ) {
				$terms['tag_small'] = $pick( $row );
				break;
			}
		}
	}

	// Old: the biggest category or tag whose newest published post is over a year old.
	$old = $wpdb->get_row(
		"SELECT tt.term_id, tt.taxonomy, tt.count
		FROM {$wpdb->term_taxonomy} tt
		INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_type = 'post' AND p.post_status = 'publish'
		WHERE tt.taxonomy IN ( 'category', 'post_tag' ) AND tt.count > 0
		GROUP BY tt.term_taxonomy_id
		HAVING MAX( p.post_date ) < NOW() - INTERVAL 1 YEAR
		ORDER BY tt.count DESC
		LIMIT 1",
		ARRAY_A
	);

	stop_on_db_error( 'the old term' );

	if ( is_array( $old ) ) {
		$terms['old'] = $pick( $old );
	}

	$custom = array_values( array_diff( get_object_taxonomies( 'post' ), [ 'category', 'post_tag' ] ) );
	$best   = best_term( $custom );

	if ( $best ) {
		$terms['custom'] = $best;
	}

	return $terms;
}

/**
 * The biggest term, by its count, across some taxonomies.
 *
 * @param string[] $taxonomies The taxonomies.
 *
 * @return array{id:int,taxonomy:string,count:int}|null
 */
function best_term( array $taxonomies ): ?array {
	global $wpdb;

	if ( ! $taxonomies ) {
		return null;
	}

	$placeholders = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );
	$row          = $wpdb->get_row(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->prepare( "SELECT term_id, taxonomy, count FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ( {$placeholders} ) AND count > 0 ORDER BY count DESC, term_id ASC LIMIT 1", ...$taxonomies ),
		ARRAY_A
	);

	stop_on_db_error( 'the biggest term of ' . implode( ', ', $taxonomies ) );

	return is_array( $row ) ? [
		'id'       => (int) $row['term_id'],
		'taxonomy' => (string) $row['taxonomy'],
		'count'    => (int) $row['count'],
	] : null;
}

/**
 * The synthetic statements to build: name, WP_Query args, whether to build it as the first
 * administrator, and whether the optimizer is meant to cover it.
 *
 * @param array<string,array{id:int,taxonomy:string,count:int}> $terms What find_terms() found.
 *
 * @return list<array{name:string,args:array,admin:bool,covered:bool}>
 */
function synthetic_specs( array $terms ): array {
	global $wpdb;

	$specs = [];
	$make  = static function ( string $name, array $tax_query, array $over = [], bool $admin = false, bool $covered = true ) use ( &$specs ): void {
		$specs[] = [
			'name'    => $name,
			'admin'   => $admin,
			'covered' => $covered,
			'args'    => array_merge(
				[
					'post_type'              => 'post',
					'post_status'            => 'publish',
					'posts_per_page'         => 12,
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'fields'                 => 'ids',
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'orderby'                => [
						'date' => 'DESC',
						'ID'   => 'DESC',
					],
					'tax_query'              => $tax_query,
				],
				$over
			),
		];
	};

	$term_filter = static fn( array $term, string $operator = 'IN', bool $children = true ): array => tax_filter( $term['taxonomy'], [ $term['id'] ], $operator, $children );

	// Each size of term, crossed with every sort the grid offers. Only date, author and ID pass
	// orderby_ok(); the others are logged as skipped.
	$sorts = [ 'date', 'modified', 'title', 'name', 'menu_order', 'comment_count', 'author', 'ID' ];

	foreach ( [ 'big', 'mid', 'small', 'old' ] as $size ) {
		if ( ! isset( $terms[ $size ] ) ) {
			WP_CLI::warning( "No {$size} term on this site, so no {$size} statements." );
			continue;
		}

		foreach ( $sorts as $sort ) {
			foreach ( [ 'ASC', 'DESC' ] as $direction ) {
				$make(
					"{$size}, {$sort} {$direction}",
					[ $term_filter( $terms[ $size ] ) ],
					[
						// Mai's tiebreaker is the ID, newest first. Sorting by the ID needs none.
						'orderby' => 'ID' === $sort ? [ 'ID' => $direction ] : [
							$sort => $direction,
							'ID'  => 'DESC',
						],
					]
				);
			}
		}
	}

	// The other shapes, each sorted newest first.
	if ( isset( $terms['big'] ) ) {
		$has_children = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE parent = %d AND taxonomy = %s LIMIT 1", $terms['big']['id'], $terms['big']['taxonomy'] ) );

		stop_on_db_error( 'the child terms of the big term' );

		if ( $has_children ) {
			$make( 'big, no child terms', [ $term_filter( $terms['big'], 'IN', false ) ] );
		}

		$make( 'big, posts and pages', [ $term_filter( $terms['big'] ) ], [ 'post_type' => [ 'post', 'page' ] ] );

		// Private posts are only in the statement for a user who may read them.
		if ( first_admin() ) {
			$make( 'big, publish and private', [ $term_filter( $terms['big'] ) ], [ 'post_status' => [ 'publish', 'private' ] ], true );
			$make( 'big, posts and pages, publish and private', [ $term_filter( $terms['big'] ) ], [
				'post_type'   => [ 'post', 'page' ],
				'post_status' => [ 'publish', 'private' ],
			], true );
		} else {
			WP_CLI::warning( 'No administrator on this site, so no publish and private statements.' );
		}

		$make( 'big, NOT IN only (not covered)', [ $term_filter( $terms['big'], 'NOT IN' ) ], [], false, false );

		$category_ids = array_map(
			'intval',
			(array) $wpdb->get_col( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'category' AND count > 0 ORDER BY count DESC, term_id ASC LIMIT 29" )
		);

		stop_on_db_error( 'the 29 biggest categories' );

		$make( count( $category_ids ) . ' terms', [ tax_filter( 'category', $category_ids ) ] );
	}

	if ( isset( $terms['tag'] ) ) {
		$make( 'tag', [ $term_filter( $terms['tag'] ) ] );
	}

	if ( isset( $terms['tag_small'] ) ) {
		$make( 'small tag', [ $term_filter( $terms['tag_small'] ) ] );
	}

	if ( isset( $terms['custom'] ) ) {
		$make( "custom taxonomy {$terms['custom']['taxonomy']}", [ $term_filter( $terms['custom'] ) ] );
	}

	if ( isset( $terms['big'], $terms['tag'] ) ) {
		$make( 'big AND tag', [ $term_filter( $terms['big'] ), $term_filter( $terms['tag'] ), 'relation' => 'AND' ] );
		$make( 'big OR tag', [ $term_filter( $terms['big'] ), $term_filter( $terms['tag'] ), 'relation' => 'OR' ] );
	}

	if ( isset( $terms['mid'], $terms['tag'] ) ) {
		$make( 'mid AND tag', [ $term_filter( $terms['mid'] ), $term_filter( $terms['tag'] ), 'relation' => 'AND' ] );
	}

	if ( isset( $terms['big'], $terms['small'] ) ) {
		$make( 'big IN, small NOT IN', [ $term_filter( $terms['big'] ), $term_filter( $terms['small'], 'NOT IN' ) ] );
	}

	if ( isset( $terms['big'], $terms['custom'] ) ) {
		$make( 'big IN, custom NOT IN', [ $term_filter( $terms['big'] ), $term_filter( $terms['custom'], 'NOT IN' ) ] );
	}

	// Other public post types that have a taxonomy with terms. At most two.
	$others = 0;

	foreach ( get_post_types( [ 'public' => true ] ) as $type ) {
		if ( $others >= 2 || in_array( $type, [ 'post', 'page', 'attachment' ], true ) ) {
			continue;
		}

		$best = best_term( array_values( get_object_taxonomies( $type ) ) );

		if ( $best ) {
			$make( "post type {$type}", [ $term_filter( $best ) ], [ 'post_type' => $type ] );
			++$others;
		}
	}

	return $specs;
}

/**
 * Builds the statement WordPress writes for some query args, without running it.
 *
 * @param array $args  The WP_Query args.
 * @param bool  $admin Whether to build it as the first administrator, for private posts.
 *
 * @return array{statement:string,queries:array}|null Null when WordPress wrote no tax query.
 */
function build( array $args, bool $admin ): ?array {
	$short = static fn(): array => [];

	$before = get_current_user_id();
	$user   = $admin ? first_admin() : 0;

	wp_set_current_user( $user );
	add_filter( 'posts_pre_query', $short, PHP_INT_MAX );

	try {
		$query = new WP_Query( $args );
	} finally {
		remove_filter( 'posts_pre_query', $short, PHP_INT_MAX );
		wp_set_current_user( $before );
	}

	if ( ! is_string( $query->request ) || ! $query->tax_query instanceof WP_Tax_Query ) {
		return null;
	}

	return [
		'statement' => $query->request,
		'queries'   => $query->tax_query->queries,
	];
}

/**
 * The same statement at another LIMIT, or none.
 *
 * @param string      $statement The statement.
 * @param string|null $limit     'LIMIT a, b', or null for no LIMIT.
 *
 * @return string
 */
function with_limit( string $statement, ?string $limit ): string {
	$base = (string) preg_replace( '/\s+LIMIT\s+\d+(?:\s*,\s*\d+)?\s*$/i', '', $statement );

	return null === $limit ? $base : "{$base} {$limit}";
}

/**
 * Writes the swapped statement for both engines.
 *
 * @param string     $statement The statement, at the LIMIT being written.
 * @param array|null $rebuilt   What rebuild() returned for its tax filters.
 * @param string     $posts     The posts table.
 * @param string     $terms     The term relationships table.
 *
 * @return array{swapped_mysql:?string,swapped_mariadb:?string}
 */
function swaps( string $statement, ?array $rebuilt, string $posts, string $terms ): array {
	$out = [
		'swapped_mysql'   => null,
		'swapped_mariadb' => null,
	];

	if ( null === $rebuilt ) {
		return $out;
	}

	// The optimizer's own rule decides whether the hint is written, from a server string for each
	// engine.
	$engines = [
		'swapped_mysql'   => '8.0.45',
		'swapped_mariadb' => '10.11.9-MariaDB',
	];

	foreach ( $engines as $key => $server ) {
		$hint      = Mai_Post_Grid_Query_Optimizer_Database::hint( $server );
		$condition = Mai_Post_Grid_Query_Optimizer_Sql::condition( $rebuilt, $posts, $terms, $hint );
		$swapped   = Mai_Post_Grid_Query_Optimizer_Sql::swap( $statement, $rebuilt, $condition, $posts );

		if ( null === $swapped ) {
			return [
				'swapped_mysql'   => null,
				'swapped_mariadb' => null,
			];
		}

		$out[ $key ] = $swapped;
	}

	return $out;
}

/**
 * The site's name for file names: the host of home_url(). The probe builds it the same way.
 *
 * @return string
 */
function site_slug(): string {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$site = trim( (string) preg_replace( '/[^a-z0-9.-]+/', '-', $host ), '-' );

	return '' !== $site ? $site : 'site';
}

/**
 * The ORDER BY of a statement, without the keywords and the LIMIT.
 *
 * @param string $statement The statement.
 *
 * @return string|null Null when the statement has no ORDER BY.
 */
function orderby_of( string $statement ): ?string {
	$base = with_limit( $statement, null );

	return 1 === preg_match( '/.*\sORDER BY\s+(.*?)\s*$/s', $base, $match ) ? $match[1] : null;
}

/**
 * A short description of a tax query, for names and logs.
 *
 * @param array $queries The tax queries.
 *
 * @return string
 */
function describe( array $queries ): string {
	$parts = [];

	foreach ( $queries as $key => $clause ) {
		if ( 'relation' === $key || ! is_array( $clause ) ) {
			continue;
		}

		$terms   = is_array( $clause['terms'] ?? null ) ? $clause['terms'] : [];
		$parts[] = ( $clause['taxonomy'] ?? '?' ) . ' ' . ( $clause['operator'] ?? 'IN' ) . ' ' . ( count( $terms ) > 3 ? count( $terms ) . ' terms' : implode( ',', $terms ) );
	}

	return implode( ' ' . ( $queries['relation'] ?? 'AND' ) . ' ', $parts );
}

/**
 * Records a skipped statement as expected or not.
 *
 * @param array<string,mixed> $tally      Counts and skips, updated here.
 * @param bool                $must_cover Whether the statement was meant to be covered.
 * @param string              $why        The statement's name and why it was skipped.
 *
 * @return void
 */
function skip( array &$tally, bool $must_cover, string $why ): void {
	$tally[ $must_cover ? 'unexpected' : 'expected' ][] = $why;
}

/**
 * Writes the pair lines for one statement: its own LIMIT and three more.
 *
 * @param resource             $handle     The open output file.
 * @param string               $name       The statement's name.
 * @param string               $statement  The statement.
 * @param array                $queries    The tax filters WordPress used.
 * @param string               $posts      The posts table.
 * @param string               $terms      The term relationships table.
 * @param bool                 $must_cover Whether the optimizer is meant to cover it, when its sort
 *                                         is one orderby_ok() takes. A skip is then unexpected.
 * @param array<string,mixed>  $tally      Counts, the pair texts written and skipped names, updated here.
 *
 * @return void
 */
function write_pairs( $handle, string $name, string $statement, array $queries, string $posts, string $terms, bool $must_cover, array &$tally ): void {
	++$tally['statements'];

	// No tax filter: a grid with no taxonomies, which the optimizer never touches.
	if ( ! array_filter( $queries, 'is_array' ) ) {
		skip( $tally, false, "{$name} (no tax filter)" );
		return;
	}

	$orderby = orderby_of( $statement );

	if ( null === $orderby || ! Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $orderby, $posts ) ) {
		skip( $tally, false, "{$name} (ORDER BY is not covered: " . ( $orderby ?? 'none' ) . ')' );
		return;
	}

	$rebuilt = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( new WP_Tax_Query( $queries ), $posts );

	if ( null === $rebuilt ) {
		skip( $tally, $must_cover, "{$name} (rebuild returned null: " . describe( $queries ) . ')' );
		return;
	}

	$own      = preg_match( '/\s+(LIMIT\s+\d+(?:\s*,\s*\d+)?)\s*$/i', $statement, $match ) ? $match[1] : 'no LIMIT';
	$variants = [
		"own ({$own})" => $statement,
		'LIMIT 0, 2'   => with_limit( $statement, 'LIMIT 0, 2' ),
		'LIMIT 0, 32'  => with_limit( $statement, 'LIMIT 0, 32' ),
		'no LIMIT'     => with_limit( $statement, null ),
	];

	foreach ( $variants as $label => $text ) {
		// The own LIMIT can be one of the others, and another statement can differ only by its
		// LIMIT. Each text is written and timed once.
		if ( isset( $tally['seen'][ $text ] ) ) {
			++$tally['duplicate_pairs'];
			continue;
		}

		$swapped = swaps( $text, $rebuilt, $posts, $terms );

		if ( null === $swapped['swapped_mysql'] || null === $swapped['swapped_mariadb'] ) {
			skip( $tally, $must_cover, "{$name} | {$label} (swap returned null)" );
			continue;
		}

		$tally['seen'][ $text ] = true;

		$line = wp_json_encode(
			[
				'name'            => "{$name} | {$label}",
				'original'        => $text,
				'swapped_mysql'   => $swapped['swapped_mysql'],
				'swapped_mariadb' => $swapped['swapped_mariadb'],
			],
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( ! is_string( $line ) ) {
			WP_CLI::error( "Could not encode the pair {$name} | {$label} as JSON." );
		}

		if ( false === fwrite( $handle, $line . "\n" ) ) {
			WP_CLI::error( "Could not write the pair {$name} | {$label} to the pairs file." );
		}

		++$tally['pairs'];
	}
}

global $wpdb;

$site = site_slug();
$out  = "/tmp/mai-optimizer-pairs-{$site}.jsonl";
$in   = isset( $args[0] ) && is_string( $args[0] ) ? $args[0] : "/tmp/mai-optimizer-statements-{$site}.jsonl";

$handle = fopen( $out, 'w' );

if ( false === $handle ) {
	WP_CLI::error( "Cannot write {$out}." );
}

$tally = [
	'captured'        => 0,
	'duplicates'      => 0,
	'duplicate_pairs' => 0,
	'statements'      => 0,
	'pairs'           => 0,
	'expected'        => [],
	'unexpected'      => [],
	'seen'            => [],
];
$seen  = [];

// Captured statements first.
if ( is_readable( $in ) ) {
	foreach ( (array) file( $in, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $number => $row ) {
		$data = json_decode( (string) $row, true );

		if ( ! is_array( $data ) || ! is_string( $data['statement'] ?? null ) || ! is_array( $data['queries'] ?? null ) ) {
			WP_CLI::warning( "Line " . ( $number + 1 ) . " of {$in} is not a captured statement. Skipped." );
			continue;
		}

		++$tally['captured'];

		$posts     = (string) ( $data['posts'] ?? $wpdb->posts );
		$terms     = (string) ( $data['terms'] ?? $wpdb->term_relationships );
		$role      = (string) ( $data['role'] ?? 'grid' );
		$statement = $data['statement'];

		// A grid's own statement reaches the database in its ID-only form, when WordPress splits
		// it. One split() cannot rewrite, such as a Load More grid's, is written as captured.
		if ( 'grid' === $role ) {
			$split = Mai_Post_Grid_Query_Optimizer_Sql::split( $statement, $posts );

			if ( null === $split ) {
				WP_CLI::log( 'Captured grid statement ' . $tally['captured'] . ' is not in the form split() takes, so it is written as captured.' );
			} else {
				$statement = $split;
			}
		}

		if ( isset( $seen[ $statement ] ) ) {
			++$tally['duplicates'];
			continue;
		}

		$seen[ $statement ] = true;

		// A real page can hold any grid, so a captured statement that is not covered is expected.
		write_pairs( $handle, 'captured ' . $role . ' ' . ( $tally['captured'] ) . ' [' . describe( $data['queries'] ) . ']', $statement, $data['queries'], $posts, $terms, false, $tally );
	}
} else {
	WP_CLI::warning( "No captured statements at {$in}. Only the synthetic statements are written." );
}

// Synthetic statements next.
$found = find_terms();

foreach ( $found as $label => $term ) {
	WP_CLI::log( sprintf( 'Term %-9s %s %d (%d posts)', $label, $term['taxonomy'], $term['id'], $term['count'] ) );
}

foreach ( synthetic_specs( $found ) as $spec ) {
	$built = build( $spec['args'], $spec['admin'] );

	if ( null === $built ) {
		skip( $tally, false, "{$spec['name']} (WordPress wrote no tax query)" );
		continue;
	}

	if ( isset( $seen[ $built['statement'] ] ) ) {
		++$tally['duplicates'];
		continue;
	}

	$seen[ $built['statement'] ] = true;

	write_pairs( $handle, $spec['name'], $built['statement'], $built['queries'], $wpdb->posts, $wpdb->term_relationships, $spec['covered'], $tally );
}

fclose( $handle );

foreach ( $tally['expected'] as $why ) {
	WP_CLI::log( "Skipped, not covered: {$why}" );
}

foreach ( $tally['unexpected'] as $why ) {
	WP_CLI::warning( "Skipped, but meant to be covered: {$why}" );
}

if ( $tally['unexpected'] ) {
	WP_CLI::error( count( $tally['unexpected'] ) . ' synthetic statements meant to be covered were skipped, listed above. The optimizer no longer covers a shape it should, or the site writes it differently.' );
}

if ( 0 === $tally['pairs'] ) {
	WP_CLI::error( "No pairs were written to {$out}, so there is nothing to replay." );
}

WP_CLI::success(
	sprintf(
		'%d captured statements read, %d duplicate statements and %d duplicate pair texts left out, %d statements checked, %d skipped as not covered, %d pairs written to %s.',
		$tally['captured'],
		$tally['duplicates'],
		$tally['duplicate_pairs'],
		$tally['statements'],
		count( $tally['expected'] ),
		$tally['pairs'],
		$out
	)
);
