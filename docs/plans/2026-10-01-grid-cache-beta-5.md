# Grid cache beta.5 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship Mai Engine 2.41.0-beta.5 with mai-cache 0.5.0 (released as 0.6.0, see Task 15): grids load only the posts they show, a note that only aged out is rebuilt after the page, small sites without Redis save notes after the page, plus the agreed fixes.

**Architecture:** mai-cache gains soft and hard lifetimes, an injectable clock, a fixed `bump()`, and flush cleanup. Mai Engine merges the kept-only branch, then adds a small per-request queue (`Mai_Query_Cache_Queue`) that holds pending stores and rebuild jobs and finishes the response with `fastcgi_finish_request()` before running them. Version-stale notes (a post was saved) keep today's during-the-page rebuild.

**Tech Stack:** PHP 8.1+, WordPress 6.4+, PHPUnit 10 with Brain Monkey (unit) and the WordPress test suite (integration), Composer.

**Spec:** `docs/specs/2026-10-01-grid-cache-beta-5.md`. Read its "How it works, in plain terms" section first. Every task implements a part of it; where the plan and the spec disagree, stop and ask Mike.

## Global Constraints

- Repos: mai-cache at `~/LocalPackages/mai-cache` (branch `develop`), Mai Engine at `~/Plugins/mai-engine` (branch `develop`). The kept-only branch is `feat/grid-load-kept-posts` in the worktree `~/Plugins/mai-engine-grid-loading`.
- PHP floor 8.1 in both repos (`composer.json`, plugin header `Requires PHP: 8.1`). No syntax newer than 8.1.
- New PHP files start with `declare(strict_types=1);`. Existing files do not get it added.
- Match surrounding code: tabs, WordPress spacing, docblocks with `@since`. In Mai Engine use `@since TBD` (the beta commit replaces it with `2.41.0`). In mai-cache use `@since 0.5.0`.
- Mai Engine classes are global `Mai_*` classes loaded by name from `lib/classes/class-mai-<name>.php` (`lib/functions/autoload.php`; the unit bootstrap mirrors it).
- No em-dash characters (U+2014) in anything Mike reads: changelogs, commit messages, comments meant as docs, `STATE.md`. Plain language, soft-wrapped paragraphs.
- No The Events Calendar specific code.
- Never push, tag, or fast-forward a shared branch without Mike's explicit yes for that specific action. Commit locally freely.
- Auto mode blocks destructive git (branch deletes, worktree removal). Hand those exact commands to Mike.
- Headless browsers only. Never open a window in front of Mike (`open -g` if ever needed).
- Live sites: read-only. Never write to a production site.
- Filter names (exact): `mai_query_cache_hard_ttl` (default `DAY_IN_SECONDS`), `mai_query_cache_after_page` (default `true`), `mai_query_cache_after_page_ms` (default `1000`). Existing filters keep their names and defaults (`mai_query_cache_ttl` 4 hours, `mai_query_cache_lock_ttl` 5 seconds, `mai_query_cache_wait_ms` 500).
- Test commands. mai-cache: `composer test-unit` in `~/LocalPackages/mai-cache`. Mai Engine: `composer test-unit` and `composer test-integration` in `~/Plugins/mai-engine` (run `composer install -d tests` first if `tests/vendor` is missing; integration needs local MySQL with database `mai_engine_tests`, see `tests/phpunit/integration/wp-tests-config.php`).

## Review Focus

Inputs and failure modes the spec implies that are easy to miss. Each has a test in the task that owns the code.

1. **A post saved between a grid's query and its deferred store** (sites without Redis). The stored note must carry the version read before the query, so it reads stale next time. Test in Task 10.
2. **Two grids with the same cache key on one page,** in every combination: cold, version-stale, age-stale, pending store, queued job. One query per key per request at most. Tests in Tasks 10 and 11.
3. **A job running after Mai_Grid restored the query** (posts per page, excludes, tiebreak removed). The job must use only what it captured. Test in Task 11.
4. **A request that cannot finish early** (no PHP-FPM, AJAX, REST, cron, WP-CLI, feed, `switch_to_blog()`). Nothing is queued and stores happen at today's point. Tests in Tasks 9 and 10.
5. **Entries written by beta.4** (no `w`, no `'by'` marker, padded IDs). Never age-stale, never queued, still served. Tests in Tasks 6 and 11.

---

## Phase A: mai-cache 0.5.0

All tasks in `~/LocalPackages/mai-cache` on `develop`. Start with `git status` clean and `composer test-unit` passing.

### Task 1: Injectable clock and real expiry in the test store

**Files:**
- Modify: `src/Cache.php`
- Modify: `tests/Support/ArrayStore.php`
- Test: `tests/Unit/ClockTest.php` (new)

