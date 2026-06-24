# Mai Post Grid Query Optimization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Mai post grid "posts in these terms" query fast on every render by rewriting its tax filter into a correlated scalar subquery, in a new hook-wired class, with no caching.

**Architecture:** A new self-contained class `Mai_Post_Grid_Query_Optimizer` registers on the existing `mai_post_grid_query_args` filter to detect the simple single-`IN` tax case, resolve its `term_taxonomy_id`s, strip the `tax_query` (so WordPress builds no JOIN), set `no_found_rows`, and stash the ids on the query. Two clause filters then add a `WHERE (SELECT ... LIMIT 1) IS NOT NULL` scalar subquery and an `ID DESC` order tiebreaker. `Mai_Grid` is not modified. Everything that is not the proven-equivalent simple-`IN` shape falls through to stock WordPress untouched.

**Tech Stack:** PHP 8.1+, WordPress (WP_Query / posts_* clause filters), PHPUnit 10.5 with brain/monkey for unit tests, WP-CLI for the equivalence check.

## Global Constraints

- Target runtime PHP 8.1+. Use modern idioms (`str_contains`, typed signatures, `match`).
- Filter names exactly: `mai_post_grid_optimize_query` (master on/off, default `true`); `mai_post_grid_found_rows` (keep the row count, default `false`).
- Optimize only the simple case: a `tax_query` with exactly one clause whose operator is `IN`. Every other case (NOT IN, AND, multiple clauses, relations, no tax_query) returns the stock query untouched.
- Output must equal stock for the simple case; the only allowed difference is deterministic tie ordering (`ORDER BY post_date DESC, ID DESC`).
- No caching, no `mai-cache`, no optimizer hints, no engine/version detection. Portable SQL only.
- Post grids only. Never touch term grids (`WP_Term_Query`).
- No em-dashes in shipped code or comments. Keep durable why-comments.

---

### Task 1: Unit test harness

**Files:**
- Create: `tests/phpunit/unit/bootstrap.php`
- Create: `tests/TestCase.php`
- Create: `tests/phpunit/unit/SmokeTest.php`
- Modify: `composer.json` (add `autoload-dev` for the test base class)

**Interfaces:**
- Produces: `Mai\Engine\Tests\TestCase` base class extending `PHPUnit\Framework\TestCase`, wiring brain/monkey `setUp`/`tearDown`. All later unit tests extend it.

- [ ] **Step 1: Create the unit bootstrap**

```php
<?php
// tests/phpunit/unit/bootstrap.php
// Unit suite: no WordPress, no DB. brain/monkey mocks WP functions.

defined( 'ABSPATH' ) || define( 'ABSPATH', sys_get_temp_dir() . '/' );

require_once dirname( __DIR__, 2 ) . '/../vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/TestCase.php';

// Class under test (mai-engine's runtime autoloader is not active without WP).
require_once dirname( __DIR__, 3 ) . '/lib/classes/class-mai-post-grid-query-optimizer.php';
```

- [ ] **Step 2: Create the TestCase base**

```php
<?php
// tests/TestCase.php
namespace Mai\Engine\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
```

- [ ] **Step 3: Add a smoke test**

```php
<?php
// tests/phpunit/unit/SmokeTest.php
namespace Mai\Engine\Tests\Unit;

use Mai\Engine\Tests\TestCase;

final class SmokeTest extends TestCase {
	public function test_harness_runs(): void {
		$this->assertTrue( true );
	}
}
```

- [ ] **Step 4: Add autoload-dev to composer.json**

In `composer.json`, add under the top-level object (sibling of `autoload`):

```json
"autoload-dev": {
	"psr-4": { "Mai\\Engine\\Tests\\": "tests/" }
}
```

- [ ] **Step 5: Run the unit suite**

Run: `composer test-unit`
Expected: PASS, 1 test, 1 assertion.

- [ ] **Step 6: Commit**

```bash
git add tests/ composer.json
git commit -m "test: add brain/monkey unit harness for mai-engine"
```

---

### Task 2: Optimizer class skeleton and registration

**Files:**
- Create: `lib/classes/class-mai-post-grid-query-optimizer.php`
- Modify: `lib/functions/performance.php` (register the optimizer)
- Test: `tests/phpunit/unit/PostGridQueryOptimizerRegisterTest.php`

