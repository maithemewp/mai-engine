# Grid Deferred Excludes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop `exclude_current` and `exclude_displayed` from putting per-page-view post IDs into the grid's SQL, so every page sharing a grid's filters shares one result-cache entry.

**Architecture:** `Mai_Grid::get_post_query_args()` keeps returning exactly what it returns today; it just also records which IDs it contributed dynamically. `Mai_Grid::get_query()` then decides, once every `mai_post_grid_query_args` filter has run and the args are final, whether to defer. If so it strips those IDs from `post__not_in`, pads `posts_per_page` by their count, adds a post-ID tiebreaker so the padded query and the unpadded one cannot disagree about tied rows, runs the query, filters the IDs out of `$query->posts` in PHP, and restores the query object before returning.

**Tech Stack:** PHP 8.1+, WordPress 6.4+, PHPUnit 10.5 with brain/monkey (unit) and wp-phpunit (integration).

**Spec:** None by decision. Design settled by walk decisions in session `mai-engine-cache` on 2026-08-21. Evidence and reasoning are in the `project-grid-cache-key-shatter` memory and in Background below. Related deferred work: `.agents/elasticpress-grid-cache.md`.

**Revision:** This is the second draft. The first was reviewed by three agents and did not survive: it fataled on `array_diff()`, missed the `paged` guard, had no tiebreaker, and its integration tests could not run at all because the harness never loads `Mai_Grid`. Findings are folded in below. Do not restore anything from git history for this file.

## Global Constraints

- PHP floor is **8.1** (`mai-engine.php` `Requires PHP: 8.1`, `composer.json` `"php": "^8.1"`). Do not raise it.
- WordPress floor is **6.4** (`mai-engine.php` `Requires at least: 6.4`).
- **No em-dashes** in `CHANGES.md`, `readme.txt`, code comments, or commit messages. Rewrite the sentence rather than swapping punctuation.
- **The branch must stay deployable.** mai-engine deploys as-is with a committed `vendor/`. Never commit a with-dev autoloader. If composer runs for any reason, finish with `composer dump-autoload --no-dev` before committing.
- Follow the surrounding style in `lib/classes/class-mai-grid.php`: tabs, WordPress docblocks with `@since`, `@param`, `@return`.
- New public filters need a docblock with `@since 2.41.0`.
- Do not push any branch, do not open a PR, do not tag. Ask first.

## Background

Read all of this before Task 1. Several tasks look arbitrary without it.

**The problem.** `Mai_Grid::get_post_query_args()` (`lib/classes/class-mai-grid.php:583-618`) merges `get_the_ID()` and every already-rendered post ID into `post__not_in`. That reaches the SQL as `wp_posts.ID NOT IN (766283)`, and `Mai_Query_Cache::cache_key()` hashes both the query vars and the final SQL. One cache key per page view. On larrybrownsports that is 145,646 possible keys against a 4-hour TTL, while the average post is re-viewed every 8 hours, so nearly every read is a cold miss that stores an entry nobody will read.

**What a version bump actually does.** A publish does not wipe the cache. mai-cache stamps the version inside the stored envelope (`vendor/maithemewp/mai-cache/src/Cache.php:271-298`), so a mismatch returns stale-but-servable and `pre_query` serves it while one lock winner recomputes. Key cardinality is the problem, not publish cadence. **Do not touch invalidation as part of this work.**

**Why a longer TTL cannot fix it.** With one key per post, executions per view can never fall below (distinct posts viewed per day) / (views per day). On larrybrownsports that is 51,348 / 169,373 = 0.30, worse than the 0.2 target, even with an infinite TTL. Keys must be shared.

**Why the restore is load-bearing.** mai-load-more serializes `$query->query_vars` into a DOM attribute (`mai-load-more/classes/class-post-grid.php:96`) and rebuilds its own `WP_Query` from them in an AJAX request (`classes/class-ajax.php:126-187`). `Mai_Grid` is never constructed on that second request. A padded `posts_per_page` leaking out would make page 2 stride past posts; a missing `post__not_in` would drop the exclusions.

**Scope of the guarantee, stated precisely.** Anything reading the query object **after `get_query()` returns** sees the original request. Anything running **inside** `WP_Query::get_posts()` sees the padded, stripped version: `pre_get_posts`, `posts_where`, `posts_orderby`, `posts_clauses`, `post_limits`, `posts_request`, `posts_pre_query`, `the_posts`. Nothing in the Mai fleet depends on that, but do not write the broader claim anywhere.

**Ties are a real failure mode, and the tiebreaker is not optional.** `Mai_Grid` emits a bare `ORDER BY wp_posts.post_date DESC` with no tiebreaker (`:568-576`). When rows tie on the sort column, MySQL's LIMIT-aware sort may keep different ones for `LIMIT 6` than for `LIMIT 13`. Measured on larrybrownsports with a tie-heavy sort (`comment_count`, where 134,207 of 145,646 posts are tied at zero), across 120 grid renders:

| | mismatches | same posts, reordered | genuinely different posts |
| --- | --- | --- | --- |
| without a tiebreaker | 20 of 120 | 15 | **5** |
| with `, ID DESC` appended | **0 of 120** | 0 | 0 |

Date ordering never showed this in roughly 10,000 earlier cases, because two articles rarely publish in the same second. It is `meta_value_num` (trending, views), `comment_count` and `title` that have large tie groups.

**Measured on local mirrors** of larrybrownsports (145,646 posts), totalprosports (114,981) and tvnewscheck (180,083):

- 1,400 real grid renders across 700 articles, date ordering: 0 mismatches.
- 9,594 hostile cases aimed at short results, including excluding every article in a term: 0 mismatches. Grids showing 0, 1, 2, 3, 4 and 5 of 6 all matched.
- Padding cost, cache cleared each run so priming is real: `LIMIT 6` 252.6ms, `LIMIT 7` 308.7ms, `LIMIT 13` 298.8ms, `LIMIT 24` 313.7ms, `LIMIT 48` 314.3ms, `LIMIT 106` 323.5ms, `LIMIT 1000` 1718.2ms, `LIMIT 1040` 1739.9ms. Flat from 7 to about 106; the cliff is the show-all ceiling, not the pad.
- The 500-row split: `LIMIT 499` 246ms against `LIMIT 500` 1190ms, flat either side. A shape switch rather than a volume effect, and the reason for the guard in `can_defer_excludes()`. Method below, since a bare pair of numbers cannot be re-checked.
- Cache keys collapse 107x, 20x and 583x on the three sites.
- `offset > 0` genuinely differs, in roughly 1 of 150 articles. Hence the guard.

**How to re-take the 500-row split numbers.** The site is the larrybrownsports mirror and the category is Football, `term_id` 5, which holds 54,461 of that mirror's 145,646 posts. Confirm the category first, because the counts move as the mirror is refreshed:

```bash
cd ~/Herd/larrybrownsports && wp --path=. db query "SELECT t.term_id, t.name, tt.count FROM wp_term_taxonomy tt JOIN wp_terms t ON t.term_id = tt.term_id WHERE tt.taxonomy = 'category' ORDER BY tt.count DESC LIMIT 5;"
```

Then time a plain `WP_Query` for that category at each page size. Flush the object cache between runs so the priming cost is real, pass `mai_cache => false` so the grid result cache does not answer instead of the database, and warm the buffer pool once before timing anything. Run it with `wp --path=. eval-file`:

```php
<?php
$run = static function ( int $n ): float {
	wp_cache_flush();

	$start = microtime( true );

	new WP_Query( [
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $n,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'mai_cache'           => false,
		'tax_query'           => [
			[ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [ 5 ], 'operator' => 'IN' ],
		],
	] );

	return ( microtime( true ) - $start ) * 1000;
};

$run( 100 );

foreach ( [ 400, 490, 498, 499, 500, 501, 510, 600 ] as $n ) {
	$times = [ $run( $n ), $run( $n ), $run( $n ) ];
	sort( $times );

	printf( "LIMIT %4d: median %7.1fms\n", $n, $times[1] );
}
```

The step sits at exactly 500 because that is core's own threshold: `$split_the_query` is `$is_unfiltered_query && ( wp_using_ext_object_cache() || ( ! empty( $limits ) && $query_vars['posts_per_page'] < 500 ) )` in `WP_Query::get_posts()`. Below it core selects IDs and hydrates them; at it core selects whole rows and the tax query's temp table has to carry every matching post's full content. A site with a persistent object cache keeps the split at any size, which is why the guard costs nothing there, and it is also why this must be measured with `wp_using_ext_object_cache()` false.

