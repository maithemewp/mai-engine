# Grid Taxonomy Query Rewrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mai post grid queries filtered by taxonomy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, wherever Mai can prove it returns the same posts, on by default, falling back to today's statement everywhere else.

**Architecture:** A new `Mai_Post_Grid_Query_Optimizer` (replacing the old class of that name) marks Mai's grid queries and ID-only copies, records each SQL part at the first and last plugin filter, and prepares a swap at `posts_request`. Its `$wpdb` `query` callback swaps the exact statement at the last moment, after rebuilding WordPress's tax SQL with WordPress's own `WP_Tax_Query` code (`Mai_Post_Grid_Query_Optimizer_Sql`) and checking the database (`Mai_Post_Grid_Query_Optimizer_Database`). Failures are pinned by statement count, swapped text and owner, repaired by resending the original statement, and turn the swap off for 24 hours.

**Tech Stack:** PHP 8.1+, WordPress 6.4+ (`WP_Query`, `WP_Tax_Query`, `wpdb`), PHPUnit 10.5 (unit suite with brain/monkey, integration suite with wp-phpunit), MySQL 9.7 locally, Docker for MySQL 8.0, 8.4 and MariaDB 10.6, 10.11, 11.4, 11.8.

**Spec:** `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`. Read it before any task. "Spec §3" means its section 3.

## Global Constraints

- PHP floor 8.1 (`Requires PHP: 8.1`, composer `^8.1`): no typed class constants, no `readonly` classes. `readonly` properties are fine.
- WordPress floor 6.4. On 6.4 and 6.5 only the grid's split statement steps aside (spec "Which grids").
- New PHP files start like `lib/classes/class-mai-query-cache-queue.php`: `<?php`, `declare(strict_types=1);`, the file docblock with `@package BizBudding\MaiEngine`, then `defined( 'ABSPATH' ) || die;`. Classes are global `Mai_*` names loaded from `lib/classes/class-{lowercased-dashed-name}.php` by `lib/functions/autoload.php` (the existing convention, so no namespace). Integration test helpers live in `tests/phpunit/integration/` under `BizBudding\MaiEngine\Tests\Integration` (PSR-4, `tests/composer.json`).
- Hook callbacks take `mixed` and return anything that is not the expected type unchanged.
- On `WP_Query`, assign whole property values. `$query->prop['k'] = $v` on an unset property is silently dropped (`class-wp-query.php:4117`).
- Exact values: filter `mai_post_grid_optimize_query` (default `true`); transient `mai_post_grid_optimize_off` for `DAY_IN_SECONDS`; slow limit `1.0` second; MySQL minimum `8.0.16`; MySQL hint `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ` right after `SELECT ` inside each `EXISTS`; allowed `$wpdb` classes exactly `wpdb` and `QM_DB`; version strings containing `sqlite`, `vitess` or `tidb` (case-insensitive) are not MySQL; tiebreaker always `, {posts}.ID DESC`.
  - Superseded on 2026-10-05: see spec "Ties" for the tiebreaker rule.