**Interfaces:**
- Produces: `Mai_Post_Grid_Query_Optimizer::register(): void` adds the three filters. Master kill-switch `mai_post_grid_optimize_query` (default `true`) gates registration.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/phpunit/unit/PostGridQueryOptimizerRegisterTest.php
namespace Mai\Engine\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mai\Engine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerRegisterTest extends TestCase {
	public function test_register_adds_filters_when_enabled(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value ); // optimize_query stays true

		( new Mai_Post_Grid_Query_Optimizer() )->register();

		$this->assertNotFalse( has_filter( 'mai_post_grid_query_args' ) );
		$this->assertNotFalse( has_filter( 'posts_where' ) );
		$this->assertNotFalse( has_filter( 'posts_orderby' ) );
	}

	public function test_register_is_noop_when_disabled(): void {
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_optimize_query' === $tag ? false : $value
		);

		( new Mai_Post_Grid_Query_Optimizer() )->register();

		$this->assertFalse( has_filter( 'mai_post_grid_query_args' ) );
	}
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `composer test-unit`
Expected: FAIL ("Class Mai_Post_Grid_Query_Optimizer not found").

- [ ] **Step 3: Create the class skeleton**

```php
<?php
/**
 * Mai Post Grid Query Optimizer.
 *
 * Rewrites the simple "posts IN these terms" grid tax query from the stock
 * LEFT JOIN + GROUP BY + filesort into a correlated scalar subquery, which the
 * optimizer plans as a backward index scan that stops at LIMIT. Hook-wired; does
 * not modify Mai_Grid. Falls back to stock WordPress for anything but the simple
 * single-IN case.
 *
 * @package BizBudding\MaiEngine
 */

defined( 'ABSPATH' ) || die;

class Mai_Post_Grid_Query_Optimizer {

	/**
	 * Query var that carries the resolved term_taxonomy_ids from the args filter
	 * to the clause filters.
	 */
	private const TT_IDS_VAR = 'mai_post_grid_tt_ids';

	/**
	 * Register hooks, unless the master switch is off.
	 */
	public function register(): void {
		if ( ! apply_filters( 'mai_post_grid_optimize_query', true ) ) {
			return;
		}

		add_filter( 'mai_post_grid_query_args', [ $this, 'maybe_optimize' ], 99, 2 );
		add_filter( 'posts_where', [ $this, 'add_subquery_where' ], 10, 2 );
		add_filter( 'posts_orderby', [ $this, 'add_orderby_tiebreaker' ], 10, 2 );
	}

	public function maybe_optimize( array $query_args, array $args ): array {
		return $query_args;
	}

	public function add_subquery_where( string $where, $query ): string {
		return $where;
	}

	public function add_orderby_tiebreaker( string $orderby, $query ): string {
		return $orderby;
	}
}
```

- [ ] **Step 4: Register it in performance.php**

Read `lib/functions/performance.php` first to match its style, then append:

```php
add_action( 'init', function () {
	( new Mai_Post_Grid_Query_Optimizer() )->register();
} );
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `composer test-unit`
Expected: PASS (register tests green).

- [ ] **Step 6: Commit**

```bash
git add lib/classes/class-mai-post-grid-query-optimizer.php lib/functions/performance.php tests/phpunit/unit/PostGridQueryOptimizerRegisterTest.php
git commit -m "feat: scaffold Mai_Post_Grid_Query_Optimizer with kill-switch"
```

---

### Task 3: Simple-IN classification

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerClassifyTest.php`

**Interfaces:**
- Produces: `get_simple_in_clause( array $query_args ): ?array` returns the single tax clause array (`['taxonomy' => string, 'terms' => int[], ...]`) when the query has exactly one `IN` clause, else `null`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/phpunit/unit/PostGridQueryOptimizerClassifyTest.php
namespace Mai\Engine\Tests\Unit;

use Mai\Engine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;
use ReflectionMethod;

final class PostGridQueryOptimizerClassifyTest extends TestCase {
	private function classify( array $query_args ): ?array {
		$m = new ReflectionMethod( Mai_Post_Grid_Query_Optimizer::class, 'get_simple_in_clause' );
		$m->setAccessible( true );
		return $m->invoke( new Mai_Post_Grid_Query_Optimizer(), $query_args );
	}