Those mirrors are a **lab**, not a survey. They prove behavior against real data. They say nothing about what the wider fleet has installed, and no task may reason from what is or is not installed on them.

**Explicitly out of scope, and say so in the changelog:**

- **Term grids.** `lib/classes/class-mai-grid.php:731-747` has the same two settings for terms, writing into `exclude`. `Mai_Query_Cache` only hooks `posts_pre_query` (`lib/functions/query-cache.php:22`), so term grids have no result cache to shatter and nothing to gain. There is deliberately no `mai_term_grid_defer_excludes` filter.
- **Choice grids** (`query_by => 'id'`). The whole exclude block is already skipped for them (`:584`), so they never had dynamic excludes and nothing changes. Their `orderby => post__in` is a total order, so ties are impossible.

## Known and accepted

Decided, not oversights. Do not "fix" these without raising them first.

- **Tied rows can order differently than today.** The tiebreaker goes on the deferred query only, so a deferring grid picks different posts among equal-ranked ones than the same grid with deferring off. Today's order among ties is arbitrary and can change between page loads; this makes it fixed. Approved as item 1 of the walk.
- **`exclude_displayed` grids collapse partially, not fully.** The padding is `posts_per_page + count( $effective )`, and `posts_per_page` is hashed into the key, so a grid whose exclude count varies gets one key per count. For `exclude_current` the count is always 1 and the collapse is total. For `exclude_displayed` it varies with what rendered above it. Still far better than one key per page view, but do not write "one entry" in a changelog for that half.
- **`$query->request` is not restored.** It keeps the padded `LIMIT`, the stripped `NOT IN` and the tiebreaker, because that really is the SQL that ran. Query Monitor will show SQL that does not match the query vars beside it. Faking it would be worse than explaining it.
- **A cache hit can render fewer entries than asked.** `Mai_Query_Cache::hydrate()` drops ids whose post status changed (`lib/classes/class-mai-query-cache.php:374-381`). That is deliberate, so a stale grid can only shrink. Before this change it almost never showed, because nearly every read missed. Now reads hit, so it will. There is no re-pad and no top-up.
- **A third-party `posts_orderby` filter can defeat the tiebreaker.** The early return keys on the clause containing `wp_posts.ID`, which a "pin this post to the top" filter also produces without being deterministic. The tiebreaker is then skipped and the padded query stays tie-unstable. Nothing in the Mai fleet does this today.
- **`can_defer_excludes()` can decline a grid the cache would have taken.** It calls `is_cacheable()` with the grid args, where a `meta_value_num` grid has `meta_key` but no `meta_query` yet; `pre_query()` calls it later with `meta_query` already built. Only reachable when the optimizer is switched on, which is off by default. Declining to defer is the safe direction.
- ~~**The filter never fires for a guarded-out grid**, because `$can &&` short-circuits. A site cannot use `mai_post_grid_defer_excludes` to observe which grids declined.~~ Changed in review: the filter now fires for every grid and is handed the guards' verdict as its default, so a site can observe which grids declined. `$can &&` still wraps it, so returning true still cannot defeat a guard.
- **Sticky posts and image priming need nothing.** Core's sticky reshuffle is gated on `is_home && ! ignore_sticky_posts`, and grids always set `ignore_sticky_posts => true` (`lib/classes/class-mai-grid.php:410`). `mai_prime_featured_images_cache` returns early unless it is the main query on an archive (`lib/functions/performance.php:285`), so it never sees a grid.

## Preflight

Do this before Task 1, not after Task 7.

- [ ] **Settle `vendor/` before writing any code**

```bash
composer dump-autoload --no-dev
git status --porcelain vendor/
```

The working tree can carry a with-dev autoloader from ordinary local tooling. Committing one fatals every site on deploy; it took eurweb.com down on 2026-06-29. Expect at most `vendor/composer/installed.php` to differ afterwards, which is a commit-hash reference and harmless. If the four `autoload_*.php` files still show as modified, stop and find out why before continuing.

Every commit in this plan uses a targeted `git add`. Never `git add -A` or `git commit -a` on this branch.

## File Structure

| File | Responsibility |
| --- | --- |
| `lib/classes/class-mai-grid.php` (modify) | All production changes. |
| `tests/phpunit/integration/plugin-loader.php` (modify) | Load enough of the plugin that the integration suite can build a grid and register the cache. |
| `tests/phpunit/unit/GridDeferredExcludesTest.php` (create) | Recording and eligibility rules, brain/monkey, no WordPress. |
| `tests/phpunit/integration/GridDeferredExcludesTest.php` (create) | Real `WP_Query` equivalence and cache-key collapse. |
| `bin/grid-key-collapse.php` (create) | Lab measurement of key cardinality. |
| `CHANGES.md` (modify) | One changelog entry. |

No changes to `lib/classes/class-mai-query-cache.php`. The deferred IDs never become query vars, so nothing needs normalizing out of the key.

---

### Task 1: Record the dynamic excludes, changing nothing

A true pure refactor. The lines that build `$query_args` are left byte-for-byte alone; a second array is appended to alongside them. Normalization happens later, at the point of use, so this task cannot alter the emitted SQL.

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (add property near `:69`, add reset and two appends inside `:583-618`)
- Test: `tests/phpunit/unit/GridDeferredExcludesTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `protected array $deferred_excludes`, the raw IDs contributed by `exclude_displayed` and `exclude_current` this render, unnormalized and possibly containing duplicates or `false`. Task 2 normalizes it.

- [ ] **Step 1: Write the failing test**

Create `tests/phpunit/unit/GridDeferredExcludesTest.php`:

```php
<?php
// tests/phpunit/unit/GridDeferredExcludesTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid;
use ReflectionProperty;

final class GridDeferredExcludesTest extends TestCase {

	/**
	 * Builds a Mai_Grid without its constructor, then injects args. The constructor pulls in
	 * the whole field layer via get_sanitized_args() and get_defaults(); this suite is about
	 * the exclude logic only.
	 */
	private function grid( array $args ): Mai_Grid {
		$grid = ( new \ReflectionClass( Mai_Grid::class ) )->newInstanceWithoutConstructor();

		$prop = new ReflectionProperty( Mai_Grid::class, 'args' );
		$prop->setAccessible( true );
		$prop->setValue( $grid, $args + [
			'post_type'      => [ 'post' ],
			'posts_per_page' => 6,
			'offset'         => 0,
			'query_by'       => 'tax_meta',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post__not_in'   => '',
			'excludes'       => '',
			'taxonomies'     => '',
			'meta_keys'      => '',
			'date_after'     => '',
			'date_before'    => '',
		] );

		return $grid;
	}

	private function read( Mai_Grid $grid, string $name ) {
		$prop = new ReflectionProperty( Mai_Grid::class, $name );
		$prop->setAccessible( true );

		return $prop->getValue( $grid );
	}

	private function stub_wp(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
	}