- Tests that look for Mai's swap match `EXISTS ( SELECT ` (core's own `EXISTS` operator writes `EXISTS (` too).
- No em-dashes in code comments, `CHANGES.md` or commit messages. Commit bodies are one line per paragraph and end with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- The committed `vendor/composer` autoloader must stay no-dev; `php vendor/bin/deployable-guard check` passes before every commit (the pre-commit hook runs it).
- Live sites are read-only. Never push, tag or release.
- Local site work follows `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: back up `wp-config.php`, restore with `cp`, check with `cmp`, never `wp cache flush` on eurweb or larrybrownsports, delete every temporary mu-plugin.

## Review Focus

1. **A grid on a page where list-category-posts already ran** (`remove_all_filters('posts_orderby')` stripped the tiebreaker): today's posts, unswapped. Test: Task 3 `test_steps_aside` "tiebreaker removed".
2. **A grid whose saved category was deleted** (`0 = 1`, no join), or a `NOT IN` filter whose terms were all deleted (no condition at all): the page renders with no warning or `ValueError`. Tests: Task 2 `test_rebuild_matches_core` and `test_rebuild_declines`, Task 3 `test_steps_aside` "deleted term".
3. **A logged-in editor viewing a grid** (publish and private): same posts as with the optimizer off. Test: Task 6 "editor".
4. **Two grids on one page, the first one's swapped statement fails:** both show the right posts, the second is not swapped. Test: Task 5 `test_a_failed_swap_turns_it_off_for_the_page`.
5. **A term whose term ID differs from its term_taxonomy ID,** common on old sites and never on a fresh install: same posts. Test: the shared fixture (Task 2) skews every test term, and Tasks 2, 3 and 6 use it.

---

### Task 1: Database check

**Files:**
- Create: `lib/classes/class-mai-post-grid-query-optimizer-database.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerDatabaseTest.php`

**Interfaces:**
- Produces, on `final class Mai_Post_Grid_Query_Optimizer_Database`:
  - `public const MYSQL_MIN = '8.0.16';`
  - `public static string $mariadb_min = '';` (empty means MariaDB is off; Task 8 sets the shipped default; tests may override it)
  - `public static function engine( mixed $server_info ): ?array` returning `[ 'engine' => 'mysql'|'mariadb', 'version' => 'X.Y.Z' ]`, or `null` when the value is not a non-empty string, contains `sqlite`, `vitess` or `tidb`, or has no version.
  - `public static function allows( mixed $server_info, ?string $mariadb_min = null ): bool` (`null` means `self::$mariadb_min`)
  - `public static function hint( mixed $server_info ): bool` (true for MySQL only)
  - `public static function layer_allows( object $db ): bool` (`get_class( $db )` is exactly `wpdb` or `QM_DB`)
  - `public static function server_info( object $db ): string` (calls `$db->db_server_info()` inside `try`/`catch ( Throwable )`, returns `''` on a throw, `false` or anything not a string)

- [ ] **Step 1: Write the failing test** `PostGridQueryOptimizerDatabaseTest` extending `BizBudding\MaiEngine\Tests\TestCase`, with a provider `servers()` and `test_allows( mixed $info, bool $expected )` asserting `allows( $info, '10.11.0' )`:
  - `'8.0.15'` false, `'8.0.16'` true, `'8.0.46-0ubuntu0.22.04.4'` true, `'8.4.11-11'` true, `'9.7.1'` true, `'5.7.44'` false
  - `'8.0.38-mysql-on-sqlite-3.0.2'` false, `'8.0.40-Vitess'` false, `'8.0.11-TiDB-v7.5.0'` false, `'8.0.mysql_aurora.3.04.0'` false
  - `'5.5.5-10.11.6-MariaDB'` true, `'10.11.6-MariaDB-log'` true, `'10.6.18-MariaDB'` false, `'11.8.2-MariaDB'` true
  - `false`, `''`, `null` false

  Plus: `test_mariadb_is_off_by_default` (`allows( '11.8.2-MariaDB' )` false while `$mariadb_min` is `''`, restored in `tearDown()`); `test_engine` (`5.5.5-10.11.6-MariaDB` reads `10.11.6`); `test_hint` (MySQL true, MariaDB false); `test_layer_allows` with stand-ins declared globally, since unit tests run in a namespace: `if ( ! class_exists( 'wpdb', false ) ) { eval( 'class wpdb {}' ); }` and the same for `eval( 'class QM_DB extends wpdb {}' )`, plus `new \stdClass()` false; `test_server_info` with stubs whose `db_server_info()` returns `'8.0.46'`, `false`, and throws `TypeError`, expecting `'8.0.46'`, `''`, `''`.
- [ ] **Step 2: Run it to see it fail.** Run: `composer test-unit -- --filter PostGridQueryOptimizerDatabaseTest`. Expected: FAIL, class not found.
- [ ] **Step 3: Implement the class.** MariaDB when the string contains `mariadb` (case-insensitive): drop a leading `5.5.5-`, take the first `\d+\.\d+\.\d+`. MySQL otherwise: the string must start with `\d+\.\d+\.\d+`. `allows()` uses `version_compare()`; an empty MariaDB minimum means false.
- [ ] **Step 4: Run it to see it pass.** Same command. Expected: PASS.
- [ ] **Step 5: Commit** the class and the test: "Grid optimizer: which databases can take the faster query".

### Task 2: Rebuild and rewrite WordPress's tax SQL, and the shared fixture

**Files:**
- Create: `lib/classes/class-mai-post-grid-query-optimizer-sql.php`
- Create: `tests/phpunit/integration/PostGridQueryOptimizerFixture.php` (a trait used by Tasks 2, 3, 5 and 6)
- Test: `tests/phpunit/integration/PostGridQueryOptimizerSqlTest.php`

**Interfaces:**
- Produces, all `public static` on `final class Mai_Post_Grid_Query_Optimizer_Sql`:
  - `const SORT_COLUMNS = [ 'post_date', 'post_modified', 'post_title', 'post_name', 'menu_order', 'comment_count', 'post_author', 'post_parent', 'post_type', 'ID' ];` (Task 8 may remove some)
  - `rebuild( WP_Tax_Query $tax_query, string $posts_table ): ?array` returning `[ 'join' => string, 'where' => string, 'relation' => 'AND'|'OR', 'pieces' => list<array{where: string, alias: ?string}> ]`, or `null` when not covered
  - `condition( array $rebuilt, string $posts_table, string $term_table, bool $hint ): string`
  - `swap( string $statement, array $rebuilt, string $condition, string $posts_table ): ?string`
  - `split( string $request, string $posts_table ): ?string`
  - `orderby_ok( string $orderby, string $posts_table ): bool`
- Produces, trait `PostGridQueryOptimizerFixture` with `static function create_optimizer_fixture( $factory ): array` returning term and post IDs by name: `big` (category, 40 posts, one a day for the last 40 days, with child category `child` holding 5 of them), `small` (category, 3 posts dated 2015), `tag` (8 posts spread through the big category's range), `custom` (taxonomy `mai_test_tax`, 8 posts), `empty` (category, no posts), `private` (2 private posts in `big`), `newest` (15 posts newer than all others, in no test term), and a page in `big`. Before creating any term it inserts 100 spare rows into `$wpdb->term_taxonomy` with plain `INSERT`s (never `ALTER ... AUTO_INCREMENT`, which commits the test transaction), so every test term's term ID differs from its term_taxonomy ID. It registers `mai_test_tax` for `post` and `page`.

- [ ] **Step 1: Write the fixture trait and the failing tests.** Build the fixture once per class in `wpSetUpBeforeClass()`. Tests:
  - `test_fixture_is_skewed`: no test term's term ID equals any test term's term_taxonomy ID.
  - `test_rebuild_matches_core` with a provider: `big` (children on), `tag`, `custom`, AND `big` + `tag`, AND three `IN` filters, OR `big` + `tag`, AND `IN` + `NOT IN`, AND `IN` + `NOT IN` whose terms are all deleted (no condition from that filter), AND `IN` + `'operator' => 'AND'`, AND `IN` + `'operator' => 'EXISTS'`. Assert `rebuild()` join and where equal `( new WP_Tax_Query( $tq ) )->get_sql( $wpdb->posts, 'ID' )`.
  - `test_rebuild_declines` returns `null` for: a nested query, OR with a `NOT IN` filter, OR with `IN` then a lowercase `in` filter (core gives it a second table), only `NOT IN`, a deleted term (`0 = 1`), an unregistered taxonomy, an empty tax query.
  - `test_condition`: exact strings for one filter with and without the hint; AND with two `IN` filters (the second is `EXISTS ( SELECT 1 FROM {term} AS tt1 WHERE tt1.object_id = {posts}.ID AND tt1.term_taxonomy_id IN (...) )`); AND `IN` + `NOT IN` (the `NOT IN` text unchanged); OR, with WordPress's whole condition, brackets included, inside one `EXISTS`.
  - `test_swap`: replaces the join, the where and `GROUP BY {posts}.ID` once each; returns `null` when the statement holds the join twice, lacks `GROUP BY`, or the rebuilt join or where is empty, without calling `substr_count()` on an empty needle.
  - `test_split`: `"SELECT   {posts}.* FROM..."` becomes `"SELECT   {posts}.ID FROM..."`; anything else returns `null`.
  - `test_orderby_ok`: true for `{p}.post_date DESC, {p}.ID DESC`, `{p}.post_title ASC, {p}.ID DESC`, `{p}.menu_order ASC, {p}.post_date DESC, {p}.ID DESC`, `{p}.ID DESC`; false for `RAND(7), {p}.ID DESC`, `{p}.post_date DESC`, anything naming the term table or `tt1`, `{p}.post_content DESC, {p}.ID DESC`.
  - `test_same_posts` with a provider of every covered shape above plus: 29 terms in one filter, `empty`, an offset of 5, `posts_per_page` 200, and every fixture post given one shared date. Build today's statement with a `WP_Query` (`fields => ids`, `no_found_rows => true`, `posts_per_page => 10`, `orderby => [ 'date' => 'DESC', 'ID' => 'DESC' ]`, `cache_results => false`) and the same with `nopaging => true` for the no-`LIMIT` form; swap each; `$wpdb->get_col()` all four. Assert today's and swapped match with and without the `LIMIT`, and that today's differs from the same query with no tax query (except the `empty` and 200 cases, which assert counts instead).
  - `test_or_without_brackets_would_be_wrong`: the OR swap with WordPress's brackets stripped returns different posts than today's statement; the real swap returns the same.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter PostGridQueryOptimizerSqlTest`. Expected: FAIL, class not found.