	public function test_single_in_clause_is_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 1, 2 ], 'operator' => 'IN' ];
		$this->assertSame( $clause, $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_missing_operator_defaults_to_in(): void {
		$clause = [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 3 ] ];
		$this->assertNotNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_not_in_is_not_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'terms' => [ 1 ], 'operator' => 'NOT IN' ];
		$this->assertNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_multiple_clauses_not_simple(): void {
		$tax = [
			'relation' => 'AND',
			[ 'taxonomy' => 'category', 'terms' => [ 1 ], 'operator' => 'IN' ],
			[ 'taxonomy' => 'post_tag', 'terms' => [ 2 ], 'operator' => 'IN' ],
		];
		$this->assertNull( $this->classify( [ 'tax_query' => $tax ] ) );
	}

	public function test_no_tax_query_not_simple(): void {
		$this->assertNull( $this->classify( [ 'post_type' => 'post' ] ) );
	}

	public function test_empty_terms_not_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'terms' => [], 'operator' => 'IN' ];
		$this->assertNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}
}
```

- [ ] **Step 2: Run to verify failure**

Run: `composer test-unit`
Expected: FAIL ("get_simple_in_clause does not exist").

- [ ] **Step 3: Implement the classifier**

Add to the class:

```php
/**
 * Return the single tax clause if this is the simple "one IN clause" case, else null.
 * Strips the 'relation' key, requires exactly one clause, operator IN (default),
 * a string taxonomy and a non-empty terms array.
 */
private function get_simple_in_clause( array $query_args ): ?array {
	$tax_query = $query_args['tax_query'] ?? null;

	if ( ! is_array( $tax_query ) || ! $tax_query ) {
		return null;
	}

	// Drop the relation key; what remains must be exactly one first-order clause.
	$clauses = array_filter(
		$tax_query,
		fn( $key ) => 'relation' !== $key,
		ARRAY_FILTER_USE_KEY
	);

	if ( 1 !== count( $clauses ) ) {
		return null;
	}

	$clause = reset( $clauses );

	if ( ! is_array( $clause ) || isset( $clause['relation'] ) ) {
		return null; // nested clause group, not first-order.
	}

	$operator = strtoupper( (string) ( $clause['operator'] ?? 'IN' ) );
	$terms    = $clause['terms'] ?? [];

	$is_simple = 'IN' === $operator
		&& is_string( $clause['taxonomy'] ?? null )
		&& is_array( $terms )
		&& [] !== $terms;

	return $is_simple ? $clause : null;
}
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test-unit`
Expected: PASS (all classify tests green).

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-post-grid-query-optimizer.php tests/phpunit/unit/PostGridQueryOptimizerClassifyTest.php
git commit -m "feat: classify simple single-IN grid tax queries"
```

---

### Task 4: Resolve term_taxonomy_ids and rewrite the args

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerArgsTest.php`

**Interfaces:**
- Produces: `resolve_tt_ids( string $taxonomy, array $term_ids ): array` returns `int[]` term_taxonomy_ids including child terms (mirrors WP_Tax_Query `include_children` default).
- Produces: `maybe_optimize()` now strips `tax_query`, sets `no_found_rows` (unless `mai_post_grid_found_rows` opts in), and stashes the ids under the `mai_post_grid_tt_ids` query var.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/phpunit/unit/PostGridQueryOptimizerArgsTest.php
namespace Mai\Engine\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Engine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerArgsTest extends TestCase {
	public function test_resolves_terms_with_children_to_tt_ids(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( true );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_children' )->alias( fn( $id ) => 10 === $id ? [ 11 ] : [] );
		Functions\when( 'get_term' )->alias(
			fn( $id ) => (object) [ 'term_taxonomy_id' => [ 10 => 110, 11 => 111 ][ $id ] ?? 0 ]
		);

		$o   = new Mai_Post_Grid_Query_Optimizer();
		$ref = new \ReflectionMethod( $o, 'resolve_tt_ids' );
		$ref->setAccessible( true );

		$this->assertSame( [ 110, 111 ], $ref->invoke( $o, 'category', [ 10 ] ) );
	}

	public function test_strips_tax_query_and_stashes_ids_and_sets_no_found_rows(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( (object) [ 'term_taxonomy_id' => 200 ] );
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value ); // found_rows stays false

		$args_in = [
			'post_type' => 'post',
			'tax_query' => [ [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 5 ], 'operator' => 'IN' ] ],
		];

		$out = ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] );

		$this->assertArrayNotHasKey( 'tax_query', $out );
		$this->assertSame( [ 200 ], $out['mai_post_grid_tt_ids'] );
		$this->assertTrue( $out['no_found_rows'] );
	}

	public function test_keeps_found_rows_when_opted_in(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( (object) [ 'term_taxonomy_id' => 1 ] );
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_found_rows' === $tag ? true : $value
		);

		$args_in = [ 'tax_query' => [ [ 'taxonomy' => 'category', 'terms' => [ 5 ], 'operator' => 'IN' ] ] ];

		$out = ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] );

		$this->assertArrayNotHasKey( 'no_found_rows', $out );
		$this->assertSame( [ 1 ], $out['mai_post_grid_tt_ids'] );
	}

	public function test_non_simple_query_is_untouched(): void {
		$args_in = [ 'post_type' => 'post' ];
		$this->assertSame( $args_in, ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] ) );
	}
}
```