	public function test_exclude_current_is_recorded_and_query_args_are_unchanged(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 99 ], $args['post__not_in'], 'this task must not change behavior' );
		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_author_set_exclusions_are_not_recorded(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ], 'post__not_in' => [ 7, 8 ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 7, 8, 99 ], $args['post__not_in'] );
		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ), 'author-set ids are stable per grid and belong in the key' );
	}

	public function test_exclude_displayed_is_recorded(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( false );

		Mai_Grid::$existing_post_ids = [ 'post' => [ 11, 12 ] ];

		$grid = $this->grid( [ 'excludes' => [ 'exclude_displayed' ] ] );
		$args = $grid->get_post_query_args();

		Mai_Grid::$existing_post_ids = [];

		$this->assertSame( [ 11, 12 ], $args['post__not_in'] );
		$this->assertSame( [ 11, 12 ], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_nothing_recorded_without_dynamic_excludes(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'post__not_in' => [ 7 ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 7 ], $args['post__not_in'] );
		$this->assertSame( [], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_choice_grids_record_nothing(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		// query_by 'id' skips the whole exclude block at class-mai-grid.php:584.
		$grid = $this->grid( [ 'query_by' => 'id', 'post__in' => [ 3, 4 ], 'excludes' => [ 'exclude_current' ] ] );
		$args = $grid->get_post_query_args();

		$this->assertArrayNotHasKey( 'post__not_in', $args );
		$this->assertSame( [], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_repeat_calls_do_not_accumulate(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		// get_post_query_args() is public. Two calls must not double the list.
		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ] ] );
		$grid->get_post_query_args();
		$grid->get_post_query_args();

		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ) );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test-unit -- --filter GridDeferredExcludesTest`
Expected: FAIL. `ReflectionProperty` throws `ReflectionException` because `Mai_Grid::$deferred_excludes` does not exist.

- [ ] **Step 3: Add the property**

In `lib/classes/class-mai-grid.php`, immediately after the `$existing_term_ids` property (around line 69):

```php
	/**
	 * Post IDs this render contributed through the exclude_displayed and exclude_current
	 * settings, as opposed to the author-set Exclude Entries field. These change with every
	 * page view, so they shatter the result cache key when they reach the SQL. Recorded raw
	 * here, exactly as they were added to post__not_in; get_query() normalizes them and
	 * decides whether to keep them out of the query and apply them in PHP instead.
	 *
	 * @since 2.41.0
	 *
	 * @var array
	 */
	protected $deferred_excludes = [];
```

- [ ] **Step 4: Record the IDs without touching the existing lines**

In `get_post_query_args()`, add the reset as the first statement of the method, immediately after the opening brace:

```php
		// get_post_query_args() is public and may be called more than once on one instance.
		// Start clean so a second call cannot accumulate ids from the first.
		$this->deferred_excludes = [];
```

Then, inside the exclude block, add one line to each of the two branches. The existing lines stay exactly as they are:

```php
			// Exclude displayed.
			if ( $this->args['excludes'] && in_array( 'exclude_displayed', $this->args['excludes'] ) && ! empty( $post__not_ins ) ) {
				if ( isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $post__not_ins );
				} else {
					$query_args['post__not_in'] = $post__not_ins;
				}

				$this->deferred_excludes = array_merge( $this->deferred_excludes, $post__not_ins );
			}

			// Exclude current.
			if ( is_singular() && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'], true ) ) {
				if ( isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'][] = get_the_ID();
				} else {
					$query_args['post__not_in'] = [ get_the_ID() ];
				}

				$this->deferred_excludes[] = get_the_ID();
			}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `composer test-unit -- --filter GridDeferredExcludesTest`
Expected: PASS, 6 tests.

- [ ] **Step 6: Run the whole unit suite**

Run: `composer test-unit`
Expected: PASS, no new failures.

- [ ] **Step 7: Commit**

```bash
git add lib/classes/class-mai-grid.php tests/phpunit/unit/GridDeferredExcludesTest.php
git commit -m "refactor(grid): record dynamic exclude ids separately from author-set ones

No behavior change. The lines that build post__not_in are untouched; a second
array records which ids came from exclude_displayed and exclude_current so a
later change can keep those out of the SQL."
```

---

### Task 2: Work out which IDs are still in play, and whether to defer

Two methods. `effective_excludes()` reconciles what was recorded with what actually survived to the final args, which is what makes the later `array_diff` safe. `can_defer_excludes()` is every guard.

The guards read the **final** args, after `mai_post_grid_query_args`, because filters change what they depend on: mai-load-more turns `no_found_rows` off at priority 10, mai-analytics rewrites `orderby` at 30, mai-elasticpress adds `ep_integrate`.

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (two methods after `get_post_query_args()`)
- Test: `tests/phpunit/unit/GridDeferredExcludesTest.php`

**Interfaces:**
- Consumes: `Mai_Grid::$deferred_excludes` from Task 1.
- Produces:
  - `protected function effective_excludes( array $query_args ): array` returning normalized int IDs that are both recorded as dynamic and still present in the final `post__not_in`. Empty array when there is nothing to do or the args are a shape it will not touch.
  - `protected function can_defer_excludes( array $query_args, array $effective ): bool`.

  Task 3 calls both from `get_query()`.

- [ ] **Step 1: Write the failing test**

Append inside the class in `tests/phpunit/unit/GridDeferredExcludesTest.php`:

```php
	private function effective( array $query_args, array $recorded = [ 99 ] ): array {
		$grid = $this->grid( [] );

		$prop = new ReflectionProperty( Mai_Grid::class, 'deferred_excludes' );
		$prop->setAccessible( true );
		$prop->setValue( $grid, $recorded );

		$method = new \ReflectionMethod( Mai_Grid::class, 'effective_excludes' );
		$method->setAccessible( true );

		return $method->invoke( $grid, $query_args );
	}

	private function can_defer( array $query_args, array $effective = [ 99 ] ): bool {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		return $method->invoke( $grid, $query_args + [
			'posts_per_page' => 6,
			'offset'         => 0,
			'no_found_rows'  => true,
			'mai_cache'      => true,
		], $effective );
	}

	// ---- effective_excludes() ----

	public function test_effective_normalizes_and_intersects(): void {
		$this->assertSame(
			[ 11, 12 ],
			$this->effective( [ 'post__not_in' => [ 7, 11, 12 ] ], [ 11, 12, 11 ] )
		);
	}

	public function test_effective_drops_an_id_a_filter_removed(): void {
		// A site filtered post__not_in to let the current post back in. Honour that.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => [ 7 ] ], [ 99 ] ) );
	}

	public function test_effective_drops_a_falsy_id(): void {
		// get_the_ID() returns int|false on a malformed singular request.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => [ 0 ] ], [ false ] ) );
	}

	public function test_effective_is_empty_when_post_not_in_is_missing(): void {
		// A filter unset the key. Must not fatal.
		$this->assertSame( [], $this->effective( [], [ 99 ] ) );
	}

	public function test_effective_is_empty_when_post_not_in_is_not_an_array(): void {
		// A filter set the comma string form WordPress also accepts. Leave the grid alone.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => '12,34' ], [ 12 ] ) );
	}

	// ---- can_defer_excludes() ----

	public function test_defers_for_an_ordinary_grid(): void {
		$this->assertTrue( $this->can_defer( [] ) );
	}

	public function test_does_not_defer_with_nothing_effective(): void {
		$this->assertFalse( $this->can_defer( [], [] ) );
	}

	public function test_does_not_defer_with_an_offset(): void {
		// The database skips rows before we can filter, so filtering after returns a
		// different set. Measured: differs for roughly 1 article in 150.
		$this->assertFalse( $this->can_defer( [ 'offset' => 3 ] ) );
	}

	public function test_does_not_defer_when_paged(): void {
		// Same mechanism as offset. LIMIT start is (paged - 1) * posts_per_page, so padding
		// the page size multiplies the start row and silently skips posts.
		$this->assertFalse( $this->can_defer( [ 'paged' => 2 ] ) );
	}

	public function test_does_not_defer_when_nopaging(): void {
		// No LIMIT is emitted at all, so padding does nothing and the slice would truncate.
		$this->assertFalse( $this->can_defer( [ 'nopaging' => true ] ) );
	}

	public function test_does_not_defer_when_found_rows_are_wanted(): void {
		$this->assertFalse( $this->can_defer( [ 'no_found_rows' => false ] ) );
	}

	public function test_does_not_defer_when_found_rows_key_is_absent(): void {
		// WP_Query's own default is false, meaning counting is ON. Absent must be treated
		// the same as false, not as true.
		$args = [ 'posts_per_page' => 6, 'offset' => 0, 'mai_cache' => true ];

		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}

	public function test_does_not_defer_for_facetwp(): void {
		$this->assertFalse( $this->can_defer( [ 'facetwp' => true ] ) );
	}

	public function test_does_not_defer_when_the_grid_is_not_cached(): void {
		$this->assertFalse( $this->can_defer( [ 'mai_cache' => false ] ) );
	}

	public function test_does_not_defer_past_the_show_all_ceiling(): void {
		// A show-all grid resolves to 1000. Padding it would step over the ceiling that
		// exists so one editor setting cannot take a site down.
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 1000 ], range( 1, 5 ) ) );
	}

	public function test_pads_right_up_to_the_ceiling(): void {
		$this->assertTrue( $this->can_defer( [ 'posts_per_page' => 995 ], range( 1, 5 ) ) );
	}

	public function test_filter_can_switch_it_off(): void {
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_defer_excludes' === $tag ? false : $value
		);

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$args = [ 'posts_per_page' => 6, 'offset' => 0, 'no_found_rows' => true, 'mai_cache' => true ];

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}

	public function test_filter_cannot_switch_it_on_past_a_guard(): void {
		// The filter is an opt-out only. Returning true must not defeat the offset guard.
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_defer_excludes' === $tag ? true : $value
		);

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$args = [ 'posts_per_page' => 6, 'offset' => 3, 'no_found_rows' => true, 'mai_cache' => true ];

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test-unit -- --filter GridDeferredExcludesTest`
Expected: FAIL with `ReflectionException: Method Mai_Grid::effective_excludes() does not exist`.

- [ ] **Step 3: Write both methods**

