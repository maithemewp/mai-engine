# Grid Taxonomy Query Rewrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mai post grid queries filtered by taxonomy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, wherever Mai can prove it returns the same posts, on by default, falling back to today's statement everywhere else.

**Architecture:** A new `Mai_Post_Grid_Query_Optimizer` (replacing the old class of that name) marks Mai's grid queries and ID-only copies, records each SQL part at the first and last plugin filter, and prepares a swap at `posts_request`. Its `$wpdb` `query` callback swaps the exact statement at the last moment, after rebuilding WordPress's tax SQL with WordPress's own `WP_Tax_Query` code (`Mai_Post_Grid_Query_Optimizer_Sql`) and checking the database (`Mai_Post_Grid_Query_Optimizer_Database`). Failures are pinned by statement count and owner, repaired by resending the original statement, and turn the swap off for 24 hours.

**Tech Stack:** PHP 8.1+, WordPress 6.4+ (`WP_Query`, `WP_Tax_Query`, `wpdb`), PHPUnit 10.5 (unit suite with brain/monkey, integration suite with wp-phpunit), MySQL 9.7 locally, Docker for MySQL 8.0, 8.4 and MariaDB 10.6, 10.11, 11.4, 11.8.

**Spec:** `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`. Read it before any task. Section numbers below ("spec §3") refer to it.

## Global Constraints