- [ ] **Step 2: Run to verify failure**

Run: `composer test-unit`
Expected: FAIL (`resolve_tt_ids` missing; `maybe_optimize` still returns args unchanged).

- [ ] **Step 3: Implement resolution and the args rewrite**

Replace the stub `maybe_optimize` and add `resolve_tt_ids`:

```php
public function maybe_optimize( array $query_args, array $args ): array {
	$clause = $this->get_simple_in_clause( $query_args );

	if ( null === $clause ) {
		return $query_args; // stock fallback.
	}

	$tt_ids = $this->resolve_tt_ids( $clause['taxonomy'], (array) $clause['terms'] );

	if ( ! $tt_ids ) {
		return $query_args; // could not resolve; stay on stock.
	}

	// Remove the tax_query so WordPress emits no JOIN or GROUP BY; we add our own clause.
	unset( $query_args['tax_query'] );

	// Stash the resolved ids for the clause filters.
	$query_args[ self::TT_IDS_VAR ] = $tt_ids;

	// Drop SQL_CALC_FOUND_ROWS unless a consumer (e.g. load-more) needs the total.
	// Required for the early stop: with the count on, MySQL evaluates the subquery
	// for every matching row just to total them.
	if ( ! apply_filters( 'mai_post_grid_found_rows', false, $args ) ) {
		$query_args['no_found_rows'] = true;
	}

	return $query_args;
}

/**
 * Resolve term ids to term_taxonomy_ids, including child terms, mirroring the
 * WP_Tax_Query include_children default for IN queries.
 *
 * @return int[]
 */
private function resolve_tt_ids( string $taxonomy, array $term_ids ): array {
	$term_ids = array_map( 'intval', $term_ids );

	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		foreach ( $term_ids as $term_id ) {
			$children = get_term_children( $term_id, $taxonomy );
			if ( ! is_wp_error( $children ) ) {
				$term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
			}
		}
		$term_ids = array_values( array_unique( $term_ids ) );
	}

	$tt_ids = [];
	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			$tt_ids[] = (int) $term->term_taxonomy_id;
		}
	}

	return array_values( array_unique( $tt_ids ) );
}
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test-unit`
Expected: PASS (args + resolution tests green).

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-post-grid-query-optimizer.php tests/phpunit/unit/PostGridQueryOptimizerArgsTest.php
git commit -m "feat: resolve tt_ids and rewrite grid args to drop the tax JOIN"
```

---

### Task 5: Inject the scalar subquery WHERE

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerWhereTest.php`

**Interfaces:**
- Produces: `add_subquery_where( string $where, WP_Query $query ): string` appends the correlated scalar subquery only for queries carrying the `mai_post_grid_tt_ids` var.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/phpunit/unit/PostGridQueryOptimizerWhereTest.php
namespace Mai\Engine\Tests\Unit;