**Interfaces:**
- Produces: `Cache::now(): int` (public static; returns the clock's time, `time()` by default). `Cache::set_clock( ?\Closure $clock ): void` (public static; null restores `time()`). `Cache::reset_runtime()` also resets the clock.
- Produces: `ArrayStore` honours `$expire` using `Cache::now()`: a read at or after `write time + $expire` returns `false`; `$expire = 0` never expires.

- [ ] **Step 1: Write the failing tests** in `tests/Unit/ClockTest.php`: `test_now_defaults_to_time` (within 1 second of `time()`), `test_set_clock_overrides_now` (`set_clock( fn() => 1000 )` then `now() === 1000`), `test_reset_runtime_restores_clock`, `test_array_store_expires` (write with expire 10 at clock 1000; read at 1009 returns the value; at 1010 returns `false`), `test_array_store_zero_never_expires`.
- [ ] **Step 2: Run** `composer test-unit -- --filter ClockTest`. Expected: FAIL, `now` undefined.
- [ ] **Step 3: Implement** a private static `?\Closure $clock` on `Cache`, `now()`, `set_clock()`, and the reset. `ArrayStore` keeps an `$expires` map alongside `$data`.
- [ ] **Step 4: Run** `composer test-unit`. Expected: all pass.
- [ ] **Step 5: Commit:** `git -C ~/LocalPackages/mai-cache add -A src tests` then `git -C ~/LocalPackages/mai-cache commit -m "Add an injectable clock, and real expiry in the test store"`. Every commit in this plan stages new files explicitly; `commit -am` misses them.

### Task 2: `bump()` writes an envelope

**Files:**
- Modify: `src/Cache.php` (`bump()`, around line 319)
- Test: `tests/Unit/SwrTest.php`

**Interfaces:**
- Produces: `bump()` writes `[ '_v' => null, 'value' => <new token>, 'w' => Cache::now() ]` to `key( '__v_' . $scope )` with expiry 0, directly through `$this->store->write()`, never through `put()`, so it still runs when `can_cache()` is false.

- [ ] **Step 1: Write the failing tests:** `test_bump_token_is_what_version_returns` (bump, then `version( [ 'post' ] )` returns the bumped token and the store saw exactly one write for that key; count writes with a counting store); `test_bump_writes_even_when_cannot_cache` (store `available()` false: the write still happens). Keep the existing bypass test at `SwrTest.php:56-81` passing.
- [ ] **Step 2: Run** `composer test-unit -- --filter SwrTest`. Expected: the first FAILs (a second write mints a new token).
- [ ] **Step 3: Implement** the envelope write in `bump()`.
- [ ] **Step 4: Run** `composer test-unit`. Expected: all pass.
- [ ] **Step 5: Commit** `"bump() writes the token in the envelope version() reads"`.

### Task 3: Soft and hard expiry

**Files:**
- Modify: `src/Cache.php` (`write_swr()`, `read_swr()`, `put()` as needed)
- Test: `tests/Unit/SoftHardExpiryTest.php` (new)

**Interfaces:**
- Consumes: `Cache::now()` (Task 1).
- Produces: `write_swr( string $key, mixed $value, string $version, int $ttl, ?int $hard_ttl = null ): bool`. Envelope `[ '_v' => $version, 'value' => $value, 'w' => now, 's' => now + $ttl ]`, store expiry `max( $ttl, $hard_ttl ?? $ttl )`. When `$ttl <= 0`, leave `s` out (no soft deadline), so a 0 lifetime never reads as age-stale.
- Produces: `read_swr( string $key, string $version ): ?array` returning `[ 'value' => mixed, 'fresh' => bool, 'stale' => null|'version'|'age', 'written' => ?int ]`. `stale` is `'version'` when `_v` differs (wins over age), `'age'` when `s` is set and `now >= s`, else null. `fresh` is `null === stale`. `written` is `w` or null. Entries without `s` are never age-stale.

- [ ] **Step 1: Write the failing tests** (ArrayStore, clock at 1000): `test_fresh_before_soft` (ttl 100, hard 1000, read at 1099: fresh, stale null, written 1000); `test_age_stale_after_soft` (read at 1100: fresh false, stale `'age'`, value intact); `test_gone_after_hard` (read at 2000: null); `test_version_wins_over_age` (bump then read at 1100: `'version'`); `test_write_resets_both`; `test_hard_below_soft_is_raised` (ttl 100, hard 50: still readable at 1099); `test_null_hard_behaves_like_040` (ttl 100, no hard: gone at 1100); `test_entry_without_s_is_never_age_stale` (write a raw `[ '_v' => v, 'value' => x ]` envelope: fresh at any time while stored); `test_zero_ttl_has_no_soft_deadline`.
- [ ] **Step 2: Run** `composer test-unit -- --filter SoftHardExpiryTest`. Expected: FAIL.
- [ ] **Step 3: Implement.** Keep existing callers of `read_swr()` working: `value` and `fresh` keep their meaning.
- [ ] **Step 4: Run** `composer test-unit`. Expected: all pass, including the existing `SwrTest`.
- [ ] **Step 5: Commit** `"Soft and hard lifetimes for versioned entries"`.

### Task 4: Clean up orphaned rows on flush

**Files:**
- Create: `src/PrefixDelete.php`
- Modify: `src/Cache.php` (`flush()`), `src/TransientStore.php`, `src/ObjectCacheStore.php`
- Test: `tests/Unit/FlushCleanupTest.php` (new)

**Interfaces:**
- Produces: `interface Mai\Cache\PrefixDelete { public function delete_prefix( string $prefix ): int; }` (rows deleted).
- Produces: `flush()` computes the old key prefix before rotating, rotates as today, then calls `delete_prefix( $old_prefix )` when the store implements `PrefixDelete`. Old prefix: grouped `"{prefix}_" . SCHEMA . "_{root token}_{group}_{old group token}_"`, ungrouped `"{prefix}_" . SCHEMA . "_{old root token}_"`. Still not gated by `can_cache()`.
- Produces: `TransientStore::delete_prefix()` returns 0 without touching the database when `wp_using_ext_object_cache()` is true (transients live in the object cache then). Otherwise it loops `$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s LIMIT 1000", $wpdb->esc_like( '_transient_' . $prefix ) . '%', $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' ) )` until it deletes 0 rows, then `wp_cache_delete( 'alloptions', 'options' )`, and returns the total.
- Produces: `ObjectCacheStore::delete_prefix()` returns 0.

- [ ] **Step 1: Write the failing tests** with a fake store implementing `Store` and `PrefixDelete` that records calls: `test_group_flush_deletes_old_group_prefix` (the recorded prefix equals the group key prefix before the flush, and a key written before the flush starts with it); `test_root_flush_deletes_old_root_prefix`; `test_flush_without_prefix_delete_still_rotates` (plain ArrayStore: a value written before reads as a miss after). The real SQL is tested in Mai Engine's integration suite (Task 8).
- [ ] **Step 2: Run** `composer test-unit -- --filter FlushCleanupTest`. Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** `composer test-unit`. Expected: all pass.
- [ ] **Step 5: Commit** `"Delete the old token's rows when a scope is flushed"`.

### Task 5: Version 0.5.0 (local only)

**Files:**
- Modify: `init.php:98` (register `'0.5.0'`), `CHANGES.md` (new `## [0.5.0] - TBD` entry: Added soft and hard lifetimes, `write_swr()` hard lifetime argument, `read_swr()` `stale` and `written`, injectable clock, `PrefixDelete`; Fixed `bump()` double write and token race, orphaned rows after a flush), `README.md` (document the new `write_swr()` argument and `read_swr()` keys).

- [ ] **Step 1: Make the edits.** No em-dashes.
- [ ] **Step 2: Run** `composer test-unit`. Expected: all pass.
- [ ] **Step 3: Commit** `"mai-cache 0.5.0"`. Do not tag or push. Tagging happens in Task 15 with Mike's yes.

---

## Phase B: Mai Engine

### Task 6: Merge the kept-only branch

**Files (in the worktree `~/Plugins/mai-engine-grid-loading`):**
- Modify: `lib/classes/class-mai-grid.php` (remove the strategy switch around lines 270-330, the `prime_late` paths, and comments naming the strategies)
- Modify: `tests/phpunit/integration/GridLoadStrategyTest.php` (rename to `GridKeptOnlyTest.php`), `tests/phpunit/unit/MaiQueryCacheKeepTest.php` as needed

**Interfaces:**
- Produces: kept-only is the only strategy; the fallback path (filter after the query) stays for every case where kept-only steps aside. Keep `prime_shown_posts()` and call it for every deferring grid: its docblock (`class-mai-grid.php:1007-1013`) explains kept-only still needs it for the fallback and for posts a `the_posts` callback adds.

- [ ] **Step 1: Preflight.** In `~/Plugins/mai-engine`: `git fetch origin`, then `git status -sb` shows `develop` is not behind `origin/develop` and the tree is clean. Being ahead is expected: the unpushed docs commit `0e9306491` (spec, idea, plan, `STATE.md`) and any fix commits after it. If `develop` is behind, or the docs commit is missing, stop and ask Mike.
- [ ] **Step 2: Rewrite the tests first.** Replace the `strategies` data provider with kept-only plus fallback cases (fallback: another `posts_pre_query` callback answers first; the copy is declined). Add a beta.4-shaped entry test. No existing test writes one by hand, and after Task 7 `write_swr()` adds `w` and `s`, so the test writes the old shape itself: `set_transient( mai_cache( 'grid' )->key( $key ), [ '_v' => $version, 'value' => [ 'ids' => $padded_ids, 'found' => count( $padded_ids ) ] ], HOUR_IN_SECONDS )`. Assert it is served correctly.
- [ ] **Step 3: Remove** the `mai_grid_load_strategy` filter, its `// TEMPORARY` comment, and the `prime_late` code.
- [ ] **Step 4: Run** both suites in the worktree. Expected: all pass. `grep -rn "prime_late\|mai_grid_load_strategy" lib tests` returns nothing.
- [ ] **Step 5: Commit on the branch** `"Keep kept-only as the only grid load strategy"`.
- [ ] **Step 6: Merge.** In `~/Plugins/mai-engine`: `git merge --no-ff feat/grid-load-kept-posts -m "Merge branch 'feat/grid-load-kept-posts' into develop"`. `STATE.md` will conflict (the branch changes it too). Keep `develop`'s version, which points at this plan:

```bash
git checkout --ours STATE.md
git add STATE.md
git commit --no-edit
```
- [ ] **Step 7: Run** both suites on `develop`. Expected: all pass.
- [ ] **Step 8: Hand Mike the cleanup commands** (auto mode blocks them):

```bash
git -C ~/Plugins/mai-engine worktree remove ~/Plugins/mai-engine-grid-loading
git -C ~/Plugins/mai-engine branch -d feat/grid-load-kept-posts
```

### Task 7: Bundle local mai-cache 0.5.0

**Done, do not repeat (2026-10-03).** mai-cache no longer has `init.php`, so these steps would now fail, and copying files by hand would leave `autoload_files.php` pointing at a deleted file. Task 15 replaces the copy through Composer.

**Files:**
- Modify: `vendor/maithemewp/mai-cache/` (copy of `~/LocalPackages/mai-cache` `src/`, `init.php`, `CHANGES.md`, `README.md`, `composer.json`)

- [ ] **Step 1: Copy,** with absolute paths:

```bash
rsync -a --delete ~/LocalPackages/mai-cache/src/ ~/Plugins/mai-engine/vendor/maithemewp/mai-cache/src/
cp ~/LocalPackages/mai-cache/init.php ~/LocalPackages/mai-cache/CHANGES.md ~/LocalPackages/mai-cache/README.md ~/LocalPackages/mai-cache/composer.json ~/Plugins/mai-engine/vendor/maithemewp/mai-cache/
```

  mai-cache loads its classes through its own autoloader in `init.php`, so `vendor/composer` does not change.
- [ ] **Step 2: Run** both suites. Expected: all pass (0.5.0 is call-compatible: 4-argument `write_swr()`, `read_swr()` keeps `value` and `fresh`). Fix any unit stub in `tests/phpunit/unit/MaiQueryCacheSingleFlightTest.php` that builds a read result by hand only if a later task needs the new keys.
- [ ] **Step 3: Commit:** `git add vendor/maithemewp/mai-cache` (this stages the new `src/PrefixDelete.php` too), then commit `"Bundle mai-cache 0.5.0 from local develop"`. Task 15 replaces this with the tagged release through Composer.

### Task 8: Fixes from spec section 5, and flush cleanup on MySQL

**Files:**
- Modify: `lib/classes/class-mai-query-cache.php` (`cache_key()`, `is_cacheable()`, `hydrate()`, `on_transition()`, `on_delete()`, new `posts_results()`), `lib/functions/query-cache.php` (register `posts_results` at `PHP_INT_MIN`)
- Test: `tests/phpunit/unit/MaiQueryCacheKeyTest.php`, `MaiQueryCacheabilityTest.php`, `MaiQueryCacheHydrateTest.php`, `MaiQueryCacheInvalidationTest.php`; `tests/phpunit/integration/GridCacheFailedQueryTest.php` (new); `tests/phpunit/integration/MaiCacheFlushCleanupTest.php` (new)

**Interfaces:**
- Produces: `cache_key()` strips the placeholder escape with `$wpdb->remove_placeholder_escape()` from the SQL and, recursively, from string query var values that contain `$wpdb->placeholder_escape()`, as core does (`class-wp-query.php:5076-5097`). Guard with `isset( $GLOBALS['wpdb'] )`: the 12 unit tests in `MaiQueryCacheKeyTest` run without `$wpdb`.
- Produces: `is_cacheable()` returns false when `fields` is `'ids'` or `'id=>parent'`.
- Produces: `hydrate()` drops posts whose post type is not in the query's `post_type` (string or array; skip the check for `'any'` or empty).
- Produces: `on_transition()` and `on_delete()` treat `private` like `publish`. Every post type except revisions still rotates.
- Produces: `Mai_Query_Cache::posts_results( $posts, $query )` at `PHP_INT_MIN`: when the query is flagged to store and `$wpdb->last_error` is set and `$wpdb->last_query === $query->request`, clear the store flags so nothing is stored.

- [ ] **Step 1: Write the failing tests:**
  - `test_placeholder_escape_does_not_change_key` (two calls with different placeholder strings around a `LIKE '%foo%'`, in SQL and in a `s` query var: equal keys).
  - `test_fields_ids_not_cacheable` and `test_fields_id_parent_not_cacheable`.
  - `test_hydrate_drops_wrong_post_type` (a page ID in a `post` grid's list is dropped; `'any'` keeps it).
  - `test_private_transition_rotates` (`private` to `private`, `draft` to `private`, `private` to `trash` all bump); `test_delete_private_rotates`.
  - Integration `test_failed_query_is_not_stored` (force an SQL error through a `posts_where` filter on a cacheable grid: nothing stored, the next request runs the query again).
  - Integration `MaiCacheFlushCleanupTest`: write 2500 grid entries without a persistent object cache, `flush_all()`, and assert every `_transient_` and `_transient_timeout_` row under the old grid prefix is gone (more than one batch ran), rows of another group and unrelated options are untouched, and `alloptions` no longer holds old version rows. Repeat for the root flush used by `lib/admin/upgrade.php:58`.
- [ ] **Step 2: Run** the new tests. Expected: FAIL.
- [ ] **Step 3: Implement** each fix.
- [ ] **Step 4: Run** both suites. Expected: all pass.
- [ ] **Step 5: Commit** one commit per fix, plain messages (for example `"Do not cache grid results from a failed query"`).

### Task 9: Lifetimes, the marker, and the fetch and store split

**Files:**
- Modify: `lib/classes/class-mai-query-cache.php`, `lib/classes/class-mai-grid.php`, `lib/functions/query-cache.php`
- Test: `tests/phpunit/integration/GridCacheStoreTest.php` (new)

**Interfaces:**
- Produces: `private function lifetimes( array $query_vars ): array` returning `[ int $soft, int $hard ]`: soft from `mai_query_cache_ttl` (default `self::TTL`), hard from `mai_query_cache_hard_ttl` (default `DAY_IN_SECONDS`), hard raised to at least soft. Add `defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );` next to the existing `HOUR_IN_SECONDS` fallback (`class-mai-query-cache.php:18`): `MaiQueryCacheInvalidationTest.php:145` reaches `store()` in the unit suite.
- Produces: `private function store( string $key, string $version, array $value, int $soft, int $hard ): void`. `$value` is `[ 'ids' => int[], 'found' => int ]` plus `'by' => 'ids'` when the ID copy built it. Calls `write_swr( $key, $value, $version, $soft, $hard )`. (Task 10 routes it through the queue.)
- Produces: `private function fetch_ids( array $args, bool $cache_results, string $expected_key ): array|false|null`. Returns `[ 'ids' => int[], 'found' => int ]`, `false` when the copy's own statement failed (caller clears its store flags, as the branch does at `:511`), or `null` when the copy cannot stand in (selects more than the ID, key differs from `$expected_key`, or an empty result whose failure could be hidden because another plugin rewrote the statement). An empty, error-free result is a real answer and is stored (changed after local verification, 2026-10-02). The `no_found_rows` check moves to the live caller.
- Produces: `pre_query_kept()` stores with `'by' => 'ids'`; `the_posts()` stores without it.
- Produces: `Mai_Grid::add_deferred_orderby_tiebreaker( $orderby, $query )` becomes `public static`, registered once in `mai_register_query_cache()` through the wrapper `mai_add_grid_orderby_tiebreaker()` (`add_filter( 'posts_orderby', 'mai_add_grid_orderby_tiebreaker', 99, 2 )`), which checks `mai_grid_tiebreak` before touching Mai_Grid (changed after the final review, 2026-10-02). Remove the add and remove calls in `Mai_Grid::get_query()`. It stays gated by `mai_grid_tiebreak`.

- [ ] **Step 1: Write the failing tests:** `test_store_uses_soft_and_hard_lifetimes` (filter both; the envelope's `s` and the store expiry match); `test_kept_only_store_has_marker`; `test_the_posts_store_has_no_marker` (a non-deferring grid); `test_tiebreaker_registered_once` (render three deferring grids: `has_filter( 'posts_orderby', [ 'Mai_Grid', 'add_deferred_orderby_tiebreaker' ] )` is 99 and the callback appears once); `test_tiebreaker_ignores_other_queries` (a query without `mai_grid_tiebreak` gets no `wp_posts.ID` added).
- [ ] **Step 2: Run** them. Expected: FAIL.
- [ ] **Step 3: Implement.** Existing kept-only tests must keep passing unchanged. Delete `test_tiebreaker_filter_does_not_survive_the_grid_that_added_it` (`tests/phpunit/integration/GridDeferredExcludesTest.php:325-339`): it checks the per-grid removal this task replaces, and would pass without testing anything.
- [ ] **Step 4: Run** both suites. Expected: all pass.
- [ ] **Step 5: Commit** `"Grid cache: hard lifetime, a marker for ID-built entries, and a fetch that does not need the live query"`.

### Task 10: The queue, finishing the response, and deferred stores

**Files:**
- Create: `lib/classes/class-mai-query-cache-queue.php`
- Modify: `lib/classes/class-mai-query-cache.php`, `lib/functions/query-cache.php`
- Test: `tests/phpunit/unit/MaiQueryCacheQueueTest.php` (new), `tests/phpunit/integration/GridCacheDeferredStoreTest.php` (new)

**Interfaces:**
- Produces: `class Mai_Query_Cache_Queue` with:
  - `__construct( ?callable $can_finish = null, ?callable $finish = null, ?callable $no_page_cache = null, ?callable $is_page_view = null )`. Defaults: `$can_finish` returns `function_exists( 'fastcgi_finish_request' ) || function_exists( 'litespeed_finish_request' )`; `$finish` calls whichever exists; `$no_page_cache` defines `DONOTCACHEPAGE` as true only when it is not already defined (the `defined()` check lives inside this default, so a test's recording callable is always called); `$is_page_view` is the page-view test below.
  - `is_page_view(): bool`: first `( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'GET'`, then not `is_admin()`, `wp_doing_ajax()`, `wp_is_serving_rest_request()` (or the `REST_REQUEST` constant on WordPress before 6.5), `wp_doing_cron()`, the `WP_CLI` constant, the `XMLRPC_REQUEST` constant, `is_feed()`, or `is_multisite() && ms_is_switched()` (`ms_is_switched()` only exists on multisite, `wp-settings.php:157-161`).
  - `can_finish_early(): bool`: `is_page_view()` and the `$can_finish` seam.
  - `add_store( string $key, string $version, array $value, int $soft, int $hard ): void` and `pending( string $key ): ?array` (returns `[ 'version', 'value', 'soft', 'hard' ]`).
  - `add_job( array $job ): void` (ignored when the key is already queued), `has_job( string $key ): bool`, `jobs(): array` (sorted by `written` ascending, null first).
  - `mark_rebuilt( string $key ): void`, `was_rebuilt( string $key ): bool`.
  - `no_page_cache(): void`: when `is_page_view()`, calls the seam.
  - `ignore_user_abort( true )` is called once, when the first store or job is added (as the spec says), not in `finish()`.
  - `finish(): void`: once, calls `session_write_close()` when `session_status() === PHP_SESSION_ACTIVE`, then the `$finish` seam, records `hrtime( true )` as the start of after-the-page work, and sets a flag.
  - `finished(): bool` (the flag) and `elapsed_ms(): float` (since `finish()`, from `hrtime( true )`; never `Cache::now()`, which counts whole seconds and is frozen in tests).
  - `is_empty(): bool`, `take_stores(): array` (returns and clears pending stores).
- Produces: `Mai_Query_Cache::__construct( ?Mai_Query_Cache_Queue $queue = null )` (new queue by default), `public static function instance(): Mai_Query_Cache` (the one instance the hooks use), `public function set_queue( Mai_Query_Cache_Queue $queue ): void` (tests install a fresh queue per pretend request in `set_up`), and `public function run_queue(): void`, registered as `add_action( 'shutdown', [ $cache, 'run_queue' ], PHP_INT_MAX )`. `mai_register_query_cache()` uses `Mai_Query_Cache::instance()` instead of a local `new`; do not add a typed parameter to it (`do_action( 'init' )` passes `''`). In this task `run_queue()` returns at once when the queue is empty, else calls `finish()`, then writes every pending store with `write_swr()`. Jobs come in Task 11.
- Produces: `store()` writes inline when `wp_using_ext_object_cache()` is true, or `can_finish_early()` is false, or the queue is already `finished()` (so a job's store at shutdown is written, not left in a list nobody reads); otherwise it calls `add_store()`. Either way it calls `mark_rebuilt( $key )`.
- Produces: `pre_query()` checks `pending( $key )` before `read_swr()` and serves it (through `serve()` with `$keep`) when present.

- [ ] **Step 1: Write the failing unit tests** (`MaiQueryCacheQueueTest`): `test_page_view_rules` (each excluded request type returns false; the `WP_CLI`, `XMLRPC_REQUEST` and `REST_REQUEST` constant cases each run in their own process with `#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]`, because a defined constant cannot be undefined); `test_single_site_does_not_call_ms_is_switched`; `test_can_finish_early_needs_page_view_and_finish_function`; `test_add_job_once_per_key`; `test_jobs_oldest_first_null_first`; `test_no_page_cache_only_on_page_view`; `test_default_no_page_cache_skips_when_defined` (separate process: define the constant first, call the default hook, no error); `test_finish_runs_once`; `test_ignore_user_abort_on_first_add`; `test_elapsed_uses_real_time`.
- [ ] **Step 2: Write the failing integration tests** (`GridCacheDeferredStoreTest`, no persistent object cache, a fresh queue per test via `Mai_Query_Cache::instance()->set_queue( new Mai_Query_Cache_Queue( fn() => true, fn() => null, $recorder ) )`). The suite sets `REQUEST_METHOD` to `GET` (wp-phpunit `includes/functions.php:27`), so test queues count as page views unless told otherwise:
  - `test_cold_miss_store_is_deferred` (after the grid renders nothing is stored; after `run_queue()` the entry exists with the version read before the query).
  - **`test_save_between_query_and_deferred_store_leaves_entry_stale`**: render a cold grid, publish a post in its post type, run `run_queue()`, then read: `stale === 'version'`.
  - `test_second_grid_same_key_uses_pending` (two identical grids on one request: one grid query, both show the same IDs).
  - `test_store_inline_when_cannot_finish_early` (finish seam false, and separately `is_page_view` false: stored before `run_queue()`).
  - `test_store_inline_with_persistent_object_cache`: in `set_up` call `wp_using_ext_object_cache( true )` before any cache read, then `\Mai\Cache\Cache::reset_runtime()`; restore both in `tear_down`. Stored inline. The single-flight unit test `tests/phpunit/unit/MaiQueryCacheSingleFlightTest.php` still passes.
  - `test_pending_stores_written_before_jobs` (finish, then the store is in the cache before any job reads it).
  - `test_store_after_finish_is_inline` (a `store()` call once `finished()` is true writes straight away).
- [ ] **Step 3: Run** both. Expected: FAIL.
- [ ] **Step 4: Implement.**
- [ ] **Step 5: Run** both suites. Expected: all pass.
- [ ] **Step 6: Commit** `"Save grid results after the page on sites without a persistent object cache"`.

### Task 11: Rebuild aged-out notes after the page

**Files:**
- Modify: `lib/classes/class-mai-query-cache.php`
- Test: `tests/phpunit/integration/GridCacheAfterPageTest.php` (new)

**Interfaces:**
- Consumes: `read_swr()` `stale` and `written` (Task 3), `fetch_ids()`, `store()`, `lifetimes()` (Task 9), the queue (Task 10).
- Produces: in `pre_query()`, after the pending check and the cold path, for a stale hit:
  1. If `has_job( $key )`: serve stale, return.
  2. If `stale === 'age'` and `$keep` is non-null and `'ids' === ( $hit['value']['by'] ?? null )` and `can_finish_early()` and `apply_filters( 'mai_query_cache_after_page', true )`: compute `$served = serve(...)`. If null, fall through to step 3. Else `add_job()` with `key`, `version` (the one just read), `written` (`$hit['written']`), `args` (a copy of `$query->query`), `cache_results` (`$keep['cache_results']`), `soft` and `hard` (from `lifetimes( $query->query_vars )`), `blog_id` (`get_current_blog_id()`), then return `$served`.
  3. Otherwise today's path: the `lock()` winner calls `flag_miss()`; a loser is served stale.
- Produces: `private function run_job( array $job ): void` and the job loop in `run_queue()`: after pending stores, take `jobs()`; before each, stop when `elapsed_ms() >= mai_query_cache_after_page_ms` (default 1000). Each job: skip if `get_current_blog_id() !== $job['blog_id']`; skip if `was_rebuilt( $key )`; skip if `! mai_cache( 'grid' )->lock( $key, $this->lock_ttl() )`; `$r = fetch_ids( $job['args'], $job['cache_results'], $job['key'] )`; on an array, `store()` it with `'by' => 'ids'` under `$job['version']` and the captured lifetimes; on `false` or `null`, `mai_cache( 'grid' )->delete( $key )`. Log the duration with `error_log()` when `WP_DEBUG_LOG` is on.

- [ ] **Step 1: Write the failing tests** (a fresh test queue per pretend request as in Task 10, clock moved past the soft lifetime with `\Mai\Cache\Cache::set_clock()`, and `\Mai\Cache\Cache::set_clock( null )` in `tear_down`, because `MaiIntegrationTestCase` never resets it):
  - `test_age_stale_marked_entry_is_served_and_queued` (no grid query during render; one job).
  - `test_job_rebuilds_after_query_restored` (render, then confirm Mai_Grid restored `posts_per_page` and removed `mai_grid_tiebreak` from the live query, then `run_queue()`: entry is fresh, written now, same IDs a during-page rebuild stores).
  - `test_no_drift` (for three grid shapes: fixed category, exclude displayed, and a views-sorted grid built with `orderby => meta_value_num` on a views meta key, since Mai Publisher is not in the suite: the job's stored `ids` equal those from a cold during-page rebuild of the same grid).
  - `test_save_between_read_and_job_leaves_entry_stale`.
  - `test_version_stale_is_rebuilt_during_page` (bump: one grid query during render, nothing queued).
  - `test_not_queued_without_marker`, `test_not_queued_for_non_kept_grid`, `test_not_queued_when_filter_off`, `test_not_queued_when_cannot_finish_early`, `test_not_queued_when_not_a_page_view`, `test_not_queued_for_beta4_entry` (write the old shape by hand, as in Task 6), `test_malformed_entry_rebuilds_during_page`.
  - `test_same_key_twice_one_query` (two identical grids, age-stale: one job, no grid query during render; and version-stale: one query).
  - `test_decline_deletes_entry` (a `posts_request` filter that changes the SQL only at shutdown: entry deleted, nothing wrong stored).
  - `test_job_skipped_on_other_blog` (works on a single site: add a job by hand to a test queue with `blog_id` set to `get_current_blog_id() + 1`; it does not run).
  - `test_job_skipped_when_key_already_rebuilt` (`mark_rebuilt()` the key, then run the job: no query).
  - `test_job_skipped_when_lock_held`.
  - `test_budget_stops_jobs` (budget filtered to 0: no job runs, because the loop stops when `elapsed_ms() >= budget`; on the next pretend request, with a fresh queue, the entries are queued again).
- [ ] **Step 2: Run** them. Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** both suites. Expected: all pass.
- [ ] **Step 5: Commit** `"Rebuild aged-out grid results after the page is sent"`.

### Task 12: `DONOTCACHEPAGE` for lock losers on a saved post's grid

**Files:**
- Modify: `lib/classes/class-mai-query-cache.php`
- Test: `tests/phpunit/integration/GridCacheDoNotCacheTest.php` (new)

**Interfaces:**
- Produces: in `pre_query()` step 3, when a `lock()` loser is served an entry with `stale === 'version'`, call `$this->queue->no_page_cache()`. Never for fresh or age-stale reads.

- [ ] **Step 1: Write the failing tests** with a fresh test queue holding a recording `$no_page_cache` callable, and `wp_using_ext_object_cache( true )` then `\Mai\Cache\Cache::reset_runtime()` in `set_up` (restore in `tear_down`): `test_version_stale_loser_marks_page` (take the lock first with `mai_cache( 'grid' )->lock()`, then render: seam called once); `test_winner_does_not_mark_page`; `test_age_stale_never_marks_page`; `test_not_marked_outside_page_view`. One more in a separate process without the seam: the real constant is defined after a loser render.
- [ ] **Step 2: Run** them. Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** both suites. Expected: all pass.
- [ ] **Step 5: Commit** `"Keep page caches from saving a page served an out-of-date grid"`.

### Task 13: Changelog and docs

**Files:**
- Modify: `CHANGES.md` (under `## 2.41.0 (TBD)`, in the existing Added, Changed, Fixed order)

- [ ] **Step 1: Write entries,** plain language, no em-dashes. Suggested content:
  - Changed: [Performance] Post grids using "Exclude current" or "Exclude displayed" now load only the entries they show, instead of every entry they asked for to cover the excluded ones.
  - Changed: [Performance] When a cached grid is more than 4 hours old and nothing has been published since, visitors get it instantly and it is refreshed after the page is sent, on PHP-FPM and LiteSpeed hosts. Grids are still refreshed straight away when a post is saved.
  - Changed: [Performance] On sites without a persistent object cache, cached grid results are saved after the page is sent.
  - Changed: [Developers] Cached grid results now last up to 24 hours (`mai_query_cache_hard_ttl`), refreshed after 4 (`mai_query_cache_ttl`). New `mai_query_cache_after_page` and `mai_query_cache_after_page_ms` filters.
  - Changed: Pages showing a grid that is being refreshed after a save are not saved by page cache plugins that honour `DONOTCACHEPAGE`.
  - Fixed: Saving a private post now refreshes grids that show private posts.
  - Fixed: Grids whose query uses `LIKE` were never served from the cache.
  - Fixed: A grid whose query failed could cache an empty result.
  - Fixed: Changing a post's type left it in cached grids for the old type.
  - Fixed: Old cached grid rows are removed from the database when the cache is flushed, on sites without a persistent object cache.
- [ ] **Step 2: Commit** `"Changelog for the grid cache work"`.

### Task 14: Local verification

Follow the spec's "Verification on local sites" section. Concrete procedure:

- [ ] **Step 1: Prepare a beta.4 copy for comparisons** without a worktree: `rm -rf /tmp/mai-engine-beta4 && mkdir /tmp/mai-engine-beta4 && git -C ~/Plugins/mai-engine archive 1bb05a8fa | tar -x -C /tmp/mai-engine-beta4`. eurweb and larrybrownsports symlink `wp-content/plugins/mai-engine` to `~/Plugins/mai-engine`. To compare, repoint one site's symlink at a time to `/tmp/mai-engine-beta4`, run, and repoint back. Reload PHP-FPM after each switch (`kill -USR2` on the pool master) and probe that the expected code is live before trusting numbers.
- [ ] **Step 2: For every speed test, protect `wp-config.php`:**

```bash
cp wp-config.php /tmp/<site>-wp-config.bak
wp config set WP_DEVELOPMENT_MODE ''
wp config set SCRIPT_DEBUG false --raw
# run the test
cp /tmp/<site>-wp-config.bak wp-config.php
cmp wp-config.php /tmp/<site>-wp-config.bak
```

  `cmp` must print nothing. Never restore with `wp config set`.
- [ ] **Step 3: Measure** with a temporary mu-plugin that logs each grid query (count) and each job's duration to a file. Delete it afterwards. Time to first byte with `curl -s -o /dev/null -w '%{time_starttransfer}'`, medians of at least 20 runs, bypassing WP Rocket with a query string it does not cache or a subscriber's cookie.
- [ ] **Step 4: Run each check in the spec** (eurweb timings; WP Rocket after a save; busy site saving every 30 seconds for 10 minutes, beta.4 against beta.5; larrybrownsports expiry concurrency; two small sites without Redis, churn; visitsleepyhollow event grids; identical rendered IDs with the cache on and off).
- [ ] **Step 5: Write the results** into the spec under a new "Results" heading (numbers, not adjectives) and commit `"Grid cache beta.5: local verification results"`. If any check fails its bar, stop and tell Mike before release.
- [ ] **Step 6: Clean up:** symlinks point at `~/Plugins/mai-engine`, mu-plugin deleted, `rm -rf /tmp/mai-engine-beta4`.

### Task 15: Release (every push and tag needs Mike's yes)

The beta goes out with the repo's own `npm run beta` (`package.json:74`), as 2.41.0-beta.4 did. It runs a preflight, `gulp build`, `composer dump-autoload --no-dev`, commits "Beta release", runs `deployable-guard check`, pushes `develop`, merges into `beta`, and pushes `beta`. Its preflight refuses to run with uncommitted changes outside `assets`, `vendor`, `mai-engine.php`, `CHANGES.md` and `readme.txt`, so commit everything else first.

- [ ] **Step 1: mai-cache is already released (changed 2026-10-03).** Never tag `v0.5.0`. mai-cache shipped as `v0.6.0`: 0.5.0's code, loaded by mai-package-loader instead of its own bootstrap, and numbered 0.6.0 so it wins over this plugin's 2.40 copy, which registers itself as 0.5.0. Its `src/` differs from the copy Task 7 bundled only by five dropped `ABSPATH` guard lines.
- [ ] **Step 2: In Mai Engine:** set `"maithemewp/mai-cache": "^0.6"` in `composer.json` and add `{ "type": "vcs", "url": "https://github.com/maithemewp/mai-package-loader" }` to `repositories`. Without that entry Composer cannot find the loader and silently keeps the old version. Remove the `/vendor/composer/installed.php` line from `.gitignore`: the loader reads that file, and deployable-guard v1.1.0 fails CI without it. Run `composer update maithemewp/mai-cache maithemewp/mai-package-loader`, then **`composer dump-autoload --no-dev`**. A dev autoloader committed to `vendor/composer` crashes every site (it happened before: a876863db, fixed by 4c8c6131e). Check `vendor/composer/autoload_files.php` lists `maithemewp/mai-package-loader/init.php` and no longer `maithemewp/mai-cache/init.php`, and `composer.lock` holds mai-cache `v0.6.0` and mai-package-loader `v0.1.0`. Fix the `Mai_Cache_Bootstrap` comments in `tests/phpunit/integration/plugin-loader.php` and `tests/phpunit/unit/bootstrap.php`. Run both suites, and `php vendor/bin/deployable-guard check`. Commit `"Require mai-cache 0.6, loaded by mai-package-loader"`, including `vendor/composer/installed.php` and `vendor/maithemewp/mai-package-loader/`.
- [ ] **Step 3: Set the version in docblocks:** replace every `@since TBD` with `@since 2.41.0` (`grep -rn "@since TBD" lib` then prints nothing). Commit `"Set @since for 2.41.0"`.
- [ ] **Step 4: Rewrite `STATE.md`** for the released state (Now, Next, Blocked, Verify, Gotchas; about 40 lines). Commit.
- [ ] **Step 5: Prepare the beta files,** uncommitted: plugin header `Version: 2.41.0-beta.5` in `mai-engine.php`, then `composer i18n` (writes `assets/lang/mai-engine.pot`). `git status --short` shows only those two paths, plus anything under `assets` or `vendor` that the build touches.
- [ ] **Step 6: Ask Mike:** "Run `npm run beta` for 2.41.0-beta.5? It pushes `develop` and `beta`." Wait for an explicit yes. Then run `npm run beta` and check it ends on `develop` with `deployable-guard check` passing.
- [ ] **Step 7: Confirm** `git branch -a` shows only `develop`, `beta`, `master` and their remotes, `git worktree list` shows one entry, and `git status` is clean.