- PHP floor 8.1 (`Requires PHP: 8.1`, composer `^8.1`): no typed class constants, no `readonly` classes. `readonly` properties are fine.
- WordPress floor 6.4 (`Requires at least: 6.4`). On 6.4 and 6.5 the exact match fails and Mai steps aside; nothing to build for them.
- New PHP files start like `lib/classes/class-mai-query-cache-queue.php`: `<?php`, `declare(strict_types=1);`, the file docblock with `@package BizBudding\MaiEngine`, then `defined( 'ABSPATH' ) || die;`. Classes are global `Mai_*` names loaded from `lib/classes/class-{lowercased-dashed-name}.php` by `lib/functions/autoload.php` (the existing convention, so no namespace).
- Hook callbacks take `mixed` and return anything that is not the expected type unchanged.
- On `WP_Query`, assign whole property values. `$query->prop['k'] = $v` on an unset property is silently dropped (`class-wp-query.php:4117`).
- Exact values: filter `mai_post_grid_optimize_query` (default `true`); transient `mai_post_grid_optimize_off` for `DAY_IN_SECONDS`; slow limit `0.1` seconds; MySQL minimum `8.0.16`; MySQL hint `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ` placed right after `SELECT ` in each `EXISTS`; allowed `$wpdb` classes exactly `wpdb` and `QM_DB`; version strings containing `sqlite`, `vitess` or `tidb` (case-insensitive) are not MySQL; tiebreaker always `, {posts}.ID DESC`.
- No em-dashes in code comments, `CHANGES.md` or commit messages. Commit bodies are one line per paragraph and end with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- The committed `vendor/composer` autoloader must stay no-dev; `php vendor/bin/deployable-guard check` passes before every commit (the pre-commit hook runs it).
- Live sites are read-only. Never push, tag or release.
- Local site work follows `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: back up `wp-config.php`, restore with `cp`, check with `cmp`, never `wp cache flush` on eurweb or larrybrownsports, delete every temporary mu-plugin.

## Review Focus

1. **A related-posts grid on a page where list-category-posts already ran** (`remove_all_filters('posts_orderby')` stripped the tiebreaker): the grid shows today's posts, unswapped. Test: Task 3, `test_steps_aside` case "tiebreaker removed".
2. **A grid whose saved category was deleted** (`0 = 1`, no join): the page renders, no `ValueError`, today's statement. Test: Task 2 `test_rebuild_declines` and Task 3 `test_steps_aside` case "deleted term".
3. **A logged-in editor viewing a grid** (`post_status` publish and private): same posts as with the optimizer off. Test: Task 6 matrix case "editor".
4. **Two grids on one page, the first one's swapped statement fails:** both grids show the right posts, the second is not swapped. Test: Task 5 `test_a_failed_swap_turns_it_off_for_the_page`.
5. **A term whose term ID differs from its term_taxonomy ID** (common on old sites, never on a fresh install): same posts. Test: Task 2 fixture "skewed" and Task 6 matrix.

---

### Task 1: Database check

**Files:**
- Create: `lib/classes/class-mai-post-grid-query-optimizer-database.php`
- Test: `tests/phpunit/unit/PostGridQueryOptimizerDatabaseTest.php`

**Interfaces:**
- Produces:
  - `final class Mai_Post_Grid_Query_Optimizer_Database`
  - `public const MYSQL_MIN = '8.0.16';`
  - `public const MARIADB_MIN = '';` (empty means MariaDB is off; Task 8 sets it)
  - `public static function engine( mixed $server_info ): ?array` returning `[ 'engine' => 'mysql'|'mariadb', 'version' => 'X.Y.Z' ]`, or `null` when the value is not a non-empty string, contains `sqlite`, `vitess` or `tidb`, or has no version.
  - `public static function allows( mixed $server_info, string $mariadb_min = self::MARIADB_MIN ): bool`
  - `public static function hint( mixed $server_info ): bool` (true for MySQL only)
  - `public static function layer_allows( object $db ): bool` (`get_class( $db )` is exactly `wpdb` or `QM_DB`)

- [ ] **Step 1: Write the failing test** `PostGridQueryOptimizerDatabaseTest` extending `BizBudding\MaiEngine\Tests\TestCase`, with a data provider `servers()` and `test_allows( mixed $info, bool $expected )` asserting `allows( $info, '10.11.0' )`:
  - `'8.0.15'` false, `'8.0.16'` true, `'8.0.46-0ubuntu0.22.04.4'` true, `'8.4.11-11'` true, `'9.7.1'` true, `'5.7.44'` false
  - `'8.0.38-mysql-on-sqlite-3.0.2'` false, `'8.0.40-Vitess'` false, `'8.0.11-TiDB-v7.5.0'` false, `'8.0.mysql_aurora.3.04.0'` false
  - `'5.5.5-10.11.6-MariaDB'` true, `'10.11.6-MariaDB-log'` true, `'10.6.18-MariaDB'` false, `'11.8.2-MariaDB'` true
  - `false`, `''`, `null` all false

  Plus `test_mariadb_is_off_until_measured`: `allows( '11.8.2-MariaDB' )` with the default minimum is false. Plus `test_engine` (MariaDB with `5.5.5-` reads `10.11.6`), `test_hint` (MySQL true, MariaDB false), `test_layer_allows` (a `wpdb` stand-in: create classes `wpdb` and `QM_DB` in the test file only if not defined, plus `stdClass` false).
- [ ] **Step 2: Run it to see it fail.** Run: `composer test-unit -- --filter PostGridQueryOptimizerDatabaseTest`. Expected: FAIL, class not found.
- [ ] **Step 3: Implement the class.** MariaDB when the string contains `mariadb` (case-insensitive): drop a leading `5.5.5-`, take the first `\d+\.\d+\.\d+`. MySQL otherwise: the string must start with `\d+\.\d+\.\d+`. `allows()` compares with `version_compare()`, and an empty MariaDB minimum means false.
- [ ] **Step 4: Run it to see it pass.** Same command. Expected: PASS.
- [ ] **Step 5: Commit** `lib/classes/class-mai-post-grid-query-optimizer-database.php` and the test: "Grid optimizer: which databases can take the faster query".

### Task 2: Rebuild and rewrite WordPress's tax SQL

**Files:**
- Create: `lib/classes/class-mai-post-grid-query-optimizer-sql.php`
- Test: `tests/phpunit/integration/PostGridQueryOptimizerSqlTest.php`

**Interfaces:**
- Produces, all `public static` on `final class Mai_Post_Grid_Query_Optimizer_Sql`:
  - `const SORT_COLUMNS = [ 'post_date', 'post_modified', 'post_title', 'post_name', 'menu_order', 'comment_count', 'post_author', 'post_parent', 'post_type', 'ID' ];` (Task 8 removes any that miss the speed bar)
  - `rebuild( WP_Tax_Query $tax_query, string $posts_table ): ?array` returning `[ 'join' => string, 'where' => string, 'relation' => 'AND'|'OR', 'pieces' => list<array{where: string, alias: ?string}> ]`, or `null` when not covered.
  - `condition( array $rebuilt, string $posts_table, string $term_table, bool $hint ): string`
  - `swap( string $statement, array $rebuilt, string $condition, string $posts_table ): ?string`
  - `split( string $request, string $posts_table ): ?string`
  - `orderby_ok( string $orderby, string $posts_table ): bool`

- [ ] **Step 1: Write the failing tests.** Fixtures in `set_up()`: register taxonomy `mai_test_tax` for `post`; insert one spare row into `$wpdb->term_taxonomy` (term_id of an existing term, taxonomy `mai_test_spare`) before creating the test terms, so their term IDs and term_taxonomy IDs differ ("skewed"); a big category with 40 posts across 40 days, a child category, a small category with 3 posts dated 2015, a tag, a `mai_test_tax` term. Tests:
  - `test_rebuild_matches_core` with a provider of tax queries: one category (children on), one tag, one `mai_test_tax`, AND category + tag, AND three `IN` filters, OR category + tag, AND `IN` + `NOT IN`, AND `IN` + `'operator' => 'AND'`, AND `IN` + `'operator' => 'EXISTS'`. Assert `rebuild()` join and where equal `( new WP_Tax_Query( $tq ) )->get_sql( $wpdb->posts, 'ID' )`.
  - `test_rebuild_declines` returns `null` for: a nested query, OR with a `NOT IN` filter, OR with a lowercase `'in'` filter (two joins), only `NOT IN`, a deleted term (`0 = 1`), an unregistered taxonomy, an empty tax query.
  - `test_condition`: exact strings for one filter with and without the hint; AND with two `IN` filters (second uses `FROM {term} AS tt1 WHERE tt1.object_id = {posts}.ID AND tt1.term_taxonomy_id IN (...)`); OR where WordPress's whole condition, brackets included, sits inside one `EXISTS`.
  - `test_swap`: replaces the join, the where and `GROUP BY {posts}.ID` once each; returns `null` when the statement holds the join twice, lacks `GROUP BY`, or the rebuilt join or where is empty (and never calls `substr_count()` with an empty needle).
  - `test_split`: `"SELECT   {posts}.* FROM..."` becomes `"SELECT   {posts}.ID FROM..."`; anything else returns `null`.
  - `test_orderby_ok`: true for `{p}.post_date DESC, {p}.ID DESC`, `{p}.post_title ASC, {p}.ID DESC`, `{p}.menu_order ASC, {p}.post_date DESC, {p}.ID DESC`, `{p}.ID DESC`; false for `RAND(7), {p}.ID DESC`, `{p}.post_date DESC`, anything naming the term table or `tt1`, `{p}.post_content DESC, {p}.ID DESC`.
  - `test_same_posts`: for each covered shape, build today's statement with a `WP_Query` (`fields => ids`, `no_found_rows => true`, `posts_per_page => 10`, `orderby => [ 'date' => 'DESC', 'ID' => 'DESC' ]`, `cache_results => false`) and its swapped statement; `$wpdb->get_col()` both, with the `LIMIT` and with it removed (`nopaging => true` builds the no-`LIMIT` statement). Assert equal, and assert the result differs from the same query with no tax query. Include the small 2015 category and the skewed terms.
  - `test_or_without_brackets_would_be_wrong`: the OR swap with WordPress's brackets stripped returns different posts than today's statement, and the real swap returns the same. This proves `test_same_posts` would catch the bracket mistake.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter PostGridQueryOptimizerSqlTest`. Expected: FAIL, class not found.