Add to `lib/classes/class-mai-grid.php`, immediately after `get_post_query_args()`:

```php
	/**
	 * The dynamic exclude IDs that are still in play for the final query.
	 *
	 * Reconciles what get_post_query_args() recorded against what actually survived every
	 * mai_post_grid_query_args filter. Two things fall out of that, both deliberate:
	 *
	 * A site that filters an id back out of post__not_in gets its override honored, because
	 * an id that is no longer in the query is no longer ours to apply in PHP either.
	 *
	 * A post__not_in that a filter replaced with something that is not an array (WordPress
	 * also accepts a comma string in places) returns empty here, so the grid falls back to
	 * today's behavior instead of fataling on array_diff().
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_args The final query args.
	 *
	 * @return int[]
	 */
	protected function effective_excludes( $query_args ) {
		if ( ! $this->deferred_excludes ) {
			return [];
		}

		$in_query = $query_args['post__not_in'] ?? null;

		if ( ! is_array( $in_query ) ) {
			return [];
		}

		// array_filter drops 0, which is what get_the_ID() casts to when it returns false.
		$recorded = array_filter( array_map( 'intval', $this->deferred_excludes ) );
		$in_query = array_map( 'intval', $in_query );

		return array_values( array_unique( array_intersect( $recorded, $in_query ) ) );
	}

	/**
	 * Whether this grid may keep its dynamic excludes out of the query and apply them in PHP.
	 *
	 * Called from get_query() with the final args, after every mai_post_grid_query_args filter
	 * has run. Checking the block settings instead would read stale values.
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_args The final query args.
	 * @param int[] $effective  The exclude IDs still in play, from effective_excludes().
	 *
	 * @return bool
	 */
	protected function can_defer_excludes( $query_args, $effective ) {
		$can = (bool) $effective;

		// An offset makes the database skip rows before we can filter, so filtering after
		// returns a different set. Measured on real archives: differs for roughly 1 article
		// in 150. `paged` is the same mechanism, since the LIMIT start is
		// (paged - 1) * posts_per_page and padding the page size multiplies the start row.
		if ( ! empty( $query_args['offset'] ) || ! empty( $query_args['paged'] ) ) {
			$can = false;
		}

		// No LIMIT is emitted at all, so there is nothing to pad and the slice would truncate
		// a query that was deliberately asked to return everything.
		if ( ! empty( $query_args['nopaging'] ) ) {
			$can = false;
		}

		if ( ! isset( $query_args['posts_per_page'] ) || $query_args['posts_per_page'] < 1 ) {
			$can = false;
		}

		// Something wants an accurate total, which means something is paginating this grid.
		// Padding inflates found_posts and skews the page count derived from it. empty()
		// rather than a false check on purpose: WP_Query's own default is false, so an absent
		// key means counting is ON and must be treated the same as an explicit false.
		if ( empty( $query_args['no_found_rows'] ) ) {
			$can = false;
		}

		// FacetWP rewrites the query for its own pagination.
		if ( ! empty( $query_args['facetwp'] ) ) {
			$can = false;
		}

		// Never step over the ceiling that exists so one editor setting cannot take a site
		// down. A show-all grid already sits at it, so it simply does not defer.
		$max = (int) apply_filters( 'mai_post_grid_max_posts_per_page', 1000 );

		if ( $max > 0 && isset( $query_args['posts_per_page'] ) && ( $query_args['posts_per_page'] + count( $effective ) ) > $max ) {
			$can = false;
		}

		// No point paying for this on a grid whose result will not be cached: the whole
		// benefit is a shared cache entry. Covers the mai_post_grid_cache opt-out, plus
		// everything Mai_Query_Cache refuses (ElasticPress, random order, the optimizer's
		// fast path). Calling is_cacheable() rather than restating its rules means the two
		// cannot drift apart. It fires the mai_query_cache filter a second time for this
		// query, which is harmless for a filter that only answers a question.
		if ( empty( $query_args['mai_cache'] ) ) {
			$can = false;
		}

		if ( $can && class_exists( 'Mai_Query_Cache' ) && ! ( new Mai_Query_Cache() )->is_cacheable( $query_args ) ) {
			$can = false;
		}

		/**
		 * Filters whether a grid keeps exclude_displayed and exclude_current out of the query
		 * and applies them while rendering instead. Off means those IDs go into post__not_in
		 * as before, which gives that grid a separate cache entry per page view.
		 *
		 * This is an opt-out. Returning true cannot turn deferring on for a grid the guards
		 * above ruled out, because those guards protect correctness rather than preference.
		 *
		 * @since 2.41.0
		 *
		 * @param bool  $enabled    Whether deferring is allowed. Default true.
		 * @param array $query_args The final query args.
		 * @param array $args       The grid args.
		 */
		return $can && (bool) apply_filters( 'mai_post_grid_defer_excludes', true, $query_args, $this->args );
	}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test-unit -- --filter GridDeferredExcludesTest`
Expected: PASS, 24 tests.

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-grid.php tests/phpunit/unit/GridDeferredExcludesTest.php
git commit -m "feat(grid): add effective_excludes() and can_defer_excludes()

Reconciles recorded ids against the final query args, and guards on offset,
paged, nopaging, found-rows counting, FacetWP, the show-all ceiling and
cacheability. The mai_post_grid_defer_excludes filter is opt-out only, so it
cannot defeat a correctness guard. Not wired up yet."
```

---

### Task 3: Make the integration suite able to build a grid

The first draft's integration tests all died on `Error: Class "Mai_Grid" not found`. `tests/phpunit/integration/plugin-loader.php` deliberately loads only three files and never registers the `Mai_*` autoloader. This task fixes that and proves it, before any test depends on it.

Keep the loader's existing discipline: add what is needed and no more. Requiring all of `lib/` pulls in the Genesis dependency the suite exists to avoid.

**Files:**
- Modify: `tests/phpunit/integration/plugin-loader.php`
- Test: `tests/phpunit/integration/HarnessTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Mai_Grid`, `Mai_Query_Cache` and the `lib/fields/` helpers available in the integration suite, with `Mai_Query_Cache` hooked to `posts_pre_query` and `the_posts`.

- [ ] **Step 1: Write the failing test**

Append to `tests/phpunit/integration/HarnessTest.php`, inside the class:

```php
	public function test_grid_classes_are_available(): void {
		$this->assertTrue( class_exists( 'Mai_Grid' ), 'plugin-loader.php must register the Mai_* autoloader' );
		$this->assertTrue( class_exists( 'Mai_Query_Cache' ) );
		$this->assertTrue( function_exists( 'mai_get_wp_query_defaults' ), 'lib/fields/wp-query.php must be loaded' );
	}

	public function test_query_cache_is_hooked(): void {
		// query-cache.php hooks on init, and init has already fired by the time a test runs,
		// so this only passes if plugin-loader.php loaded it on muplugins_loaded.
		//
		// Assert on the callback, not just on has_filter(). Core hooks the_posts itself, so a
		// bare has_filter( 'the_posts' ) is green even when the cache never loaded.
		$cache = null;

		foreach ( $GLOBALS['wp_filter']['posts_pre_query']->callbacks ?? [] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof \Mai_Query_Cache ) {
					$cache = $callback['function'][0];
				}
			}
		}

		$this->assertInstanceOf( \Mai_Query_Cache::class, $cache, 'Mai_Query_Cache is not on posts_pre_query' );
	}

	public function test_a_grid_can_be_built(): void {
		$term = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $term ] ] );

		$grid = new \Mai_Grid( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 3,
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $term ], 'current' => false, 'operator' => 'IN' ],
			],
		] );

		$this->assertInstanceOf( \WP_Query::class, $grid->get_query() );
	}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test-integration -- --filter HarnessTest`
Expected: FAIL on all three new tests. `class_exists( 'Mai_Grid' )` is false.

- [ ] **Step 3: Extend the plugin loader**

Append to `tests/phpunit/integration/plugin-loader.php`:

```php
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test-integration -- --filter HarnessTest`
Expected: PASS.

If the bootstrap dies rather than a test failing, read the stack trace: it is almost certainly another function one of these files needs at load time. Add only the one file that declares it and note which, rather than requiring more of `lib/`.

- [ ] **Step 5: Run both suites**

Run: `composer test`
Expected: PASS. Watch for new deprecation or warning output from the newly loaded files; `failOnWarning` is on in both configs.

- [ ] **Step 6: Commit**

```bash
git add tests/phpunit/integration/plugin-loader.php tests/phpunit/integration/HarnessTest.php
git commit -m "test: let the integration suite build a Mai_Grid