- [ ] **Step 3: Implement `rebuild()`.** One fresh `new WP_Tax_Query( $tax_query->queries )`, set its public `primary_table` and `primary_id_column` (`'ID'`), and loop the copied queries by reference, calling the public `get_sql_for_clause( $clause, $queries )` on each first-order clause, exactly as `get_sql_for_query()` does at depth 0 (`class-wp-tax-query.php:302-370`). Mirror the protected `is_first_order_clause()` (`:229`); a nested clause returns `null`. Per clause: zero where entries adds nothing; one is used; more than one returns `null`; `0 = 1` returns `null`. Return `null` when no clause produced a join, or the relation is OR and any operator, compared with `strtoupper()`, is not `IN` or the joins are not exactly one. Reassemble with core's exact separators: `implode( ' ', array_unique( array_filter( $joins ) ) )` and `' AND ( ' . "\n  " . implode( " \n  {$relation} \n  ", $chunks ) . "\n)"`. A piece's `alias` is `$clause['alias']` after the call, or `null` for filters that use their own subquery.
- [ ] **Step 4: Implement `condition()`, `swap()`, `split()` and `orderby_ok()`.** AND: `' AND ' . implode( ' AND ', $parts )`, an aliased piece becoming `EXISTS ( SELECT {hint}1 FROM {term}[ AS {alias}] WHERE {alias}.object_id = {posts}.ID AND {piece where} )` (no `AS` when the alias is the table name), other pieces as written. OR: `' AND EXISTS ( SELECT {hint}1 FROM {term} WHERE {term}.object_id = {posts}.ID AND ' . substr( $where, 5 ) . ' )'`. `{hint}` is `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ` or `''`. `split()` requires the exact prefix `"SELECT   {posts}.*"` (three spaces: core's template with empty found-rows and distinct). `orderby_ok()` is one anchored regex over `SORT_COLUMNS` with optional `ASC|DESC`, ending in `{posts}.ID (ASC|DESC)`.
- [ ] **Step 5: Run the tests to see them pass.** Same command. Expected: PASS.
- [ ] **Step 6: Commit**: "Grid optimizer: rebuild WordPress's tax SQL and write the EXISTS form".

### Task 3: The optimizer: marks, looks, prepared swaps and the swap (replaces the old optimizer)

**Files:**
- Replace: `lib/classes/class-mai-post-grid-query-optimizer.php` (same class name, new class)
- Modify: `lib/functions/performance.php:293-303` (`add_action( 'init', 'mai_register_post_grid_query_optimizer', 9 )`; body `Mai_Post_Grid_Query_Optimizer::instance()->register();`)
- Modify: `tests/phpunit/integration/plugin-loader.php` (`add_action( 'init', static fn() => Mai_Post_Grid_Query_Optimizer::instance()->register(), 9 );` mirroring performance.php, and `Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min = (string) ( getenv( 'WP_TESTS_MARIADB_MIN' ) ?: Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min );`). Tests never call `register()`.
- Modify: `tests/phpunit/integration/MaiIntegrationTestCase.php` (`set_up()` and `tear_down()` call `Mai_Post_Grid_Query_Optimizer::instance()->reset()`)
- Modify: `lib/classes/class-mai-query-cache.php` (remove the `mai_post_grid_tt_ids` check in `is_cacheable()`, around `:490-493`), `tests/phpunit/unit/MaiQueryCacheabilityTest.php` (remove the two tests at `:28` and `:32`), `lib/classes/class-mai-grid.php:927-928` (drop "the optimizer's fast path" from the comment), `.agents/elasticpress-grid-cache.md:58` (reword: the new optimizer never sees ElasticPress grids, because ElasticPress answers at `posts_pre_query` and no statement is sent)
- Delete: `tests/phpunit/unit/PostGridQueryOptimizer{Args,Classify,Orderby,Register,Where}Test.php`, `bin/grid-query-equivalence.php`, `bin/grid-equivalence-matrix.php`
- Test: `tests/phpunit/integration/PostGridQueryOptimizerTest.php`

**Interfaces:**
- Consumes: Task 1 (`allows()`, `hint()`, `layer_allows()`, `server_info()`), Task 2 (all five methods, the fixture).
- Produces, on `final class Mai_Post_Grid_Query_Optimizer`:
  - `public const FILTER = 'mai_post_grid_optimize_query'; public const TRANSIENT = 'mai_post_grid_optimize_off';`
  - `public static float $slow = 1.0;` (tests lower it)
  - `public static function instance(): self`
  - `public function register(): void` (runs once; a second call does nothing)
  - `public function mark( WP_Query $query, string $role ): void` (`'grid'` or `'copy'`; sets `$query->mai_optimize = $role`)
  - `public function drop( WP_Query $query ): void` (removes that query's prepared swaps)
  - `public function outcome( WP_Query $query ): ?array` returning `null` when nothing was swapped for it, else `[ 'status' => 'ok'|'failed', 'seconds' => float, 'form' => 'copy'|'split'|'full' ]`, and forgetting the record
  - `public function turn_off( string $why, string $detail = '' ): void`
  - `public function set_logger( Closure $logger ): void` (default: `error_log( 'Mai Engine: ' . $message )` when `WP_DEBUG_LOG`)
  - `public function reset(): void` (clears per-request state: flags, memos, prepared swaps, records, and puts the default logger back; never the registered flag)
  - Hook callbacks, `public`, `mixed` in and out: `first_look( string $part, mixed $value, mixed $query )` (closures on `posts_where`, `posts_join`, `posts_groupby`, `posts_distinct`, `posts_fields` at `PHP_INT_MIN`), `last_search()` (`posts_search`, `PHP_INT_MAX`), `last_look()` (`posts_clauses_request`, `PHP_INT_MAX`), `first_request()` (`posts_request`, `PHP_INT_MIN`), `prepare()` (`posts_request`, `PHP_INT_MAX`), `swap( mixed $sql )` (`query`, `PHP_INT_MAX`)
  - Query properties, each assigned whole: `mai_optimize`, `mai_optimize_first` (the five parts), `mai_optimize_search`, `mai_optimize_ready` (bool), `mai_optimize_request` (string)

- [ ] **Step 1: Write the failing tests** in `PostGridQueryOptimizerTest`, using the fixture. Helper `run_marked( array $args, string $role = 'grid' ): array` calls `wp_cache_flush()` and `reset()` first, marks a new `WP_Query` before `query()`, and returns the query, its post IDs and every statement sent, captured by a `query` callback at `PHP_INT_MAX` added in the test (after the harness registered Mai's, so it sees the swapped text). Base args: `post_type => post`, `big`, `posts_per_page => 7`, `no_found_rows => true`, `ignore_sticky_posts => true`, `mai_grid_tiebreak => true`, `cache_results => false`. Every comparison asserts the run under test sent at least one statement against the posts table. Tests:
  - `test_swaps_a_covered_query`: a sent statement contains `EXISTS ( SELECT ` and no `GROUP BY`; the IDs equal the same query with the filter returning false; they differ from the site's newest 7.
  - `test_swaps_the_full_form`: with `split_the_query` returning false, the full `{posts}.*` statement is swapped and the IDs match.
  - `test_ids_fields_only_for_copies`: `fields => ids` swaps with role `copy`, not with role `grid`.
  - `test_steps_aside` with a provider; each asserts no sent statement contains `EXISTS ( SELECT ` and the IDs equal the filter-off run: filter false; transient set; unmarked; meta query; `s` set; `no_found_rows` false; `orderby => rand`; `orderby => 'RAND(7)'`; tiebreaker removed (`remove_all_filters( 'posts_orderby' )`); an `ORDER BY` that names the term table (a `posts_orderby` callback); no `LIMIT` (`posts_per_page => -1`); nested tax query; OR with `NOT IN`; OR with `IN` then lowercase `in`; only `NOT IN`; deleted term; a plugin changes where, join, groupby, distinct or fields at priority 10; a plugin appends to where at `posts_clauses` 999; a `posts_search` callback adds text with `s` empty; a `posts_request` callback changes the statement; a `posts_request_ids` callback changes the split statement; a `query` callback at priority 10 changes the statement; `remove_all_filters( 'posts_where' )` before the query; `'title' => '50% off'` (core writes the `%` escape with `prepare()` at both looks, `class-wp-query.php:2179`).
  - `test_callbacks_accept_null`: each hook callback called directly with `null` returns `null` without an error.
  - `test_a_prepared_swap_is_used_once`: after a swap, an unmarked query that sends the identical statement (with `cache_results => false`) is not swapped.
  - `test_turn_off`: after `turn_off( 'failed', 'x' )`, the transient is set, one line reached the logger, and the next covered query in the same request is not swapped.
  - `test_rebuilds_are_not_shared_between_grids`: two marked queries for different terms, run one after another in one request, are both swapped and both return their own posts.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter PostGridQueryOptimizerTest`. Expected: FAIL (the old class has no `instance()`).
- [ ] **Step 3: Replace the class and its registration, and remove the old optimizer's pieces** listed under Files.
  - `last_look()` sets `mai_optimize_ready` true only when spec §3's cheap checks hold: every first look present and unchanged, `mai_optimize_search === ''`, `s` empty, no meta query, `no_found_rows` true, limits non-empty, distinct `''`, fields `{posts}.*` (or `{posts}.ID` for a copy), groupby `{posts}.ID`, first-look join non-empty, `Sql::orderby_ok()`, tax queries non-empty.
  - `prepare()` adds `[ 'owner' => $query, 'original' => $request, 'split' => Sql::split(...) for grids, 'role' => ... ]` when ready, the filter allows it (read once per request), the request flag is not off, `$request === mai_optimize_request`, and `$wpdb->remove_placeholder_escape( $request ) === $request`.
  - `swap()` returns at once when nothing is prepared. Otherwise it takes the most recent entry whose `original` or `split` equals the statement and removes it. Then, in this order: read the transient (once per request), `layer_allows( $wpdb )`, `allows( server_info( $wpdb ) )` (read once per request), rebuild (memoized per request under `md5( serialize( $owner->tax_query->queries ) ) . $wpdb->posts`), check the rebuilt join equals the first-look join and the rebuilt where appears exactly once in the first-look where, build the condition with `hint()`, swap, and only then record `[ 'queries' => $wpdb->num_queries, 'swapped' => $swapped, 'form' => copy|split|full, 'start' => microtime( true ) ]` for the owner. Any failed check returns the statement unchanged.
  - `outcome()` reports `failed` when `$wpdb->last_error` is set and either `$wpdb->num_queries` is exactly the recorded count plus one or `$wpdb->last_query` is the recorded swapped text; `seconds` is `microtime( true )` minus `start`.
  - `turn_off()` sets the request flag, sets the transient for `DAY_IN_SECONDS`, clears every prepared swap and record, and sends one line to the logger: `Grid query optimizer off for 24 hours ({$why}): {$detail}`.
- [ ] **Step 4: Run the new tests, then everything.** Run: `composer test-integration -- --filter PostGridQueryOptimizerTest`, then `composer test-unit` and `composer test-integration`. Expected: all PASS. Old grid tests still pass, because nothing marks grid queries yet.
- [ ] **Step 5: Commit**: "Grid optimizer: swap covered grid statements for the EXISTS form, replacing the old optimizer".

### Task 4: The ID tiebreaker for every grid without Load More, newest first

Superseded on 2026-10-05: see spec "Ties" for the tiebreaker rule.

**Files:**
- Modify: `lib/classes/class-mai-grid.php:257-386`: set `$this->query_args['mai_grid_tiebreak'] = true` right after `$asked = $this->query_args;` for every grid with `no_found_rows` (remove the line inside `if ( $defer )`); after the query, remove it from `query_vars` and `query` for every grid, deferring or not.
- Modify: `lib/classes/class-mai-grid.php:1100-1123`: always append `, {posts}.ID DESC`; the docblock says ties show newest first on every sort (spec "Ties").
- Modify tests that break: `GridDeferredExcludesTest.php:300-316` (expect `.ID DESC` on ASC sorts and on grids that do not defer); `GridCacheStoreTest.php:154-159`, `GridCacheAfterPageTest.php:248-252`, `GridKeptOnlyTest.php:416-421` (their "did it defer" checks look for the padded `LIMIT` in `$query->request` instead of `.ID DESC`). Fix the stale comments at `GridKeptOnlyTest.php:423-424`, `:1295`, `:1755` and `GridDeferredExcludesTest.php:255`, `:269`. Then `rg mai_grid_tiebreak tests/` and check every remaining use still means what it says.
- Modify: `CHANGES.md:25`, replaced with: `* Changed: Post grids without Load More now break ties in their sort order by entry ID, newest first. When many entries share the same sort value, such as menu order, views or comment count, the database could return a different set of them on each page load.`
- Test: `tests/phpunit/integration/GridTiebreakerTest.php`

**Interfaces:**
- Produces: every `Mai_Grid` post query with `no_found_rows` true carries `mai_grid_tiebreak` while it runs and not after; its `ORDER BY` ends in `, {posts}.ID DESC` unless it already names `{posts}.ID`.

- [ ] **Step 1: Write the failing tests** in `GridTiebreakerTest`: `test_a_grid_without_excludes_gets_the_tiebreaker` (captured statement ends its `ORDER BY` with `, {posts}.ID DESC`); `test_ascending_sorts_break_ties_newest_first` (`orderby => menu_order`, `order => ASC`, all posts at 0: the grid shows the highest IDs first); `test_load_more_grids_do_not` (a `mai_post_grid_query_args` callback sets `no_found_rows` false); `test_the_var_is_removed_afterwards` (`query_vars` and `query` lack it after `get_query()`, deferring and not).
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter GridTiebreakerTest`. Expected: FAIL.
- [ ] **Step 3: Change `Mai_Grid` and `CHANGES.md` as listed.**
- [ ] **Step 4: Update the existing tests listed, then run everything.** Run: `composer test-integration` and `composer test-unit`. Expected: PASS.
- [ ] **Step 5: Commit**: "Grids: tie-break every grid without Load More by ID, newest first".

### Task 5: Wire the optimizer into grids, copies and failures

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (`get_query()`: `Mai_Post_Grid_Query_Optimizer::instance()->mark( $query, 'grid' )` right after `new WP_Query()`)
- Modify: `lib/classes/class-mai-query-cache.php:942-1014` (`fetch_ids()`): mark the copy as `copy`; wrap `$copy->query()` in `try`/`finally` that calls `drop( $copy )`; then `outcome( $copy )`. On `failed`: keep `$wpdb->last_error` in a local, call `turn_off( 'failed', $error )`, `$this->forget_failure()`, and set `$copy->posts` from `$wpdb->get_col( $copy->request )` before today's checks. On `ok` with `seconds > Mai_Post_Grid_Query_Optimizer::$slow`: `turn_off( 'slow', ... )`.
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php`: add `recover( mixed $posts, mixed $query ): mixed`, registered on `posts_results` at `PHP_INT_MIN` (before `Mai_Query_Cache::posts_results()`, since the optimizer registers at `init` 9). `prepare()` arms a grid only when `has_filter( 'posts_results', [ $this, 'recover' ] )`.
- Modify tests that use `refuse_once()` to test today's failure path, adding `add_filter( 'mai_post_grid_optimize_query', '__return_false' )`: `GridCacheFailedQueryTest.php` (whole class), `GridCacheAfterPageTest.php:998`, `:1383`, `:1550`, `GridKeptOnlyTest.php:952`.
- Modify: `tests/phpunit/integration/HarnessTest.php` (assert `recover` runs before `Mai_Query_Cache::posts_results` on `posts_results`).
- Modify: `CHANGES.md` 2.41.0, two lines: `* Changed: [Performance] Post grids filtered by categories, tags or other taxonomies load faster on large sites.` and ``* Changed: [Developers] New `mai_post_grid_optimize_query` filter. Return false to turn the faster grid queries off.``
- Test: `tests/phpunit/integration/PostGridQueryOptimizerGridTest.php`, plus one test in `GridCacheAfterPageTest.php`

**Interfaces:**
- Consumes: Task 3 (`mark`, `drop`, `outcome`, `turn_off`, `set_logger`, `$slow`), Task 4.
- Produces: `recover()`. For a marked grid it calls `outcome()` and `drop()`. On `failed` it keeps `$wpdb->last_error`, calls `turn_off()`, `wp_cache_set_posts_last_changed()`, and resends `$query->request` by form: split is `get_col`, then `_prime_post_caches( $ids, $query->query_vars['update_post_term_cache'], $query->query_vars['update_post_meta_cache'] )`, then `array_map( 'get_post', $ids )`; full is `array_map( 'get_post', $wpdb->get_results( $query->request ) )`.

- [ ] **Step 1: Write the failing tests** in `PostGridQueryOptimizerGridTest`, with the fixture, grids built through `( new Mai_Grid( $args ) )->get_query()` as in `GridKeptOnlyTest::run_grid()`, a logger collected through `set_logger()`, `wp_cache_flush()` and `reset()` before each run, and a breaker: a `query` callback at `PHP_INT_MAX` added in the test that appends ` BROKEN` once to a statement containing `EXISTS ( SELECT `.
  - `test_a_deferring_grid_swaps_its_copy_and_stores_the_note`
  - `test_a_grid_without_excludes_swaps_its_own_query`
  - `test_a_grid_with_the_cache_off_swaps_and_recovers` (`mai_post_grid_cache` false; swapped once, then broken once: right posts)
  - `test_a_failed_copy_swap_recovers` and `test_a_failed_grid_swap_recovers` (split and full, the full form forced with `split_the_query` false): the grid shows the same posts as with the filter off, the next view with the filter off reads the same posts from the note, the transient is set, one logged line, posts `last_changed` moved.
  - `test_a_failure_is_caught_when_the_breaker_sends_its_own_query`: the breaker also runs `$wpdb->query( 'SELECT 1' )`; same assertions.
  - `test_a_failed_swap_turns_it_off_for_the_page`: two grids rendered in one request, the first one's swap broken; the second sends no `EXISTS ( SELECT `; both show the right posts.
  - `test_a_slow_copy_turns_it_off`: set `Mai_Post_Grid_Query_Optimizer::$slow = 0.05` (restored in `tear_down()`); a later `query` callback calls `usleep( 60000 )` on the swapped statement and leaves it alone; the transient is set and the posts are right.
  - `test_recovery_is_not_armed_without_its_callback`: with `remove_all_filters( 'posts_results' )`, a grid's own statement is not swapped.
  - In `GridCacheAfterPageTest`: `test_the_rebuild_after_the_page_swaps`, using that class's `install_queue`, `warm`, `age` and `$now`.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter 'PostGridQueryOptimizerGridTest|test_the_rebuild_after_the_page_swaps'`. Expected: FAIL.
- [ ] **Step 3: Make the changes listed under Files.**
- [ ] **Step 4: Run everything.** Run: `composer test-integration -- --order-by=random` and `composer test-unit`. Expected: PASS.
- [ ] **Step 5: Commit**: "Grid optimizer: swap grid queries and copies, and recover from a failed swap".

### Task 6: Same posts across every covered grid

**Files:**
- Test: `tests/phpunit/integration/PostGridQueryOptimizerSamePostsTest.php`

**Interfaces:**
- Consumes: Tasks 2 to 5, through `Mai_Grid` only; the fixture, built once in `wpSetUpBeforeClass()`.

- [ ] **Step 1: Write the test** `test_same_posts( array $grid, string $context )` with two providers:
  - **Shapes by sorts:** every shape (`big` with children, `tag`, `custom`, `big` AND `tag`, `big` OR `tag`, `big` `IN` + `small` `NOT IN`, `big` `IN` + `custom` `NOT IN`, `small`, 29 terms) crossed with every sort (date, modified, title, name, menu order, comment count, ID, author), ascending and descending, at `posts_per_page` 7, visitor, no excludes.
  - **Limits, excludes and contexts** for three shapes (`big`, `big` AND `tag`, `big` `IN` + `small` `NOT IN`): `posts_per_page` 2, 32 and 200; excludes none, `exclude_current` (after `$this->go_to( get_permalink( $id ) )` on a `big` post) and `exclude_displayed` (set `Mai_Grid::$existing_post_ids` as `GridKeptOnlyTest::prepare()` does); contexts visitor, editor logged in (publish and private), and `post_type` `[ 'post', 'page' ]`.

  Each case renders the grid with the filter false, then true, with `wp_cache_flush()`, `( new Mai_Query_Cache() )->flush_all()` and `reset()` between. It asserts the same post IDs in the same order, that the second run sent a statement containing `EXISTS ( SELECT `, and that the IDs differ from the same grid with no taxonomy filter.
- [ ] **Step 2: Run it.** Run: `composer test-integration -- --filter PostGridQueryOptimizerSamePostsTest`. Expected: PASS. A failure is a real bug in Tasks 2 to 5: fix it there, with a test in that task's file, before going on.
- [ ] **Step 3: Commit**: "Grid optimizer: same posts across every covered grid".

### Task 7: Measurement tools

**Files:**
- Create: `bin/grid-optimizer-probe.php`: a temporary mu-plugin. On requests with `?mai_optimizer_probe=1`, for each marked query that reaches `prepare()`, it appends `{ "statement", "queries": $query->tax_query->queries, "posts": $wpdb->posts, "terms": $wpdb->term_relationships }` as a JSON line to `/tmp/mai-optimizer-statements.jsonl`.
- Create: `bin/grid-optimizer-pairs.php`: WP-CLI `eval-file`. It reads that file, plus a built-in list of synthetic shapes and sorts for the site's real big, mid-size (10 to 50% of posts), small and old terms, several post types, and publish plus private. It writes JSON lines `{ "name", "original", "swapped_mysql", "swapped_mariadb" }` for each statement at its own `LIMIT`, at `LIMIT 0, 2`, at `LIMIT 0, 32`, and with no `LIMIT`, using Task 2's methods.
- Create: `bin/grid-optimizer-replay.php`: plain PHP CLI with `mysqli`, run as `php bin/grid-optimizer-replay.php --pairs=FILE --host=127.0.0.1 --port=N --db=NAME --engine=mysql|mariadb --out=FILE`. For each pair:
  - the IDs of both forms must match
  - both forms alternate in one session, 10 runs each, timed with `NOW(6)`, medians taken
  - `EXPLAIN FORMAT=TREE` on MySQL, `EXPLAIN` on MariaDB; a swapped MySQL plan containing `weedout` or `Remove duplicates` is flagged
  - the bar passes when the swapped median ≤ today's median + max(0.5 ms, 10%)

  It prints one summary line per pair and a total, and writes all numbers as JSON.

**Interfaces:**
- Consumes: Task 2's `rebuild()`, `condition()`, `swap()`; Task 1's `hint()` rule (MySQL text carries the hint, MariaDB text does not).

- [ ] **Step 1: Write the three scripts.**
- [ ] **Step 2: Smoke-run on local eurweb.**
  1. Copy the probe to `~/Herd/eurweb/wp-content/mu-plugins/zzz-mai-optimizer-probe.php`.
  2. Switch `wp-config.php` per task-14-rules: back up, `WP_DEVELOPMENT_MODE ''`, `SCRIPT_DEBUG false`.
  3. Run `wp eval 'mai_cache( "grid" )->flush();'` in `~/Herd/eurweb`.
  4. Run `curl -sk -o /dev/null "https://eurweb.test/realistic-editorial-technology-photography/?mai_optimizer_probe=1"`, and the same for the home page.
  5. Restore `wp-config.php` with `cp`, check with `cmp`, and delete the probe.
  6. Run `wp eval-file ~/Plugins/mai-engine/bin/grid-optimizer-pairs.php` in `~/Herd/eurweb`.
  7. Run `php bin/grid-optimizer-replay.php --pairs=... --port=3306 --db=eurweb --engine=mysql`.

  Expected: every pair's IDs match, and the six article grid statements pass the bar.
- [ ] **Step 3: Commit** the three scripts: "Grid optimizer: tools to capture and replay grid statements on any database".

### Task 8: Docker runs on MySQL 8.0, 8.4 and MariaDB

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer-database.php` (`$mariadb_min` default), `lib/classes/class-mai-post-grid-query-optimizer-sql.php` (`SORT_COLUMNS`), their tests, and the spec (a "Results" section)

- [ ] **Step 1: Start the databases.**
  - Docker must be running in the way Mike chose (walk question, 2026-10-05). Never start an app with a bare `open`.
  - Start six containers, one per line:
    - `docker run -d --name mai-opt-mysql80 -e MYSQL_ALLOW_EMPTY_PASSWORD=1 -e MYSQL_DATABASE=mai_engine_tests -p 3380:3306 mysql:8.0`
    - `mai-opt-mysql84`: `mysql:8.4`, port 3381
    - `mai-opt-mariadb106`: `mariadb:10.6`, port 3382, `-e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1 -e MARIADB_DATABASE=mai_engine_tests`
    - `mai-opt-mariadb1011`: `mariadb:10.11`, port 3383, same env
    - `mai-opt-mariadb114`: `mariadb:11.4`, port 3384, same env
    - `mai-opt-mariadb118`: `mariadb:11.8`, port 3385, same env
  - Wait until each answers `mysql -u root -h 127.0.0.1 -P <port> -e 'SELECT VERSION()'`, and record each version.
- [ ] **Step 2: Run the integration suite on each.** Run: `WP_TESTS_DB_HOST=127.0.0.1:3380 composer test-integration`, and so on per port. On the four MariaDB ports add `WP_TESTS_MARIADB_MIN=10.6.0`.
  - A failure that is not in a `PostGridQueryOptimizer*` test: rerun it at the base commit (`git worktree add /tmp/mai-engine-base 17dddac85`, then `composer test-setup` there) on the same container. If it fails there too, it was already broken. Record it, and do not count it against these tasks.
  - A MySQL failure in the optimizer tests is a bug: fix it in its task first.
  - A MariaDB-only failure in the optimizer tests is recorded, and that version stays off.
- [ ] **Step 3: Load the three sites' tables.** For local eurweb, larrybrownsports and one small local site:
  1. Run `mysqldump -u root -h 127.0.0.1 --set-gtid-purged=OFF <db> <prefix>posts <prefix>term_relationships <prefix>term_taxonomy > /tmp/mai-opt-<site>.sql`.
  2. For MariaDB targets, use a copy with `utf8mb4_0900_ai_ci` replaced by `utf8mb4_unicode_520_ci`.
  3. Import into a database named after the site on each container.
  4. Check `SELECT COUNT(*)` of each table equals the local count.
- [ ] **Step 4: Replay.** Capture pairs for larrybrownsports and the small site as in Task 7 Step 2. Run `grid-optimizer-replay.php` for each site on each container, one at a time, with the load under 20 per task-14-rules. Expected: every pair's IDs match on every database, and no swapped MySQL plan shows weedout.
- [ ] **Step 5: Set the defaults from the results.**
  - `$mariadb_min` is the lowest MariaDB version whose every pair passed the bar, provided every tested version above it passed too. Otherwise it stays `''`.
  - Remove from `SORT_COLUMNS` only a sort that missed the bar on a database that stays on.
  - **If a covered shape or any MySQL version misses the bar, stop and report to Mike before changing anything.**
  - Update Task 1's and Task 2's tests to match, then run `composer test-unit` and `composer test-integration`. Expected: PASS.
- [ ] **Step 6: Record the results** in the spec under "Results": versions, pairs, misses, medians for the eurweb article statements per database, and the defaults chosen and why.
- [ ] **Step 7: Clean up.** Run `docker rm -f mai-opt-mysql80 mai-opt-mysql84 mai-opt-mariadb106 mai-opt-mariadb1011 mai-opt-mariadb114 mai-opt-mariadb118`, `/bin/rm /tmp/mai-opt-*.sql`, and, if made, `git worktree remove /tmp/mai-engine-base`.
- [ ] **Step 8: Commit**: "Grid optimizer: measured on MySQL 8.0 and 8.4 and MariaDB; set the defaults".

### Task 9: Whole pages, the checks' own cost, and wrap-up

**Files:**
- Modify: the spec ("Results"), `STATE.md`, `CHANGES.md` if Task 8 changed what is covered

- [ ] **Step 1: Measure cold article views on local eurweb,** swap on and off, per task-14-rules: four articles, 12 runs each, alternating, medians, with the load average recorded. Expected: about 1.5 s faster per fully cold article with the swap on.
- [ ] **Step 2: Measure the checks' own cost:**
  - Every note current, so no statement is sent: the home page and an article, swap on and off, 20 runs each.
  - Mai's notes emptied before each view, so statements are sent: the same pages, 12 runs each.
  - Expected: no difference above noise when nothing is sent, and the gain alone when statements are sent.
- [ ] **Step 3: Record both in the spec "Results", and check every local site you touched:** `cmp` on `wp-config.php`, no temporary mu-plugin left, and `git -C ~/Plugins/mai-engine status --short` shows only intended changes.
- [ ] **Step 4: Final verification.** Run: `composer test-unit`, `composer test-integration -- --order-by=random`, `php vendor/bin/deployable-guard check`. Expected: all pass.
- [ ] **Step 5: Rewrite `STATE.md`.** Now: built and measured, ready for a beta. Next: Mike runs the beta push commands for eurweb and larrybrownsports, then totalprosports.
- [ ] **Step 6: Commit**: "Grid optimizer: whole-page results and state". Do not push.