use Mai\Engine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerWhereTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb = new class {
			public string $posts = 'wp_posts';
			public string $term_relationships = 'wp_term_relationships';
		};
	}

	private function query_with( $tt_ids ) {
		return new class( $tt_ids ) {
			public array $query_vars;
			public function __construct( $tt_ids ) {
				$this->query_vars = null === $tt_ids ? [] : [ 'mai_post_grid_tt_ids' => $tt_ids ];
			}
		};
	}

	public function test_appends_subquery_for_flagged_query(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_subquery_where( ' AND 1=1', $this->query_with( [ 110, 111 ] ) );
		$this->assertStringContainsString( 'SELECT mtr.object_id FROM wp_term_relationships mtr', $out );
		$this->assertStringContainsString( 'mtr.object_id = wp_posts.ID', $out );
		$this->assertStringContainsString( 'mtr.term_taxonomy_id IN (110,111)', $out );
		$this->assertStringContainsString( 'LIMIT 1) IS NOT NULL', $out );
	}

	public function test_untouched_when_not_flagged(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_subquery_where( ' AND 1=1', $this->query_with( null ) );
		$this->assertSame( ' AND 1=1', $out );
	}
}
```

- [ ] **Step 2: Run to verify failure**

Run: `composer test-unit`
Expected: FAIL (stub returns `$where` unchanged).

- [ ] **Step 3: Implement the WHERE injection**

```php
public function add_subquery_where( string $where, $query ): string {
	$tt_ids = $query->query_vars[ self::TT_IDS_VAR ] ?? null;

	if ( ! is_array( $tt_ids ) || ! $tt_ids ) {
		return $where;
	}

	global $wpdb;
	$ids = implode( ',', array_map( 'intval', $tt_ids ) );

	// Correlated scalar subquery. Not eligible for the semi-join transform, so MySQL
	// keeps it correlated, drives from posts in date order, and stops at LIMIT.
	$where .= " AND ( (SELECT mtr.object_id FROM {$wpdb->term_relationships} mtr"
		. " WHERE mtr.object_id = {$wpdb->posts}.ID AND mtr.term_taxonomy_id IN ({$ids})"
		. ' LIMIT 1) IS NOT NULL )';

	return $where;
}
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test-unit`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-post-grid-query-optimizer.php tests/phpunit/unit/PostGridQueryOptimizerWhereTest.php
git commit -m "feat: inject correlated scalar subquery for grid term filter"
```

---

### Task 6: Deterministic order tiebreaker

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerOrderbyTest.php`

**Interfaces:**
- Produces: `add_orderby_tiebreaker( string $orderby, WP_Query $query ): string` appends `, {posts}.ID {DIR}` when a flagged query orders by `post_date` and has no ID tiebreaker yet.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/phpunit/unit/PostGridQueryOptimizerOrderbyTest.php
namespace Mai\Engine\Tests\Unit;

use Mai\Engine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerOrderbyTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb = new class {
			public string $posts = 'wp_posts';
			public string $term_relationships = 'wp_term_relationships';
		};
	}

	private function query_with( $tt_ids ) {
		return new class( $tt_ids ) {
			public array $query_vars;
			public function __construct( $tt_ids ) {
				$this->query_vars = null === $tt_ids ? [] : [ 'mai_post_grid_tt_ids' => $tt_ids ];
			}
		};
	}

	public function test_appends_id_desc_for_date_desc(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date DESC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.post_date DESC, wp_posts.ID DESC', $out );
	}

	public function test_matches_asc_direction(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date ASC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.post_date ASC, wp_posts.ID ASC', $out );
	}

	public function test_noop_when_not_flagged(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date DESC', $this->query_with( null ) );
		$this->assertSame( 'wp_posts.post_date DESC', $out );
	}

	public function test_noop_when_not_ordering_by_date(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.menu_order ASC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.menu_order ASC', $out );
	}
}
```

- [ ] **Step 2: Run to verify failure**

Run: `composer test-unit`
Expected: FAIL.

- [ ] **Step 3: Implement the tiebreaker**

```php
public function add_orderby_tiebreaker( string $orderby, $query ): string {
	$tt_ids = $query->query_vars[ self::TT_IDS_VAR ] ?? null;

	if ( ! is_array( $tt_ids ) || ! $tt_ids ) {
		return $orderby;
	}

	global $wpdb;

	// Already has an ID tiebreaker, or not ordering by date: leave it.
	if ( str_contains( $orderby, "{$wpdb->posts}.ID" ) ) {
		return $orderby;
	}

	if ( ! preg_match( '/' . preg_quote( $wpdb->posts, '/' ) . '\.post_date\s+(ASC|DESC)/i', $orderby, $m ) ) {
		return $orderby;
	}

	// Match the primary direction so it stays a single index scan.
	return $orderby . ", {$wpdb->posts}.ID " . strtoupper( $m[1] );
}
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test-unit`
Expected: PASS. Then run the full suite to confirm nothing regressed: `composer test-unit` (all files).

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-post-grid-query-optimizer.php tests/phpunit/unit/PostGridQueryOptimizerOrderbyTest.php
git commit -m "feat: add deterministic ID tiebreaker to grid date ordering"
```

---

### Task 7: WP-CLI equivalence and EXPLAIN check

**Files:**
- Create: `bin/grid-query-equivalence.php` (a WP-CLI eval-file script, not shipped in releases)

**Interfaces:**
- Consumes: the live optimizer on a real site. Produces a pass/fail equivalence result plus the EXPLAIN for the rewritten query.

- [ ] **Step 1: Write the equivalence script**

```php
<?php
/**
 * Run on a real site: wp eval-file bin/grid-query-equivalence.php <taxonomy> <comma,term,ids>
 * Compares the optimized grid query result to the stock result, and prints EXPLAIN.
 */