Registers the Mai_* autoloader and loads the three field-helper files the grid
constructor needs, plus query-cache.php so the result cache is actually hooked.
Still no Genesis dependency."
```

---

### Task 4: Defer, tiebreak, filter and restore

The whole change. It is one task because the order is the design: the cache stores during `the_posts`, which fires inside `new WP_Query`, so the filtering and the restore must both happen after the constructor returns.

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (the `post` branch of `get_query()`, plus one new method)
- Test: `tests/phpunit/integration/GridDeferredExcludesTest.php`

**Interfaces:**
- Consumes: `effective_excludes()` and `can_defer_excludes()` from Task 2, the harness from Task 3.
- Produces: `public function add_deferred_orderby_tiebreaker( $orderby, $query )`, a `posts_orderby` callback that is public only because it is a hook target. `get_query()` keeps returning a `WP_Query` whose `posts`, `post_count`, `query` and `query_vars` look as they do today.

- [ ] **Step 1: Write the failing test**

Create `tests/phpunit/integration/GridDeferredExcludesTest.php`:

```php
<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Query_Cache;

/**
 * Real WP_Query equivalence for the deferred-exclude path.
 *
 * Contract: a grid that defers must render the same ordered posts as one that does not, and
 * must hand back a query object that still describes the original request. The second half is
 * what keeps Mai Load More and any custom pagination working, since they serialize query_vars.
 */
final class GridDeferredExcludesTest extends MaiIntegrationTestCase {

	/** @var int[] Newest first. */
	private $post_ids = [];

	/** @var int */
	private $term_id;