- [ ] **Step 3: Implement `rebuild()`.** One fresh `new WP_Tax_Query( $tax_query->queries )`, set its public `primary_table` and `primary_id_column` (`'ID'`), and loop the copied queries by reference, calling the public `get_sql_for_clause( $clause, $queries )` on each first-order clause, exactly as `get_sql_for_query()` does at depth 0 (`class-wp-tax-query.php:302-370`). Mirror the protected `is_first_order_clause()` (`:229`) to spot nesting and return `null`. Return `null` when a clause's where is `0 = 1`, has more than one where entry, no clause produced a join, or the relation is OR and any clause is not `IN` or the joins are not exactly one. Reassemble join and where with core's exact separators (`implode( ' ', array_unique( ... ) )`, and `' AND ( ' . "\n  " . implode( " \n  {$relation} \n  ", $chunks ) . "\n)"`). A piece's `alias` is `$clause['alias']` after the call, or `null` for filters that use their own subquery.
- [ ] **Step 4: Implement `condition()`, `swap()`, `split()` and `orderby_ok()`.** AND: `' AND ' . implode( ' AND ', $parts )`, where an aliased piece becomes `EXISTS ( SELECT {hint}1 FROM {term}[ AS {alias}] WHERE {alias}.object_id = {posts}.ID AND {piece where} )` (no `AS` when the alias is the table name) and other pieces stay as written. OR: `' AND EXISTS ( SELECT {hint}1 FROM {term} WHERE {term}.object_id = {posts}.ID AND ' . substr( $where, 5 ) . ' )'`. `split()` requires the exact prefix `"SELECT   {posts}.*"` (three spaces: core's template with empty found-rows and distinct). `orderby_ok()` is one anchored regex over `SORT_COLUMNS` with optional `ASC|DESC`, ending in `{posts}.ID (ASC|DESC)`.
- [ ] **Step 5: Run the tests to see them pass.** Same command. Expected: PASS.
- [ ] **Step 6: Commit**: "Grid optimizer: rebuild WordPress's tax SQL and write the EXISTS form".

### Task 3: The optimizer: marks, looks, prepared swaps and the swap (replaces the old optimizer)

**Files:**
- Replace: `lib/classes/class-mai-post-grid-query-optimizer.php` (same class name, new class)
- Modify: `lib/functions/performance.php:293-303` (`add_action( 'init', 'mai_register_post_grid_query_optimizer', 9 )`, body `Mai_Post_Grid_Query_Optimizer::instance()->register();`)
- Modify: `tests/phpunit/integration/MaiIntegrationTestCase.php` (`set_up()` and `tear_down()` call `Mai_Post_Grid_Query_Optimizer::instance()->reset()` and `delete_transient( 'mai_post_grid_optimize_off' )`)
- Modify: `lib/classes/class-mai-query-cache.php` (remove the `mai_post_grid_tt_ids` check in `is_cacheable()`, around `:490-493`), `tests/phpunit/unit/MaiQueryCacheabilityTest.php` (remove the two tests at `:28` and `:32`), `lib/classes/class-mai-grid.php:927-928` (drop "the optimizer's fast path" from the comment), `.agents/elasticpress-grid-cache.md:58` (stale line numbers)
- Delete: `tests/phpunit/unit/PostGridQueryOptimizer{Args,Classify,Orderby,Register,Where}Test.php`, `bin/grid-query-equivalence.php`, `bin/grid-equivalence-matrix.php`
- Test: `tests/phpunit/integration/PostGridQueryOptimizerTest.php`

**Interfaces:**
- Consumes: Task 1 (`allows()`, `hint()`, `layer_allows()`), Task 2 (all five methods).
- Produces, on `final class Mai_Post_Grid_Query_Optimizer`:
  - `public const FILTER = 'mai_post_grid_optimize_query'; public const TRANSIENT = 'mai_post_grid_optimize_off'; public const SLOW = 0.1;`
  - `public static function instance(): self`
  - `public function register(): void`
  - `public function mark( WP_Query $query, string $role ): void` (`'grid'` or `'copy'`; sets `$query->mai_optimize = $role`)
  - `public function drop( WP_Query $query ): void` (removes that query's prepared swaps)
  - `public function outcome( WP_Query $query ): ?array` returning `null` when nothing was swapped for it, else `[ 'status' => 'ok'|'failed', 'seconds' => float ]`, and forgetting the record
  - `public function turn_off( string $why, string $detail = '' ): void`
  - `public function set_logger( Closure $logger ): void` (default: `error_log( 'Mai Engine: ' . $message )` when `WP_DEBUG_LOG`)
  - `public function reset(): void` (clears every in-memory flag, memo, prepared swap and record; for tests)
  - Hook callbacks, all `public` and typed `mixed` in and out: `first_look( string $part, mixed $value, mixed $query )` (via closures for `posts_where`, `posts_join`, `posts_groupby`, `posts_distinct`, `posts_fields` at `PHP_INT_MIN`), `last_search()` (`posts_search`, `PHP_INT_MAX`), `last_look()` (`posts_clauses_request`, `PHP_INT_MAX`), `first_request()` (`posts_request`, `PHP_INT_MIN`), `prepare()` (`posts_request`, `PHP_INT_MAX`), `swap( mixed $sql )` (`query`, `PHP_INT_MAX`)
  - Query properties, each assigned whole: `mai_optimize`, `mai_optimize_first` (array of the five parts), `mai_optimize_search`, `mai_optimize_ready` (bool), `mai_optimize_request` (string)

- [ ] **Step 1: Write the failing tests** in `PostGridQueryOptimizerTest`. Helper `run_marked( array $args, string $role = 'grid' ): array` returns the query and every statement sent, captured by a `query` callback at `PHP_INT_MAX` added in the test (after `register()`, so it sees the swapped text). Base args: `post_type => post`, a 40-post category, `posts_per_page => 7`, `no_found_rows => true`, `ignore_sticky_posts => true`, `mai_grid_tiebreak => true`. Tests:
  - `test_swaps_a_covered_query`: a statement containing `EXISTS (` and no `GROUP BY` was sent, and the posts equal the same query with the filter returning false.
  - `test_swaps_the_full_form`: with `split_the_query` returning false, the full `{posts}.*` statement is swapped and the posts match.
  - `test_copy_role_allows_ids_fields_only_for_copies`: `fields => ids` swaps with role `copy`, not with role `grid`.
  - `test_steps_aside` with a provider; each asserts no sent statement contains `EXISTS (` from Mai and the posts equal the filter-off run: filter false; transient set; unmarked; meta query; `s` set; `no_found_rows` false; `orderby => rand`; `orderby => 'RAND(7)'`; tiebreaker removed (`remove_all_filters( 'posts_orderby' )`); no `LIMIT` (`posts_per_page => -1`); nested tax query; only `NOT IN`; deleted term; a plugin changes where, join, groupby, distinct or fields at priority 10; a plugin appends to where at `posts_clauses` 999; a `posts_search` callback adds text with `s` empty; a `posts_request` callback changes the statement; a `posts_request_ids` callback changes the split statement; a `query` callback at priority 10 changes the statement; `remove_all_filters( 'posts_where' )` after `register()`; a `LIKE '%...%'` added to where (placeholder escape).
  - `test_callbacks_accept_null`: each hook callback called directly with `null` returns `null` without an error.
  - `test_a_prepared_swap_is_used_once`: after a swap, an unmarked query that sends the identical statement is not swapped.
  - `test_turn_off`: after `turn_off( 'failed', 'x' )`, the transient is set, one line reached the logger, and the next covered query in the same request is not swapped.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter PostGridQueryOptimizerTest`. Expected: FAIL (old class has no `instance()`).
- [ ] **Step 3: Replace the class and its registration, and remove the old optimizer's pieces** listed under Files. `last_look()` sets `mai_optimize_ready` true only when spec §3's cheap checks hold: every first look present and unchanged, `mai_optimize_search === ''`, `s` empty, no meta query, `no_found_rows` true, limits non-empty, distinct `''`, fields `{posts}.*` (or `{posts}.ID` for a copy), groupby `{posts}.ID`, first-look join non-empty, `Sql::orderby_ok()`, tax queries non-empty. `prepare()` adds `[ 'owner' => $query, 'original' => $request, 'split' => Sql::split(...) for grids, 'role' => ... ]` when ready, the filter allows it (read once per request), the request flag is not off, `$request === mai_optimize_request`, and `$wpdb->remove_placeholder_escape( $request ) === $request`. `swap()` returns at once when nothing is prepared; otherwise takes the most recent entry whose `original` or `split` equals the statement, removes it, then checks the transient (read once per request), `layer_allows( $wpdb )` and `allows()` on `$wpdb->db_server_info()` (read once, inside `try`/`catch ( Throwable )`, cast to string), rebuilds (memoized per owner with `spl_object_id()`), checks the rebuilt join equals the first-look join and the rebuilt where appears exactly once in the first-look where, builds the condition with `hint()`, swaps, and records `[ 'queries' => $wpdb->num_queries, 'form' => copy|split|full, 'start' => microtime( true ) ]` for the owner. Any failed check returns the statement unchanged. `outcome()` reports `failed` when `$wpdb->last_error` is set and `$wpdb->num_queries` is exactly the recorded count plus one, and `seconds` as `microtime( true )` minus `start`. `turn_off()` sets the request flag, sets the transient for `DAY_IN_SECONDS`, clears every prepared swap and record, and sends one line to the logger: `Grid query optimizer off for 24 hours ({$why}): {$detail}`.
- [ ] **Step 4: Run the new tests and the whole suite.** Run: `composer test-integration -- --filter PostGridQueryOptimizerTest`, then `composer test-unit` and `composer test-integration`. Expected: all PASS.
- [ ] **Step 5: Commit**: "Grid optimizer: swap covered grid statements for the EXISTS form, replacing the old optimizer".

### Task 4: The ID tiebreaker for every grid without Load More, newest first

**Files:**
- Modify: `lib/classes/class-mai-grid.php:257-386` (set `mai_grid_tiebreak` after `can_defer_excludes()` for every grid with `no_found_rows`, and remove it from `query_vars` and `query` after the query for every grid), `lib/classes/class-mai-grid.php:1100-1123` (always append `, {posts}.ID DESC`; docblock says why: ties show newest first on every sort, spec "Ties")
- Modify: tests that spot a deferring grid or its copy by `mai_grid_tiebreak`: `GridCacheStoreTest.php:139`, `GridKeptOnlyTest.php:228`, `:809`, `:846`, `:885`, `:894`, `:1074`, `:1486`. Spot the padded grid query with `isset( $query->mai_grid_keep )` and the copy with `'ids' === fields && empty( mai_cache )`.
- Modify: tests asserting only deferring grids get it: `GridDeferredExcludesTest.php:308-316`, `GridKeptOnlyTest.php:424-425`, `:1295-1296`.
- Modify: `CHANGES.md` 2.41.0 (rewrite the "now break ties in their sort order by entry ID" entry: every post grid without Load More, ties newest first)
- Test: `tests/phpunit/integration/GridTiebreakerTest.php`

**Interfaces:**
- Produces: every `Mai_Grid` post query with `no_found_rows` true carries `mai_grid_tiebreak` while it runs and not after; its `ORDER BY` ends in `, {posts}.ID DESC` unless it already names `{posts}.ID`.

- [ ] **Step 1: Write the failing tests** in `GridTiebreakerTest`: `test_a_grid_without_excludes_gets_the_tiebreaker` (captured statement `ORDER BY ... , {posts}.ID DESC`); `test_ascending_sorts_break_ties_newest_first` (`orderby => menu_order`, `order => ASC`, all posts tied at 0: the grid shows the highest IDs first); `test_load_more_grids_do_not` (a `mai_post_grid_query_args` callback sets `no_found_rows` false); `test_the_var_is_removed_afterwards` (`query_vars` and `query` lack `mai_grid_tiebreak` after `get_query()`, deferring and not).
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter GridTiebreakerTest`. Expected: FAIL.
- [ ] **Step 3: Change `Mai_Grid` as listed under Files.**
- [ ] **Step 4: Update the existing tests listed under Files, then run everything.** Run: `composer test-integration` and `composer test-unit`. Expected: PASS.
- [ ] **Step 5: Commit**: "Grids: tie-break every grid without Load More by ID, newest first".

### Task 5: Wire the optimizer into grids, copies and failures

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (`get_query()`: `Mai_Post_Grid_Query_Optimizer::instance()->mark( $query, 'grid' )` right after `new WP_Query()`)
- Modify: `lib/classes/class-mai-query-cache.php:942-1013` (`fetch_ids()`: mark the copy as `copy`; wrap `$copy->query()` in `try`/`finally` that calls `drop( $copy )`; then `outcome( $copy )`: on `failed`, call `turn_off( 'failed', $wpdb->last_error )`, `wp_cache_set_posts_last_changed()`, and set `$copy->posts` from `$wpdb->get_col( $copy->request )` before today's checks; on `ok` with `seconds > SLOW`, call `turn_off( 'slow', ... )`)
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php` (add `recover( mixed $posts, mixed $query ): mixed` on `posts_results` at `PHP_INT_MIN`; `prepare()` arms a grid only when `has_filter( 'posts_results', [ $this, 'recover' ] )`)
- Modify: `CHANGES.md` 2.41.0 (Changed: [Performance] grids filtered by categories, tags or other taxonomies ask the database in a faster form on big sites; [Developers] `mai_post_grid_optimize_query` now defaults to true and turns it off when false)
- Test: `tests/phpunit/integration/PostGridQueryOptimizerGridTest.php`

**Interfaces:**
- Consumes: Task 3 (`mark`, `drop`, `outcome`, `turn_off`, `set_logger`, `SLOW`), Task 4 (every grid carries the tiebreaker).
- Produces: `recover()`. For a marked grid it calls `outcome()` and `drop()`; on `failed` it calls `turn_off()`, `wp_cache_set_posts_last_changed()`, and resends `$query->request`: split form `get_col`, `_prime_post_caches( $ids, $query->query_vars['update_post_term_cache'], $query->query_vars['update_post_meta_cache'] )`, `array_map( 'get_post', $ids )`; full form `array_map( 'get_post', $wpdb->get_results( $query->request ) )`.

- [ ] **Step 1: Write the failing tests** in `PostGridQueryOptimizerGridTest`, with grids built through `( new Mai_Grid( $args ) )->get_query()` like `GridKeptOnlyTest::run_grid()`, a logger collected through `set_logger()`, and a breaker: a `query` callback at `PHP_INT_MAX` added after `register()` that appends ` BROKEN` once to a statement containing `EXISTS (`.
  - `test_a_deferring_grid_swaps_its_copy_and_stores_the_note`
  - `test_a_grid_without_excludes_swaps_its_own_query`
  - `test_a_grid_with_the_cache_off_swaps_too` (`mai_post_grid_cache` false)
  - `test_the_rebuild_after_the_page_swaps` (`run_queue()` like `GridCacheAfterPageTest`)
  - `test_a_failed_copy_swap_recovers` and `test_a_failed_grid_swap_recovers` (split and full, the full form forced with `split_the_query` false): the grid shows the same posts as with the filter off, nothing wrong is stored (the next view with the filter off reads the same posts), `get_transient( TRANSIENT )` is set, one logged line, posts `last_changed` moved.
  - `test_a_failed_swap_turns_it_off_for_the_page`: two grids on one render; the first one's swap breaks; the second sends no `EXISTS (`; both show the right posts.
  - `test_a_slow_copy_turns_it_off`: a `query` callback after Mai's turns the swapped copy into a slow one by inserting ` AND (SELECT SLEEP(0.12)) = 0` before ` ORDER BY `; the transient is set and the posts are right.
  - `test_recovery_is_not_armed_without_its_callback`: with `remove_all_filters( 'posts_results' )`, a grid's own statement is not swapped.
- [ ] **Step 2: Run them to see them fail.** Run: `composer test-integration -- --filter PostGridQueryOptimizerGridTest`. Expected: FAIL.
- [ ] **Step 3: Make the changes listed under Files.**
- [ ] **Step 4: Run everything.** Run: `composer test-integration -- --order-by=random` and `composer test-unit`. Expected: PASS.
- [ ] **Step 5: Commit**: "Grid optimizer: swap grid queries and copies, and recover from a failed swap".

### Task 6: Same posts across every covered grid

**Files:**
- Test: `tests/phpunit/integration/PostGridQueryOptimizerSamePostsTest.php`

**Interfaces:**
- Consumes: Tasks 3 to 5 through `Mai_Grid` only.

- [ ] **Step 1: Write the test** `test_same_posts( array $grid, string $context )` with a provider crossing:
  - shapes: one category with children, one tag, one custom taxonomy, category AND tag, category OR tag, category `IN` + category `NOT IN`, category `IN` + a second taxonomy `NOT IN`, the small 2015 category, skewed term IDs (fixture as in Task 2)
  - sorts: date, modified, title, name, menu order (all tied), comment count (mostly tied), ID, author; ascending and descending
  - `posts_per_page` 2, 7 and 32; excludes none, `exclude_current`, `exclude_displayed`
  - contexts: visitor, and an editor logged in (`post_status` publish and private, with two private posts in the fixtures), and `post_type` `[ 'post', 'page' ]`

  Each case renders the grid with the filter false, then true (flushing Mai's grid cache between), asserts the same post IDs in the same order, and asserts at least one statement with `EXISTS (` was sent on the second run.
- [ ] **Step 2: Run it.** Run: `composer test-integration -- --filter PostGridQueryOptimizerSamePostsTest`. Expected: PASS. A failure is a real bug in Tasks 2 to 5: fix it there, with a test in that task's file, before going on.
- [ ] **Step 3: Commit**: "Grid optimizer: same posts across every covered grid".

### Task 7: Measurement tools

**Files:**
- Create: `bin/grid-optimizer-probe.php` (a temporary mu-plugin: on requests with `?mai_optimizer_probe=1`, for each marked query that reaches `prepare()`, append `{ "statement": ..., "queries": $query->tax_query->queries, "posts": $wpdb->posts, "terms": $wpdb->term_relationships }` as a JSON line to `/tmp/mai-optimizer-statements.jsonl`)
- Create: `bin/grid-optimizer-pairs.php` (WP-CLI `eval-file`: reads that file plus a built-in list of synthetic grid shapes and sorts for the site's real big, mid-size, small and old terms, and writes JSON lines `{ "name", "original", "swapped_mysql", "swapped_mariadb", "original_nolimit", "swapped_mysql_nolimit", "swapped_mariadb_nolimit" }` using Task 2's methods)
- Create: `bin/grid-optimizer-replay.php` (plain PHP CLI with `mysqli`: `php bin/grid-optimizer-replay.php --pairs=FILE --host=127.0.0.1 --port=N --db=NAME --engine=mysql|mariadb --out=FILE`. For each pair: IDs of both forms with and without `LIMIT` must match; both forms alternate in one session, 10 runs each, `NOW(6)` timing, medians; `EXPLAIN FORMAT=TREE` on MySQL and `EXPLAIN` on MariaDB for both; flag a MySQL plan containing `weedout` or `Remove duplicates`; pass the bar when swapped median ≤ today's median + max(0.5 ms, 10%). Prints one summary line per pair and a total, writes all numbers as JSON)

**Interfaces:**
- Consumes: Task 2's `rebuild()`, `condition()`, `swap()`; Task 1's `hint()` decision (MySQL text carries the hint, MariaDB text does not).

- [ ] **Step 1: Write the three scripts.**
- [ ] **Step 2: Smoke-run on local eurweb.** Copy the probe into `~/Herd/eurweb/wp-content/mu-plugins/zzz-mai-optimizer-probe.php`, switch `wp-config.php` per task-14-rules (backup, `WP_DEVELOPMENT_MODE ''`, `SCRIPT_DEBUG false`), `wp eval 'mai_cache( "grid" )->flush();'`, `curl -sk -o /dev/null "https://eurweb.test/realistic-editorial-technology-photography/?mai_optimizer_probe=1"` and the home page, restore `wp-config.php` with `cp` and check with `cmp`, delete the probe. Then `wp eval-file ~/Plugins/mai-engine/bin/grid-optimizer-pairs.php` in `~/Herd/eurweb`, then `php bin/grid-optimizer-replay.php --pairs=... --port=3306 --db=eurweb --engine=mysql`. Expected: every pair has matching IDs; the six article grid statements pass the bar.
- [ ] **Step 3: Commit** the three scripts: "Grid optimizer: tools to capture and replay grid statements on any database".

### Task 8: Docker runs on MySQL 8.0, 8.4 and MariaDB

**Files:**
- Modify: `lib/classes/class-mai-post-grid-query-optimizer-database.php` (`MARIADB_MIN`), `lib/classes/class-mai-post-grid-query-optimizer-sql.php` (`SORT_COLUMNS`), their tests, and the spec (a "Results" section)

- [ ] **Step 1: Start the databases.** Check `docker info` works; if Docker is not running, start it with `open -g -a Docker` (never bare `open`). Then, one per line: `docker run -d --name mai-opt-mysql80 -e MYSQL_ALLOW_EMPTY_PASSWORD=1 -e MYSQL_DATABASE=mai_engine_tests -p 3380:3306 mysql:8.0`, and the same for `mysql:8.4` (3381), `mariadb:10.6` (3382, `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1`, `MARIADB_DATABASE=mai_engine_tests`), `mariadb:10.11` (3383), `mariadb:11.4` (3384), `mariadb:11.8` (3385). Record each `SELECT VERSION()`.
- [ ] **Step 2: Run the integration suite on each.** Run: `WP_TESTS_DB_HOST=127.0.0.1:3380 composer test-integration` and so on for each port. Expected: PASS on all six. A MySQL failure is a bug to fix in its task first. A MariaDB-only failure in the optimizer tests is recorded and that version stays off.
- [ ] **Step 3: Load the three sites' tables.** For local eurweb, larrybrownsports and one small local site: `mysqldump -u root -h 127.0.0.1 --set-gtid-purged=OFF <db> <prefix>posts <prefix>term_relationships <prefix>term_taxonomy > /tmp/mai-opt-<site>.sql`; for MariaDB targets replace `utf8mb4_0900_ai_ci` with `utf8mb4_unicode_520_ci` in a copy; import into a database named after the site on each container; check `SELECT COUNT(*)` of each table equals the local count.
- [ ] **Step 4: Replay.** Capture pairs for larrybrownsports and the small site as in Task 7 Step 2, then run `grid-optimizer-replay.php` for each site on each container (one at a time, load under 20 per task-14-rules). Expected: every pair has matching IDs on every database, and no MySQL plan shows weedout.
- [ ] **Step 5: Set the constants from the results.** `MARIADB_MIN` is the lowest MariaDB version whose every pair passed the bar, provided every tested version above it passed too; otherwise it stays `''`. Remove from `SORT_COLUMNS` any sort that missed the bar on any database. Update Task 1's and Task 2's tests to match, and run `composer test-unit` and `composer test-integration`. Expected: PASS.
- [ ] **Step 6: Record the results** in the spec under "Results": versions, pairs, misses, medians for the eurweb article statements per database, the constants chosen and why.
- [ ] **Step 7: Stop and remove the containers.** Run: `docker rm -f mai-opt-mysql80 mai-opt-mysql84 mai-opt-mariadb106 mai-opt-mariadb1011 mai-opt-mariadb114 mai-opt-mariadb118`, and `/bin/rm /tmp/mai-opt-*.sql`.
- [ ] **Step 8: Commit**: "Grid optimizer: measured on MySQL 8.0 and 8.4 and MariaDB; set the minimums".

### Task 9: Whole pages, the checks' own cost, and wrap-up

**Files:**
- Modify: the spec ("Results"), `STATE.md`, `CHANGES.md` if Task 8 changed what is covered

- [ ] **Step 1: Measure cold article views on local eurweb**, swap on and off, per task-14-rules: four articles, 12 runs each, alternating, medians with the load average recorded. Expected: about 1.5 s faster per fully cold article with the swap on.
- [ ] **Step 2: Measure the checks' own cost:** the home page and an article with every note current (no statement sent), swap on and off, 20 runs each. Expected: no difference above noise (well under 1 ms).
- [ ] **Step 3: Record both in the spec "Results", and check every local site you touched:** `cmp` on `wp-config.php`, no temporary mu-plugin left, `git -C ~/Plugins/mai-engine status --short` shows only intended changes.
- [ ] **Step 4: Final verification.** Run: `composer test-unit`, `composer test-integration -- --order-by=random`, `php vendor/bin/deployable-guard check`. Expected: all pass.
- [ ] **Step 5: Rewrite `STATE.md`** (now: built and measured, ready for a beta; next: Mike runs the beta push commands for eurweb and larrybrownsports, then totalprosports).
- [ ] **Step 6: Commit**: "Grid optimizer: whole-page results and state". Do not push.