global $wpdb;

$taxonomy = $args[0] ?? 'category';
$term_ids = array_map( 'intval', explode( ',', $args[1] ?? '' ) );

$base = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 6,
	'ignore_sticky_posts' => true,
	'fields'              => 'ids',
	'orderby'             => 'date',
	'order'               => 'DESC',
	'tax_query'           => [ [ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term_ids, 'operator' => 'IN' ] ],
];

// Stock: optimizer off.
add_filter( 'mai_post_grid_optimize_query', '__return_false', 999 );
$stock = ( new WP_Query( apply_filters( 'mai_post_grid_query_args', $base, [] ) ) )->posts;
remove_filter( 'mai_post_grid_optimize_query', '__return_false', 999 );

// Optimized: run through the same args filter the grid uses.
$opt = ( new WP_Query( apply_filters( 'mai_post_grid_query_args', $base, [] ) ) )->posts;

WP_CLI::log( 'stock:     ' . implode( ',', $stock ) );
WP_CLI::log( 'optimized: ' . implode( ',', $opt ) );
WP_CLI::log( sort( $stock ) === sort( $opt ) || $stock == $opt ? 'MATCH (set-equal)' : 'MISMATCH' );

$last = $wpdb->last_query;
foreach ( (array) $wpdb->get_results( "EXPLAIN {$last}" ) as $row ) {
	WP_CLI::log( print_r( $row, true ) );
}
```

- [ ] **Step 2: Run it on a real site**

Run (on eurweb staging or a Herd mai site, picking a real taxonomy + term ids):
`wp eval-file bin/grid-query-equivalence.php category 1,94150`

Expected:
- `stock` and `optimized` lists are set-equal (same posts; order may differ only by the deterministic tiebreaker on ties).
- The EXPLAIN shows `Backward index scan` on `posts` and no `Using temporary; Using filesort`.

- [ ] **Step 3: Commit**

```bash
git add bin/grid-query-equivalence.php
git commit -m "test: add wp-cli grid query equivalence and EXPLAIN check"
```

---

### Task 8: Integration verification on a large site

**Files:** none (verification only)

- [ ] **Step 1: Deploy the branch to eurweb (or a large staging clone)**

Check out `feature/mai-post-grid-query-optimization` on the site and disable the throwaway test filter (`includes/performance.php`) and the caching code, so only the real optimizer runs.

- [ ] **Step 2: Confirm grids render identically**

Load several grid-heavy pages logged out and logged in. Confirm the same posts appear as before (only tie order may change).

- [ ] **Step 3: Confirm the database win**

```bash
sudo mysql -e "TRUNCATE performance_schema.events_statements_summary_by_digest"
```
Let real traffic flow for a few minutes, then re-run the top-20 digest from the perf notes. Expected: the grid query digest `avg_ms` drops from hundreds of ms to low tens, and its `total_sec` share collapses.

- [ ] **Step 4: Record the before/after numbers in the PR description.**

---

## Self-Review

- Spec coverage: scope/trigger (Task 3), additive rewrite + no_found_rows (Tasks 4-5), tiebreaker (Task 6), found-rows opt-in (Task 4), kill-switch (Task 2), fallback (Tasks 3-4), filter API names (Tasks 2, 4), verification (Tasks 7-8), extracted class + performance.php registration (Task 2). All covered.
- Placeholder scan: no TBD/TODO; every code step has real code.
- Type consistency: `get_simple_in_clause(): ?array`, `resolve_tt_ids(): array`, `maybe_optimize(): array`, `add_subquery_where(): string`, `add_orderby_tiebreaker(): string`, and the `mai_post_grid_tt_ids` var name are used identically across tasks.