	public function set_up() {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart. Index 0 is the newest, so post_date DESC returns them in
		// creation order. Distinct dates, so no tie ambiguity in these tests.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );
		}
	}

	private function grid_args( array $overrides = [] ): array {
		return array_merge( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 3,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		], $overrides );
	}

	private function ids( \WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	/** Runs the same grid with deferring switched off, for a like-for-like comparison. */
	private function undeferred( array $args ): \WP_Query {
		add_filter( 'mai_post_grid_defer_excludes', '__return_false' );
		$query = ( new Mai_Grid( $args ) )->get_query();
		remove_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		return $query;
	}

	// ---- The red test for this task ----

	public function test_deferred_grid_does_not_send_the_ids_to_the_database(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );
		$this->assertTrue( is_singular(), 'exclude_current only applies on singular' );

		$query = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		$this->assertStringNotContainsString( 'NOT IN', $query->request );
		$this->assertStringContainsString( 'LIMIT 0, 4', $query->request, 'asked for 3 plus the 1 excluded' );
	}

	// ---- Equivalence ----

	public function test_deferred_result_matches_the_undeferred_result(): void {
		$current = $this->post_ids[0]; // newest, so it would otherwise lead the grid.
		$this->go_to( get_permalink( $current ) );

		$deferred = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request, 'must actually have deferred' );
		$this->assertSame( $this->ids( $this->undeferred( $this->grid_args() ) ), $this->ids( $deferred ) );
		$this->assertCount( 3, $deferred->posts );
		$this->assertNotContains( $current, $this->ids( $deferred ) );
	}

	public function test_short_result_matches(): void {
		$current = $this->post_ids[0];
		$this->go_to( get_permalink( $current ) );

		$args = $this->grid_args( [ 'excludes' => [ 'exclude_current', 'exclude_displayed' ] ] );

		Mai_Grid::$existing_post_ids = [ 'post' => array_slice( $this->post_ids, 1, 7 ) ];
		$deferred = ( new Mai_Grid( $args ) )->get_query();

		Mai_Grid::$existing_post_ids = [ 'post' => array_slice( $this->post_ids, 1, 7 ) ];
		$plain = $this->undeferred( $args );

		Mai_Grid::$existing_post_ids = [];

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request );
		$this->assertSame( $this->ids( $plain ), $this->ids( $deferred ) );
		$this->assertCount( 2, $deferred->posts, 'only two posts survive the exclusions' );
	}

	public function test_empty_result_matches(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$args = $this->grid_args( [ 'excludes' => [ 'exclude_current', 'exclude_displayed' ] ] );

		Mai_Grid::$existing_post_ids = [ 'post' => $this->post_ids ];
		$deferred = ( new Mai_Grid( $args ) )->get_query();
		Mai_Grid::$existing_post_ids = [];

		$this->assertSame( [], $deferred->posts );
		$this->assertSame( 0, $deferred->post_count );
		$this->assertFalse( $deferred->have_posts(), 'the no_results message depends on this' );
	}

	// ---- The restore ----

	public function test_query_vars_are_restored_for_downstream_consumers(): void {
		$current = $this->post_ids[0];
		$this->go_to( get_permalink( $current ) );

		$query = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		// Mai Load More serializes exactly these and rebuilds a WP_Query from them.
		$this->assertSame( 3, $query->query_vars['posts_per_page'], 'padded page size must not leak' );
		$this->assertContains( $current, $query->query_vars['post__not_in'], 'excluded ids must still be described' );
		$this->assertSame( 3, $query->query['posts_per_page'], 'the raw args copy must be restored too' );
	}

	// ---- Guards ----

	public function test_offset_grid_is_left_alone(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$query = ( new Mai_Grid( $this->grid_args( [ 'offset' => 2 ] ) ) )->get_query();

		$this->assertStringContainsString( 'NOT IN', $query->request );
		$this->assertSame( $this->ids( $this->undeferred( $this->grid_args( [ 'offset' => 2 ] ) ) ), $this->ids( $query ) );
	}

	public function test_choice_grid_is_left_alone(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$args  = $this->grid_args( [ 'query_by' => 'id', 'post__in' => array_slice( $this->post_ids, 0, 3 ) ] );
		$query = ( new Mai_Grid( $args ) )->get_query();

		// The exclude block is skipped entirely for Choice grids, so the current post is
		// still shown. That is today's behavior and must not change.
		$this->assertContains( $this->post_ids[0], $this->ids( $query ) );
	}

	// ---- Ties ----

	/** Makes every post share a post_date, so only the ID tiebreaker can settle the order. */
	private function make_everything_tie(): void {
		$same = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

		foreach ( $this->post_ids as $id ) {
			wp_update_post( [ 'ID' => $id, 'post_date' => $same, 'post_date_gmt' => $same ] );
		}
	}

	/**
	 * What the tiebreaker actually buys: the same answer every time.
	 *
	 * Do NOT assert equality with the undeferred path here. The tiebreaker is added only to
	 * the deferred query, so on tied rows the two paths legitimately return different posts.
	 * That is the accepted behavior change, pinned by the next test.
	 */
	public function test_tied_posts_come_back_in_a_stable_order(): void {
		$this->make_everything_tie();
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$first  = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );
		$second = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );

		$this->assertSame( $first, $second );
		$this->assertCount( 3, $first );

		// Highest ids win, because the sort is DESC and the tiebreaker follows it.
		$expected = array_slice( array_reverse( array_diff( $this->post_ids, [ $this->post_ids[0] ] ) ), 0, 3 );
		sort( $expected );
		$actual = $first;
		sort( $actual );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Pins the accepted behavior change so a future reader sees it was deliberate.
	 *
	 * On rows that tie, a deferring grid can show different posts than the same grid with
	 * deferring off. Today's order among tied rows is whatever MySQL felt like and can vary
	 * between page loads; the deferred order is fixed. This test asserts only that both
	 * return a full grid of valid posts, not that they match.
	 */
	public function test_tied_posts_may_differ_from_the_undeferred_path(): void {
		$this->make_everything_tie();
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$deferred = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );
		$plain    = $this->ids( $this->undeferred( $this->grid_args() ) );

		$this->assertCount( 3, $deferred );
		$this->assertCount( 3, $plain );
		$this->assertNotContains( $this->post_ids[0], $deferred );
		$this->assertNotContains( $this->post_ids[0], $plain );
		$this->assertSame( [], array_diff( $deferred, $this->post_ids ) );
	}

	public function test_tiebreaker_follows_the_requested_direction(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$query = ( new Mai_Grid( $this->grid_args( [ 'order' => 'ASC' ] ) ) )->get_query();

		$this->assertStringContainsString( '.ID ASC', $query->request );
	}

	public function test_tiebreaker_is_not_applied_to_an_undeferred_grid(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$this->assertStringNotContainsString( '.ID DESC', $this->undeferred( $this->grid_args() )->request );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test-integration -- --filter GridDeferredExcludesTest`
Expected: FAIL on `test_deferred_grid_does_not_send_the_ids_to_the_database`, the three tie tests, and the `assertStringNotContainsString( 'NOT IN', ... )` line inside each equivalence test. Nothing defers yet, so the SQL still carries `NOT IN (N)` and `LIMIT 0, 3`.

- [ ] **Step 3: Write the tiebreaker callback**

Named `add_deferred_orderby_tiebreaker` rather than `add_orderby_tiebreaker` on purpose. `Mai_Post_Grid_Query_Optimizer` already has a method by that name on the same hook (`lib/classes/class-mai-post-grid-query-optimizer.php:118`), and two same-named `posts_orderby` callbacks would read as a duplicate to anyone skimming.

Add to `lib/classes/class-mai-grid.php`, after `can_defer_excludes()`:

```php
	/**
	 * Appends a post ID tiebreaker to a deferred grid's ORDER BY.
	 *
	 * Public only because it is a hook target. Runs on posts_orderby, scoped by the
	 * mai_grid_tiebreak query var that get_query() sets, so it cannot reach into any other
	 * query that happens to run while this one is being built.
	 *
	 * Why it is needed: the deferred path asks for posts_per_page + N rows. When rows tie on
	 * the sort column, MySQL's LIMIT-aware sort is free to keep different ones for different
	 * LIMITs, and it is free to answer differently between two runs of the same statement.
	 * Measured on larrybrownsports with comment_count ordering, where 134,207 of 145,646 posts
	 * tie at zero: across 120 grid renders, 20 differed between a LIMIT 6 and a LIMIT 13 read
	 * of the same grid, 5 of them returning genuinely different posts rather than a reshuffle.
	 *
	 * What this buys is a stable answer, not the same answer as before. It is applied only to
	 * the deferred query, so on tied rows a deferring grid can legitimately show different
	 * posts than the same grid with deferring off. That is the accepted trade: today's order
	 * among tied rows is arbitrary and can change between page loads, and this makes it fixed.
	 *
	 * @since 2.41.0
	 *
	 * @param string   $orderby The ORDER BY clause.
	 * @param WP_Query $query   The query.
	 *
	 * @return string
	 */
	public function add_deferred_orderby_tiebreaker( $orderby, $query ) {
		global $wpdb;

		if ( empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
			return $orderby;
		}

		// Already deterministic.
		if ( str_contains( $orderby, "{$wpdb->posts}.ID" ) ) {
			return $orderby;
		}

		// Nothing to append to. An empty ORDER BY means no ordering was requested, and
		// imposing one would change the grid rather than settle a tie.
		if ( ! trim( $orderby ) ) {
			return $orderby;
		}

		// Match the direction the last sort key uses, so the tiebreaker reads as a
		// continuation of what the editor asked for rather than a reversal of it.
		$direction = preg_match( '/\b(ASC|DESC)\s*$/i', trim( $orderby ), $matches ) ? strtoupper( $matches[1] ) : 'DESC';

		return $orderby . ", {$wpdb->posts}.ID " . $direction;
	}
```

- [ ] **Step 4: Wire it into get_query()**

Replace the `case 'post':` branch of `get_query()` (currently lines 228-253) with:

```php
			case 'post':
				$this->query_args = $this->get_post_query_args();

				if ( $this->query_args['post_type'] ) {
					// Remove any post_types that no longer exist.
					foreach ( (array) $this->query_args['post_type'] as $index => $post_type ) {
						if ( ! post_type_exists( $post_type ) ) {
							unset( $this->query_args['post_type'][ $index ] );
						};
					}

					// Bail if no post types.
					if ( ! $this->query_args['post_type'] ) {
						return;
					}

					// Decide here, not in get_post_query_args(), because every
					// mai_post_grid_query_args filter has now run and the args are final.
					$effective = $this->effective_excludes( $this->query_args );
					$defer     = $this->can_defer_excludes( $this->query_args, $effective );
					$asked     = $this->query_args;

					if ( $defer ) {
						// Keep the per-view ids out of the SQL so every page sharing this
						// grid's filters shares one cache entry, and ask for enough extra
						// rows that the grid still fills once they are dropped.
						$this->query_args['post__not_in']      = array_values( array_diff( $asked['post__not_in'], $effective ) );
						$this->query_args['posts_per_page']    = $asked['posts_per_page'] + count( $effective );
						$this->query_args['mai_grid_tiebreak'] = true;

						add_filter( 'posts_orderby', [ $this, 'add_deferred_orderby_tiebreaker' ], 99, 2 );
					}

					$query = new WP_Query( $this->query_args );

					if ( $defer ) {
						remove_filter( 'posts_orderby', [ $this, 'add_deferred_orderby_tiebreaker' ], 99 );

						// Apply the excludes now. The result cache has already stored the
						// unfiltered superset during the_posts, which is what makes the entry
						// shareable, so this has to happen after the constructor returns.
						$kept = array_values(
							array_filter(
								$query->posts,
								static function ( $post ) use ( $effective ) {
									// fields => 'ids' gives ints and id=>parent gives stdClass,
									// so do not assume a WP_Post.
									$id = is_object( $post ) ? (int) $post->ID : (int) $post;

									return ! in_array( $id, $effective, true );
								}
							)
						);

						// Anything a the_posts filter added on top of the LIMIT is kept, so a
						// plugin that pins a post into grids is not silently truncated.
						$injected = max( 0, count( $query->posts ) - $this->query_args['posts_per_page'] );

						$query->posts      = array_slice( $kept, 0, $asked['posts_per_page'] + $injected );
						$query->post_count = count( $query->posts );

						// Put the query back the way it was asked for, before anything reads
						// it. Mai Load More and any custom pagination serialize these and
						// re-run them later: a padded posts_per_page would make them stride
						// past posts, and a missing post__not_in would drop the exclusions.
						$query->query_vars['posts_per_page'] = $asked['posts_per_page'];
						$query->query_vars['post__not_in']   = $asked['post__not_in'];
						$query->query['posts_per_page']      = $asked['posts_per_page'];
						$query->query['post__not_in']        = $asked['post__not_in'];

						unset( $query->query_vars['mai_grid_tiebreak'], $query->query['mai_grid_tiebreak'] );

						$this->query_args = $asked;

						// Core left $query->post pointing at the unfiltered first post, which
						// with exclude_current is very often the post being excluded.
						$query->rewind_posts();

						if ( ! $query->post_count ) {
							$query->post = null;
						}
					}

					// Cache featured images. After the filter, so only visible posts prime.
					if ( in_array( 'image', $this->args['show'] ) ) {
						update_post_thumbnail_cache( $query );
					}

					wp_reset_postdata();
				}
				break;
```

- [ ] **Step 5: Run the integration tests**

Run: `composer test-integration -- --filter GridDeferredExcludesTest`
Expected: PASS, 11 tests (10 in this task plus the tie pair).

- [ ] **Step 6: Run both suites**

Run: `composer test`
Expected: PASS, no new failures.

- [ ] **Step 7: Lint**

Run: `composer phpcs`
Expected: no new errors in `lib/classes/class-mai-grid.php`.

- [ ] **Step 8: Commit**

```bash
git add lib/classes/class-mai-grid.php tests/phpunit/integration/GridDeferredExcludesTest.php
git commit -m "feat(grid): keep per-view exclude ids out of the grid query

exclude_current and exclude_displayed put a different post id into every query,
which gave the result cache one entry per page view and effectively no hits. The
grid now asks for the extra rows instead, drops those posts while rendering, and
restores the query object so anything reading it afterwards sees the original
request. A post id tiebreaker keeps the padded and unpadded queries from
disagreeing about rows that tie on the sort column."
```

---

### Task 5: Prove the cache key actually collapses

Nothing so far proves the point of the exercise: that two different articles in the same category now produce one cache entry.

**Files:**
- Test: `tests/phpunit/integration/GridDeferredExcludesTest.php` (extend)

**Interfaces:**
- Consumes: `Mai_Query_Cache::cache_key( array $query_vars, string $sql ): string`, everything from Task 4.
- Produces: nothing.

- [ ] **Step 1: Write the failing test**

Append inside the class:

```php
	/**
	 * Captures the key exactly as Mai_Query_Cache::pre_query() computes it.
	 *
	 * This must run DURING the query. get_query() restores query_vars once the constructor
	 * returns, so reading them afterwards shows the original request, complete with the
	 * excluded id, and every key would look shattered. posts_pre_query fires with
	 * $query->request already built, and the cache's own callback sits at priority 10.
	 */
	private function key_for( int $current ): string {
		$this->go_to( get_permalink( $current ) );

		$key     = '';
		$capture = static function ( $posts, $query ) use ( &$key ) {
			$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

			return $posts;
		};

		add_filter( 'posts_pre_query', $capture, 9, 2 );
		( new Mai_Grid( $this->grid_args() ) )->get_query();
		remove_filter( 'posts_pre_query', $capture, 9 );

		$this->assertNotSame( '', $key, 'the capture filter did not fire' );

		return $key;
	}

	/** The reason the whole change exists. */
	public function test_two_posts_in_the_same_category_share_a_cache_key(): void {
		$this->assertSame(
			$this->key_for( $this->post_ids[0] ),
			$this->key_for( $this->post_ids[5] ),
			'the excluded id must not reach the key'
		);
	}

	public function test_an_undeferred_grid_still_shatters(): void {
		add_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		$a = $this->key_for( $this->post_ids[0] );
		$b = $this->key_for( $this->post_ids[5] );

		remove_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		$this->assertNotSame( $a, $b, 'this is the behavior being fixed' );
	}
```

- [ ] **Step 2: Run the tests**

Run: `composer test-integration -- --filter GridDeferredExcludesTest`
Expected: PASS, 14 tests.

If `test_two_posts_in_the_same_category_share_a_cache_key` fails, the excluded id is still reaching either `$query->request` or the hashed vars. Dump both and find which. Do not weaken the assertion.

- [ ] **Step 3: Commit**

```bash
git add tests/phpunit/integration/GridDeferredExcludesTest.php
git commit -m "test(grid): lock the cache key collapse for deferred excludes"
```

---

### Task 6: Verify Mai Load More page 2 for real

The largest remaining unverified risk. A reviewer traced the AJAX path and concluded the restore is sufficient even when deferring fires on a load-more grid, because page 2 recomputes its offset from the restored `posts_per_page` and re-runs `post__not_in` in its own SQL. That is reasoning, not observation. This task replaces it.

Note the guard means a load-more grid should not defer at all: mai-load-more sets `no_found_rows` to false, and `can_defer_excludes()` refuses that. Both halves need checking, and neither may be checked by forcing the filter on, because the filter is opt-out only and cannot force it.

**Files:**
- Create: `.agents/grid-deferred-excludes-verification.md`
- If a bug is found: fix in `lib/classes/class-mai-grid.php` plus a regression test in `tests/phpunit/integration/GridDeferredExcludesTest.php`.

**Interfaces:**
- Consumes: everything from Task 4.
- Produces: a written record of what was observed.

- [ ] **Step 1: Install Mai Load More on a mirror**

The user has approved installing it anywhere. Use `~/Herd/totalprosports`, whose `mai-engine` is already a symlink to this repo, so the branch under test is live there.

```bash
ln -s ~/Plugins/mai-load-more ~/Herd/totalprosports/wp-content/plugins/mai-load-more
cd ~/Herd/totalprosports && wp --path=. plugin activate mai-load-more
```

- [ ] **Step 2: Pick a category with enough posts**

```bash
cd ~/Herd/totalprosports && nice -n 19 wp --path=. db query \
  "SELECT tt.term_id, COUNT(*) c FROM wp_term_taxonomy tt
   JOIN wp_term_relationships tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
   JOIN wp_posts p ON p.ID=tr.object_id AND p.post_type='post' AND p.post_status='publish'
   WHERE tt.taxonomy='category' GROUP BY tt.term_taxonomy_id ORDER BY c DESC LIMIT 3;"
```

- [ ] **Step 3: Create the test page**

Two `acf/mai-post-grid` blocks on one page, both querying the category from Step 2, both showing 6, both excluding displayed and current. The **second** carries the `mai-grid-load-more` class; the first does not. That shape exercises both halves at once: the first should defer, the second should not.

Write the term ID from Step 2 into the script, then run it.

```bash
cat > /tmp/lm-page.php <<'PHP'
<?php
$term = 0; // <- term_id from Step 2

$grid = static function ( array $attrs ) use ( $term ) {
	$data = ( [
		'show'                   => [ 'image', 'title' ],
		'_show'                  => 'mai_grid_block_show',
		'columns'                => '3',
		'_columns'               => 'mai_grid_block_columns',
		'post_type'              => [ 'post' ],
		'_post_type'             => 'mai_grid_block_post_type',
		'query_by'               => 'tax_meta',
		'_query_by'              => 'mai_grid_block_query_by',
		'taxonomies_0_taxonomy'  => 'category',
		'_taxonomies_0_taxonomy' => 'mai_grid_block_tax_taxonomy',
		'taxonomies_0_terms'     => [ $term ],
		'_taxonomies_0_terms'    => 'mai_grid_block_tax_terms',
		'taxonomies_0_operator'  => 'IN',
		'_taxonomies_0_operator' => 'mai_grid_block_tax_operator',
		'taxonomies'             => 1,
		'_taxonomies'            => 'mai_grid_block_post_taxonomies',
		'posts_per_page'         => '6',
		'_posts_per_page'        => 'mai_grid_block_posts_per_page',
		'offset'                 => '0',
		'_offset'                => 'mai_grid_block_posts_offset',
		'orderby'                => 'date',
		'_orderby'               => 'mai_grid_block_posts_orderby',
		'order'                  => 'DESC',
		'_order'                 => 'mai_grid_block_posts_order',
		'excludes'               => [ 'exclude_displayed', 'exclude_current' ],
		'_excludes'              => 'mai_grid_block_posts_exclude',
	] );

	// className is a TOP-LEVEL block attribute, not part of data. Mai reads it at
	// lib/blocks/mai-grid/blocks.php:220 as $attributes['className']. Putting it inside data
	// renders a perfectly good grid that Mai Load More then ignores, which looks like the
	// guard working when nothing was ever tested. Verified by rendering both shapes.
	$block = array_merge( [
		'name' => 'acf/mai-post-grid',
		'data' => $data,
		'mode' => 'preview',
	], $attrs );

	return '<!-- wp:acf/mai-post-grid ' . wp_json_encode( $block ) . ' /-->';
};

$content = $grid( [] ) . "\n\n" . $grid( [ 'className' => 'mai-grid-load-more' ] );

$id = wp_insert_post( [
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Deferred excludes load-more check',
	'post_content' => $content,
] );

echo "page id: {$id}\n" . get_permalink( $id ) . "\n";
PHP
cd ~/Herd/totalprosports && wp --path=. eval-file /tmp/lm-page.php
```

Then confirm it renders, **over HTTP, not through WP-CLI**:

```bash
BODY=$(curl -sk --max-time 90 "<url printed above>")
echo "grids: $(echo "$BODY" | grep -o 'class="[^"]*mai-grid' | wc -l)"
echo "load-more class: $(echo "$BODY" | grep -c 'mai-grid-load-more')"
```

Expected: `grids: 2` and `load-more class: 1`.

Do not try `do_blocks()` under `wp eval`. ACF blocks do not render there: a real, known-good grid block pulled straight out of the database also produces zero bytes that way, so a blank result proves nothing about the markup. Measured.

If `grids` is 0, the term ID is wrong. If `load-more class` is 0, `className` ended up in the wrong place in the block JSON.

- [ ] **Step 4: Confirm each grid did the right thing**

Add a temporary mu-plugin that writes each grid's SQL to a file you can read without hunting for the PHP error log:

```bash
cat > ~/Herd/totalprosports/wp-content/mu-plugins/grid-probe.php <<'PHP'
<?php
add_filter( 'posts_request', function ( $sql, $query ) {
	if ( ! empty( $query->query_vars['mai_cache'] ) ) {
		file_put_contents(
			WP_CONTENT_DIR . '/grid-probe.log',
			preg_replace( '/\s+/', ' ', $sql ) . "\n\n",
			FILE_APPEND
		);
	}
	return $sql;
}, 10, 2 );
PHP
rm -f ~/Herd/totalprosports/wp-content/grid-probe.log
```

Reload the page, then read it:

```bash
cat ~/Herd/totalprosports/wp-content/grid-probe.log
```

Expected: the first grid's SQL has **no** `NOT IN`, a `LIMIT` of 7 (6 plus the current post), and `, wp_posts.ID DESC` on the end. The second grid's SQL **does** have `NOT IN`, a `LIMIT` of 6, and no tiebreaker, because `no_found_rows` was turned off for it.

If the second grid deferred, `can_defer_excludes()` is wrong and Task 2 needs fixing before going further.

Note the grid cache may serve a hit and skip the SQL entirely. If the log is empty or short, flush first: `wp --path=. cache flush` (or `wp --path=. mai flush`), then reload.

- [ ] **Step 5: Click Load More and check page 2**

Open the page in a browser, note the six titles in the load-more grid, click Load More, note the next six.

Expected: twelve distinct posts in date order, no repeats, no gaps. A gap of six between batches means a padded `posts_per_page` leaked into the serialized query. A repeat of the current post means the exclusions were lost.

Use the `cmux-browser` skill if working in cmux, otherwise `browser-testing-with-devtools`.

- [ ] **Step 6: Check the first grid's posts are absent from the second**

The load-more grid has `exclude_displayed`, so none of the first grid's six posts should appear in either batch. Confirm by title.

This is the cross-grid case that only works because the first grid's rendered IDs land in the static before the second grid builds its query.

- [ ] **Step 7: Clean up and record what happened**

```bash
rm ~/Herd/totalprosports/wp-content/mu-plugins/grid-probe.php ~/Herd/totalprosports/wp-content/grid-probe.log
cd ~/Herd/totalprosports && wp --path=. post delete <page id from Step 3> --force
```

Create `.agents/grid-deferred-excludes-verification.md` recording the category used, both grids' logged SQL, the two batches of titles, and whether anything was repeated or missing. Write what was observed, not what was expected. This is the only record that the load-more path was exercised at all.

```bash
git add .agents/grid-deferred-excludes-verification.md
git commit -m "docs(grid): record Mai Load More page-2 verification for deferred excludes"
```

---

### Task 7: Measure the collapse, then document

Numbers for the changelog, and for deciding later whether invalidation ever needs narrowing.

**Files:**
- Create: `bin/grid-key-collapse.php`
- Modify: `CHANGES.md`

**Interfaces:**
- Consumes: everything from Task 4.
- Produces: a measured key count per site, with deferring on and off.

- [ ] **Step 1: Write the measurement script**

The first draft of this script measured nothing, because `exclude_current` needs a singular context and WP-CLI has none, and because it hashed `$query->query` instead of `$query->query_vars`. Both are fixed here.

Create `bin/grid-key-collapse.php`:

```php
<?php
/**
 * Counts how many distinct grid cache keys a run of real articles produces, with deferring on
 * and off. Run with:
 *   wp --path=<site> eval-file bin/grid-key-collapse.php
 *
 * Lab tool. It says nothing about what any other site has installed.
 */

$sample_size = 300;

$ids = $GLOBALS['wpdb']->get_col(
	"SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type='post' AND post_status='publish' ORDER BY post_date DESC"
);

$step   = max( 1, (int) floor( count( $ids ) / $sample_size ) );
$sample = [];

for ( $i = 0; $i < count( $ids ) && count( $sample ) < $sample_size; $i += $step ) {
	$sample[] = (int) $ids[ $i ];
}

// Capture the key the same way Mai_Query_Cache::pre_query() computes it: during the query,
// from query_vars, before get_query() restores them.
$key     = '';
$capture = static function ( $posts, $query ) use ( &$key ) {
	$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

	return $posts;
};

add_filter( 'posts_pre_query', $capture, 9, 2 );

foreach ( [ 'deferred' => null, 'today' => '__return_false' ] as $label => $off ) {
	if ( $off ) {
		add_filter( 'mai_post_grid_defer_excludes', $off );
	}

	$keys = [];

	foreach ( $sample as $id ) {
		$cats = wp_get_post_terms( $id, 'category', [ 'fields' => 'ids' ] );

		if ( is_wp_error( $cats ) || ! $cats ) {
			continue;
		}

		// exclude_current is gated on is_singular(), which is false under WP-CLI unless the
		// main query is set up. Without this the two runs are identical and measure nothing.
		//
		// Guard the have_posts(). next_post() indexes $this->posts unconditionally, so on an
		// empty result it warns and leaves $GLOBALS['post'] pointing at the PREVIOUS article,
		// which would silently measure this iteration against the wrong exclusion.
		$previous            = $GLOBALS['wp_query'];
		$GLOBALS['wp_query'] = new WP_Query( [ 'p' => $id, 'post_type' => 'post' ] );

		if ( ! $GLOBALS['wp_query']->have_posts() ) {
			$GLOBALS['wp_query'] = $previous;
			continue;
		}

		$GLOBALS['wp_query']->the_post();

		$key = '';

		( new Mai_Grid( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 6,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [ [ 'taxonomy' => 'category', 'terms' => $cats, 'current' => false, 'operator' => 'IN' ] ],
		] ) )->get_query();

		wp_reset_postdata();

		$GLOBALS['wp_query'] = $previous;

		if ( '' !== $key ) {
			$keys[] = $key;
		}
	}

	if ( $off ) {
		remove_filter( 'mai_post_grid_defer_excludes', $off );
	}

	printf( "%-9s: %d articles -> %d distinct cache keys\n", $label, count( $keys ), count( array_unique( $keys ) ) );
}

remove_filter( 'posts_pre_query', $capture, 9 );
```

- [ ] **Step 2: Sanity check the script before trusting it**

Run it on larrybrownsports first and check the `today` line, not the `deferred` one.

```bash
cd ~/Herd/larrybrownsports && nice -n 19 wp --path=. eval-file ~/Plugins/mai-engine/bin/grid-key-collapse.php 2>&1 | grep -vE "^(Deprecated|Notice|Warning)"
```

Expected: `today` produces close to one key per article, around 300. If it does not, `is_singular()` is still false and the script is measuring nothing; fix that before reading the `deferred` number.

- [ ] **Step 3: Run it on all three mirrors**

```bash
for s in larrybrownsports totalprosports tvnewscheck; do
  echo "=== $s ==="
  cd ~/Herd/$s && nice -n 19 wp --path=. eval-file ~/Plugins/mai-engine/bin/grid-key-collapse.php 2>&1 | grep -vE "^(Deprecated|Notice|Warning)"
done
```

Expected: a large drop on larrybrownsports and totalprosports. **tvnewscheck is expected to show no collapse at all**, because Mai ElasticPress sets `ep_integrate` on every grid and `can_defer_excludes()` refuses those. That is the correct result there, not a failure. See `.agents/elasticpress-grid-cache.md`.

- [ ] **Step 4: Add the changelog entry**

Add to the unreleased section at the top of `CHANGES.md`, using the numbers from Step 3:

```
* Changed: Post grids using "Exclude current" or "Exclude displayed" no longer put those post IDs into the query. The grid asks for a few extra entries and drops them while rendering, so pages sharing a grid's settings now share a cached result instead of each building their own. Unchanged: grids with an offset, grids that count total results (Mai Load More), grids set to "Choice", term grids, and grids handled by ElasticPress or FacetWP. Filterable with `mai_post_grid_defer_excludes`.
* Changed: Post grids that share a sort value across many entries, such as views, trending or comment count, now settle the order by entry ID instead of leaving it to the database. Those grids previously could return a different set of equally ranked entries on each page load.
```

- [ ] **Step 5: Verify the branch is still deployable**

```bash
composer dump-autoload --no-dev
git status --porcelain vendor/
```

Same check as Preflight, repeated because anything run since could have regenerated the autoloader. Expect at most `vendor/composer/installed.php`. If any `autoload_*.php` shows as modified, do not commit it as part of this work; find out what regenerated it.

- [ ] **Step 6: Commit**

```bash
git add bin/grid-key-collapse.php CHANGES.md
git commit -m "feat(grid): add key-collapse measurement script and changelog entry"
```

---

## After the plan

Do not push, do not open a PR, do not tag. Ask first.

1. Code review before this merges. The branch deploys as-is to client sites.
2. **Decide how to roll this out, because deploying it empties the grid cache.** Every deferring grid's key changes, since both the padded `LIMIT` and the appended tiebreaker are hashed. So the moment this ships, every grid on every site goes cold at once and recomputes. Single-flight blunts it, but only on hosts with a persistent object cache (`lib/classes/class-mai-query-cache.php:281-283`), and larrybrownsports is exactly the site where a stampede hurts. Options worth weighing: deploy off-peak, or ship with `mai_post_grid_defer_excludes` filtered off and turn it on per site. This is an operational call, not a code change, and it is not decided.
3. Soak on one site before wider rollout.
3. Once soaked, revisit deleting `Mai_Post_Grid_Query_Optimizer`, its four test files, its two bin scripts, and the now-dead `mai_post_grid_tt_ids` check at `lib/classes/class-mai-query-cache.php:169`. That check also carries a stale comment describing a `post__in` fast path the optimizer does not use.
4. Still open, deliberately: ElasticPress sites get nothing from this. See `.agents/elasticpress-grid-cache.md`.
