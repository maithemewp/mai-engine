# Grid cache for 2.41.0-beta.5: load only shown posts, cheaper rebuilds

Status: agreed (2026-10-01) after three rounds of review and a walk-through with Mike. Not built.

## Why

Mai Engine 2.40.0 added a result cache for Mai Post Grid, and 2.41.0 added "deferred excludes" so pages share one cache entry instead of one per page. Benchmarks and production evidence since then show three problems:

1. **Grids that defer excludes load every padded post.** A grid that shows 6 posts but has to skip 54 loads 60 full posts, with their custom fields and categories, then throws 54 away. eurweb's front page loaded 601 posts to show 58.
2. **A visitor waits for every rebuild.** When an entry is stale or expired, the request that rebuilds it runs the grid's query while the page waits. On large sites that is 160 ms to over a second per grid, and the first visitor to a page full of expired grids waits for all of them (the overnight pile-up on larrybrownsports, July 3).
3. **Cache writes happen before the page is sent.** On small sites without a persistent object cache, a miss writes two rows to `wp_options` before the page goes out. That made busy small sites 17 to 32 ms slower than having no cache.

Production lockups on larrybrownsports (July 3) and the tmwsm22 server (eurweb, ontapsportsnet) traced to grid queries that could not share a cache entry because each page added its own `NOT IN` list. 2.41's deferred excludes fixes that.

## Goals

- A grid never makes a page slower than having no cache, on any site.
- A saved post shows on the next page view, on every site and behind every page cache, exactly as today. The first visitor after a save still waits for the query, but the query is cheaper.
- When a note only aged out (no post was saved), nobody waits: the old list, which is still correct, is shown and the note is rebuilt after the page. This applies on PHP-FPM and LiteSpeed hosts, for grids using Exclude current or Exclude displayed.
- No grid ever stores a different list from the one a normal page load would store.
- On sites without a persistent object cache, nothing is written before the page except minting a missing version token.
- During a traffic spike on a site with a persistent object cache, each grid entry is rebuilt by one request at a time, as today.

## Non-goals

- Showing an old list after a post is saved. Considered at length and rejected (2026-10-01): host and CDN page caches that ignore `DONOTCACHEPAGE` (SiteGround's server cache is one, checked in `sg-cachepress/core/Supercacher/Supercacher_Helper.php`) would keep the old list for hours.
- Invalidating by category instead of by post type. Measured and put on hold: `docs/ideas/2026-10-01-grid-cache-category-invalidation.md`.
- Rebuilding grids when a post is saved.
- Caching only slow grids, and a small-site "skip the cache read" flag.
- Skipping the stamp bump for internal post types, and turning off core's query cache on a hit. Both cut (2026-10-01): the savings were a few writes, and the post type skip could miss a type a grid uses.
- A new lock. The rebuild after the page reuses today's `lock()`.
- Changes to the post grid query optimizer. It stays off by default in 2.41.0 and is removed after.
- Faster excerpts. A later, separate change.
- Mai Galleries. Its masonry work ships separately as 1.3.0.

Everything below ships together in one beta (decided 2026-10-01).

## How it works, in plain terms

A plain-language reference for every flow. The numbered sections under "What ships" are the detail.

**The words:**

- **Note:** a grid's cached list of post IDs.
- **Query:** asking the database for that list.
- **Stamp:** a version tag on each post type that changes whenever a post of that type is saved. Each note remembers the stamp it was made with.
- **After the page:** once the visitor has their page, PHP keeps running briefly. The browser already has the whole page, so nothing in the browser waits (measured on local musekirwan: the page arrived in 0.125 s while PHP carried on for 3 s). This works on PHP-FPM and LiteSpeed, which is every BizBudding server. On other hosts there is no "after the page", and everything happens during the page, as today.

### 1. The note is current

- Mai reads the note.
- It loads only the posts the grid shows.
- The visitor gets the page.
- No query and no writes.

### 2. No note at all

This happens the first time a grid runs, after a flush, or when nobody has viewed the grid for 24 hours.

- Mai runs the query during the page load. It has to, because there is nothing to show yet.
- The visitor waits for it.
- With Redis, Mai saves the note right away (0.19 ms), so other visitors hitting the same grid at that moment can use it instead of querying.
- Without Redis, Mai saves the note after the page.

### 3. The note is out of date because a post was saved

- Mai runs the query during the page load, and the visitor sees the new post. Same as today.
- With Redis, anyone arriving while that query runs gets the old note for that moment, and their page is marked `DONOTCACHEPAGE` so WP Rocket does not keep it. Same lock as today.
- Saving the new note works as in flow 2.

### 4. The note is 4 hours old and no post was saved

For grids using Exclude current or Exclude displayed, on PHP-FPM and LiteSpeed:

- The visitor gets the old note instantly. It is still correct apart from views or trending order drifting.
- After the page, Mai runs the query once and saves a new note.
- Anyone arriving in the meantime also gets the old note.

Every other grid, or another host: flow 3, as today.

### 5. Someone saves, publishes, trashes or deletes a post

- Mai changes that post type's stamp. That is one small write. A private post now counts too.
- No notes are deleted. Any note with the old stamp now reads as out of date, which leads to flow 3.

### 6. Flush

This happens on a Mai Engine update, `wp mai flush`, or `wp cache flush`.

- Every note becomes unreachable at once, so the next visit to each grid goes through flow 2.
- New: on sites without Redis, the old notes' rows are deleted from `wp_options` instead of lingering.

### 7. Expiry

- A note older than 24 hours is deleted, and the next visit goes through flow 2.

### Limits on "after the page" work

- Each request runs a grid's query at most once, either during the page or after it, never both.
- With Redis, one rebuild per grid at a time. Without Redis there is no shared lock, so two visitors arriving at the same moment can each run the query once, as they do today.
- At most 1 second of rebuilding after any one page, oldest notes first. Anything left over is picked up by the next visitor.

## What ships

Two repos change: the cache library `maithemewp/mai-cache` (`~/LocalPackages/mai-cache`, released as 0.5.0) and Mai Engine (`~/Plugins/mai-engine`, released as 2.41.0-beta.5).

### 1. Load only the posts a deferring grid shows (Mai Engine)

Already built and reviewed on branch `feat/grid-load-kept-posts` (worktree `~/Plugins/mai-engine-grid-loading`, 18 commits on top of `develop` at `1bb05a8fa`). Summary of what that branch does:

- For a grid that defers excludes, Mai answers `posts_pre_query` itself. It gets the padded, ordered list of post IDs from the result cache, or on a miss from an internal ID-only copy of the grid's query (built from `$query->query` with `fields => 'ids'` and `mai_cache => false`; `fetch_ids()`). It stores that padded list, drops the excluded IDs, slices to the asked count, primes only the kept posts, and returns full `WP_Post` objects. `posts_results` and `the_posts` see only the shown posts.
- The miss is answered last (`PHP_INT_MAX`), so another plugin that answers `posts_pre_query` first (The Events Calendar's custom tables query at priority 100) keeps its answer, and Mai falls back to filtering after the query.
- The kept-only marker is a property on the `WP_Query` object, never a query var, so a plugin that clones the query from its vars does not inherit it.
- Excluded posts are dropped again after the query, so neither a `the_posts` callback nor sticky handling can add one back.
- A grid does not defer when `ignore_sticky_posts` is off, or when the grid cache cannot store.
- A grid query that holds the current time (The Events Calendar writes "now" into event queries) is not cached at all.

All fresh, stale and lock decisions stay in `pre_query()` at priority 10 (`class-mai-query-cache.php:260-297` on the branch). `pre_query_kept()` only answers misses and stores what `fetch_ids()` returns (`:346-350`).

Before merging, the branch's temporary benchmark switch comes out:

- Remove the `mai_grid_load_strategy` filter and its `// TEMPORARY` comment. It never shipped, so nothing needs deprecating.
- Remove the `prime_late` strategy and its code paths and comments.
- Keep kept-only as the only strategy. Keep the fallback path (filter after the query) for every case where kept-only steps aside.
- Rewrite tests that run over all three strategies (the `strategies` data provider in `GridLoadStrategyTest`, and the cross-strategy entry test) to test kept-only plus the fallback path. Keep one test that reads an entry shaped like beta.4 wrote it (padded IDs, no written time), because existing sites will have those.

Then merge the branch into `develop`, delete the branch, and remove the worktree. `develop` is the only branch work happens on from there.

### 2. Stale notes, and finishing the response (Mai Engine)

**Version-stale (a post was saved): exactly as today.** `pre_query()` lets the `lock()` winner rerun the grid's query during the request and store the result. Everyone else is served the stale entry. Without a persistent object cache, `lock()` is per-request (`WP_Object_Cache::add()` only checks this request's memory, `class-wp-object-cache.php:216`), so every request rebuilds, as today.

**A request served a version-stale entry defines `DONOTCACHEPAGE`.** On a persistent object cache, a lock loser gets the old list for the length of one rebuild. If WP Rocket's preload or a visitor refilling the page cache is that loser, it would save the old grid. WP Rocket only marks the URL for a later fetch after a purge, and only when preload is on (`wp-rocket/inc/Engine/Preload/Subscriber.php:115`, `:318`).

- Only if it is not already defined.
- Only on a normal front-end page view (defined below). This is the page-view test alone, not the finish-function check.
- WP Rocket reads it when it processes the page's output buffer, after the grids have rendered (`wp-rocket/inc/classes/Buffer/class-cache.php:155`, `class-tests.php:330` and `:747`). The `rocket_override_donotcachepage` filter can override it.
- Host caches that ignore it are no worse off than today: the window is one rebuild long.
- It is defined through a seam the tests can observe (an injectable callable), because a defined constant cannot be undefined between tests.
- An age-stale entry never defines it, since its list is still correct.

**Age-stale (no save, older than the soft lifetime): rebuilt after the page when all of these hold.** Otherwise it is handled like a version-stale entry, during the page, without `DONOTCACHEPAGE`.

- The request can finish early: a normal front-end page view and a finish function exists.
- `mai_query_cache_after_page` is on (default on).
- The grid is a kept-only request (`keep_request()` is non-null), so the job has the asked `cache_results`.
- The entry was built by the ID-only copy: its value carries `'by' => 'ids'` (see "The no-drift rule").
- `serve()` succeeded. A malformed entry falls through to `flag_miss()` and is rebuilt during the page, never queued (`class-mai-query-cache.php:294-296` on the branch).

Counting grids and grids whose query holds the current time can never carry the marker, because `fetch_ids()` refuses the first (`:490`) and the second is never cacheable (`:231`). No separate check is needed.

**One decision per key per request.** The first read of a key in a request decides its path. A later read of the same key in the same request reuses that decision: a key already rebuilt during the page is served from the pending list (below), and a key already queued is served stale and not queued again.

**Stores after the response, on sites without a persistent object cache.** A store of a result computed during the request (a cold miss or a version-stale rebuild) costs two `wp_options` writes there, so it waits until after the page.

- The result goes into a per-request list of pending writes, keyed by cache key, holding the value (with the `'by'` marker when `fetch_ids()` built it), the version read before the query ran, and the lifetimes.
- `pre_query()` checks that list before reading the cache, so a second grid with the same key on the same page is served the new result instead of running the query again.
- **The version stored is always the one read before the query ran, never one read at save time.** If a post is saved between the query and the store, the note carries the old stamp and reads as stale next time. This is what keeps a deferred store from hiding a save, and it has its own test.
- Where the request cannot finish early, the store happens at the same point as today.

**On sites with a persistent object cache, stores stay inline.** It is one `SET` (0.19 ms measured on local eurweb), and the single-flight wait depends on it: requests that lose the cold-miss lock wait up to 500 ms for the winner's stored result (`wait_for_fill()`). If the store moved to shutdown, the winner would still be rendering when the wait ran out, and every loser would run the query.

**How the response is finished:**

- A normal front-end page view is a `GET` request that is not admin, AJAX, REST, cron, WP-CLI, XML-RPC, a feed, or inside `switch_to_blog()` (`ms_is_switched()`).
- When the first job or pending store is queued, call `ignore_user_abort( true )`, so a visitor who leaves early cannot cut the work short. `wp-cron.php:19` does the same before finishing its response.
- On a `shutdown` callback at `PHP_INT_MAX`, so it runs after WordPress has flushed output buffers (`wp_ob_end_flush_all()` runs at priority 1) and after page cache plugins that save the page in an output buffer callback (WP Rocket does) have done so.
- `session_write_close()` if a PHP session is active, then `fastcgi_finish_request()` when it exists (PHP-FPM), else `litespeed_finish_request()` (LiteSpeed). WordPress core uses the same two calls in `wp-cron.php`.
- Then write the pending stores, then run the rebuild jobs.
- Where the request cannot finish early, nothing is queued. Never queue a job that would then run at `shutdown` without finishing the response, which would make the visitor wait and still show the old grid.
- Finish detection goes through a seam (a filter or injectable callable), so tests never call the real `fastcgi_finish_request()`.
- The queue runner is a public method (`Mai_Query_Cache::run_queue()`) so tests can run queued work without firing `shutdown`.

Fleet check (2026-10-01): every BizBudding server runs PHP-FPM 8.2 to 8.4. The two WP Engine sites were not checkable from a site account.

**How a rebuild after the page works:**

1. `pre_query()` serves the stale entry and queues one job for the key. It takes no lock and writes nothing before the page is sent.
2. The job captures, at that moment, everything it needs, because the query's vars are restored and its filters removed once the grid has rendered (`class-mai-grid.php:390-414` on the branch):
   - the cache key;
   - the version read in `pre_query()`, before any SQL ran;
   - the entry's written time as read (for ordering only);
   - a copy of the `$query->query` array (never a handle to `$query`), including `mai_grid_tiebreak`, and the `cache_results` value the grid asked for;
   - the soft and hard lifetimes, computed now (`mai_query_cache_ttl` receives the query vars, which Mai_Grid restores after the query);
   - the current blog ID.
3. **The ID tiebreaker filter is registered once, statically, and stays registered**, gated by the `mai_grid_tiebreak` query var as it already is. `add_deferred_orderby_tiebreaker()` is an instance method today (`class-mai-grid.php:1111`) added and removed around each grid's query, so an ID copy run at shutdown would have no `wp_posts.ID` in its `ORDER BY`, fail the key check, and decline. Make it static and register it once next to `mai_register_query_cache()`, through a small function, `mai_add_grid_orderby_tiebreaker()`, that checks the query var first so Mai_Grid only loads for grid queries (decided 2026-10-02).
4. After the response and the pending stores, jobs run oldest-written first. Before starting each job, check the time budget (`mai_query_cache_after_page_ms`, default 1000 ms of after-the-page work). Each job:
   - skips if the blog ID differs from the captured one;
   - skips if this request already rebuilt the key;
   - takes `lock()` for the key, the same lock the during-page rebuild uses (`mai_query_cache_lock_ttl`, 5 seconds, never released), and skips if it loses;
   - runs the ID-only copy from the captured args and checks its key against the captured key;
   - stores the IDs under the captured version, with `'by' => 'ids'` and the captured lifetimes;
   - on a decline, deletes the entry, releases the lock if the job still holds it (it finished at least a second inside the lock's lifetime), and logs it when `WP_DEBUG_LOG` is on. The next visitor gets a cold miss, which on a persistent object cache is single-flighted. A job that throws is handled the same way, always logged, and the next job still runs (decided 2026-10-02).
5. Each job logs how long it took when `WP_DEBUG_LOG` is on.

There is no re-read of the entry before the job runs. Inside one request, a re-read returns that request's own copy, not the store (Redis drop-in `object-cache.php:1920`; `get_option()` serves the in-request options cache, `option.php:202`), so it could never see another request's write. Correctness does not depend on it: storing under the captured version means a save during the rebuild still reads as stale.

**`fetch_ids()` and `store()` must not read the live query in the job.** On the branch, `fetch_ids()` compares the copy's key against the live grid's key (`class-mai-query-cache.php:526`), and `store()` reads `$query->found_posts` (`:650`) and passes `$query->query_vars` to `mai_query_cache_ttl`. By shutdown those are restored. Split them: the ID copy takes the args, the asked `cache_results` and the expected key, and returns the IDs and the copy's `found_posts`; the store takes the key, version, value and lifetimes. The inline path calls the same functions with values from the live query.

**The no-drift rule: a rebuild after the page must store exactly what a rebuild during the page would** (decided 2026-10-01).

- Only entries built by the ID-only copy are queued. `pre_query_kept()`'s store (`:347` on the branch) adds `'by' => 'ids'` to the value; `the_posts()`'s store (`:628`) never does. The job runs the same `fetch_ids()` with the same args, so it produces the same list.
- Every other entry, including one built by the filter-after-the-query fallback and every entry written by beta.4 or earlier, is rebuilt during the page, as today. It gets the marker on its next store if `fetch_ids()` built it.
- `serve()` and `keep()` read only `ids` and `found` (`:388-399`), so the extra key is harmless.
- Running the grid's full query after the page was considered and rejected: other plugins' `the_posts` filters and sticky handling would run at a different moment from the page, so the list could differ.
- **The key check is the safety net.** If any filter changes the SQL after the page, the copy's key differs and nothing is stored.

### 3. Soft and hard expiry (mai-cache)

Each entry gets two lifetimes:

- **Soft, 4 hours** (today's TTL, still filterable through `mai_query_cache_ttl`). After it, the entry reads as age-stale.
- **Hard, 24 hours** (new filter `mai_query_cache_hard_ttl`). This is the store's own expiry. Redis deletes the key itself; a transient is deleted on its next read (`get_transient()`, `wp-includes/option.php:1467-1469`) or by WordPress's daily `delete_expired_transients` cron.

On read, an entry is version-stale when its version no longer matches the current version, and age-stale when the version matches but it is older than its soft lifetime. Version wins when both apply. A rebuild writes a new entry, which resets both lifetimes. mai-cache raises the hard lifetime to at least the soft lifetime.

**The API:**

- `write_swr( string $key, mixed $value, string $version, int $ttl, ?int $hard_ttl = null ): bool`. `$ttl` is the soft lifetime. With `$hard_ttl` null the hard lifetime equals the soft one, so a 0.4.0-style call behaves as it does today. The store's own expiry is the hard lifetime.
- The envelope gains `w` (written time) and `s` (soft deadline), alongside `_v` and `value`.
- `read_swr()` keeps its signature and returns `value`, `fresh` (true only when neither kind of stale applies), `stale` (`null`, `'version'` or `'age'`) and `written` (the written time, or null for an entry without `w`).
- An injectable clock, so tests can move time.

**Entries written by 2.40 and beta.4** have no written time. Treat them as never age-stale; their own 4-hour store expiry retires them. No storage schema bump: mai-cache 0.4.0 already ignores extra envelope keys (`fetch()`, `Cache.php:199`), and a bump would turn every entry cold at once on upgrade. An update through the WordPress updater flushes them anyway (`lib/admin/upgrade.php:58`).

### 4. Fixes (mai-cache)

- **`bump()` writes the version token in the same envelope `get()` reads.** Today it writes a bare string (`Cache.php:320`) and `scope_version()` reads it through `get()`, which only accepts envelopes (`fetch()`, `Cache.php:199`). So every bump costs a second write, and two readers arriving together after a save can each mint a different token, which costs an extra rebuild. `bump()` must keep bypassing `can_cache()`, so it writes the envelope directly rather than through `put()` (the existing test at `tests/Unit/SwrTest.php:56-81` checks the bypass). Test: the token `bump()` writes is the token `version()` then returns, with no second write, including when `can_cache()` is false.
- **Clean up orphaned rows on flush.** A flush rotates the prefix or group token, and rows named with the old token are never read again. Without a persistent object cache, the version rows are autoloaded and stay forever.
  - On flush, `TransientStore` deletes every row named with the old token, values and version rows alike, since all are named through `key()`.
  - Each transient is two rows, `_transient_<key>` and `_transient_timeout_<key>`, so the delete matches both prefixes: `DELETE ... WHERE option_name LIKE '_transient_<old prefix>%' OR option_name LIKE '_transient_timeout_<old prefix>%' LIMIT 1000`, repeated until it deletes nothing. The whole text of each pattern before the `%` goes through `$wpdb->esc_like()`, `_transient_` included, as core does in `delete_expired_transients()`. A bare `_` is a `LIKE` wildcard, and escaping only the old prefix scanned the whole table (measured 2026-10-02 on local totalprosports: 81,433 rows per pass, against an index range of 2 rows). Both are indexed prefix matches on `option_name`. The batches keep the first flush on a big site from being one huge delete.
  - The old prefix is the key prefix up to and including the rotated token. A group flush (`flush_all()`, run after `wp cache flush`) rotates the `grid` token, so the prefix is `mai_s1_<root token>_grid_<old grid token>_`. The update flush (`lib/admin/upgrade.php:58`) and `wp mai flush` (`lib/init.php:463`) rotate the root token, so the prefix is `mai_s1_<old root token>_`, which covers every group.
  - Guards (decided 2026-10-02, so a bug can never delete the wrong rows): `flush()` skips the cleanup unless every token in the old prefix is exactly 12 lowercase hex characters, the only kind mai-cache has made since 0.2.0, and `delete_prefix()` refuses an empty prefix or one not ending in `_`. When a guard trips, the token still rotates and old rows wait for WordPress's own expiry, as before 0.5.0.
  - `{$wpdb->options}` is per-site on multisite, so this cleans the current site only, which is the site whose token rotated.
  - `ObjectCacheStore` does nothing here: the object cache expires its own keys.
  - Afterwards, clear the `alloptions` cache (`wp_cache_delete( 'alloptions', 'options' )`), since version rows are autoloaded.
  - The `Store` interface has been public since 0.2.0, so it gains no methods. Prefix delete goes on a separate optional interface that `TransientStore` and `ObjectCacheStore` implement. A custom store without it is skipped.

Release as mai-cache 0.5.0: soft and hard expiry, the injectable clock, both fixes, and `unlock()`, which Mai Engine calls when a rebuild after the page gives up (decided 2026-10-02). `lock()` does not change. Bump the version passed to `Mai_Cache_Bootstrap::register()` in `init.php:98`, tag `v0.5.0` on GitHub, and raise Mai Engine's constraint to `^0.5.0`.

### 5. Fixes (Mai Engine)

- **Strip WordPress's placeholder escape before hashing the cache key.** `$wpdb->prepare()` replaces each `%` with a per-request placeholder, so a grid whose SQL contains `%` (a `LIKE` added through `mai_post_grid_query_args`) gets a new key on every request. Strip it from both the SQL and the query var values, as core does before hashing (`class-wp-query.php:5076-5097`).
- **Treat `private` like `publish` when rotating**, in both `on_transition()` and `on_delete()`. Editors' grids include private posts. Every post type except revisions keeps rotating, as today.
- **`hydrate()` also drops posts whose post type is no longer in the query.** `set_post_type()` only fires `clean_post_cache` (`post.php:2428`), not a status transition.
- **Never store a result from a failed query.** Check `$wpdb->last_error` in `posts_results` at the earliest priority, while `last_query` is still the grid's own statement. Later, at `the_posts`, `last_query` is usually a priming query. Compare `last_query` with the request after `$wpdb->remove_placeholder_escape()`, because `last_query` is recorded after the `query` filter strips the placeholder. When the failure is detected, also call `wp_cache_set_posts_last_changed()` (decided 2026-10-02): on a site with a persistent object cache, core has already saved the failed empty list in its `post-queries` cache, and without this the next visitor would get it with no SQL and Mai would store it. Core calls the same function on every post save and post meta change.
- **Do not cache `fields => 'ids'` queries.** `serve()` returns post objects, and core turns them into `1`s for this field mode (`class-wp-query.php:3325`). Mai's own grids never use it.

### New filters

Following the existing `mai_query_cache_*` names:

- `mai_query_cache_hard_ttl`: the hard lifetime in seconds, default `DAY_IN_SECONDS`.
- `mai_query_cache_after_page`: whether age-stale notes are rebuilt after the page, default true.
- `mai_query_cache_after_page_ms`: the time budget for work after the page, default 1000.

## Tests

PHPUnit in both repos, following existing patterns. Write the failing test first.

**mai-cache (`tests/Unit`):**

- Give `tests/Support/ArrayStore.php` real expiry (it ignores `$expire` today) and use the injectable clock.
- `bump()` writes an envelope; `version()` returns the bumped token; no second write; the bypass of `can_cache()` still holds.
- Soft and hard expiry: fresh before soft, age-stale after it, gone after hard; version wins when both apply; a write resets both; entries without a written time are never age-stale; `written` is reported.
- Limits: a hard lifetime below the soft one is raised to it; `write_swr()` without a hard lifetime behaves as 0.4.0 did.
- Flush still rotates the token when the store has no prefix delete.

**Mai Engine (unit and integration):**

- The kept-only and fallback tests from the branch, rewritten without the strategy switch, plus the beta.4-shaped entry test.
- Flush cleanup on real MySQL: deletes the old token's rows (both transient prefixes, more than one batch) and nothing else.
- Which path a stale read takes: a version-stale entry is rebuilt during the page by the `lock()` winner and never queued; an age-stale kept-only entry with the marker, on a page view with a finish function, is served stale and queued; an age-stale entry without the marker, a non-kept-only grid, a malformed entry, `mai_query_cache_after_page` off, no finish function, or not a page view, each rebuilds during the page and queues nothing.
- One decision per key: two reads of the same key in one request run at most one query, whichever path the first took.
- The job, through `run_queue()`: it works after Mai_Grid has restored the query's vars; the tiebreaker is applied at shutdown so the key matches; it stores the IDs under the version captured at read time, so a post saved between read and job leaves the stored entry stale; a decline deletes the entry; a job on a different blog is skipped; a key already rebuilt this request is skipped; a job that loses `lock()` is skipped; jobs run oldest-written first and stop at the time budget.
- No drift: the list a rebuild after the page stores equals the list a during-page rebuild of the same grid stores; a stored entry carries `'by' => 'ids'` exactly when `fetch_ids()` built it.
- `DONOTCACHEPAGE`, through its seam: defined when a version-stale entry is served to a lock loser; not redefined when already defined; not defined outside a front-end page view; never defined for a fresh or age-stale read. One integration test runs in a separate process and checks the real constant.
- Deferred stores: without a persistent object cache, a store is deferred on a page view that can finish early, and inline otherwise; a second grid with the same key on the same page is served the pending result with no second query; pending stores run before jobs; **a post saved between the query and the deferred store leaves the stored note stale** (the version stored is the one read before the query). On a persistent object cache, stores stay inline and single-flight still works.
- Each fix in section 5 has a test.
- Both suites pass: `composer test-unit` and `composer test-integration`.

## Verification on local sites

Local Herd sites run PHP-FPM with 5 workers per PHP version (Herd's `config/fpm/8.4-fpm.conf`), so parallel requests run 5 at a time. For every speed test, back up the site's `wp-config.php`, set `WP_DEVELOPMENT_MODE` to `''` and `SCRIPT_DEBUG` to `false`, run the test, then copy the backup back and confirm it is byte-identical (`cmp`). Never restore with `wp config set`. If testing code outside the main checkout, repoint one site's `wp-content/plugins/mai-engine` symlink at a time and restore it. Never touch live sites or post content beyond what a test creates and removes. Log each job's duration, since Query Monitor outputs at shutdown priority 9 and never sees the jobs.

- **eurweb (Redis, 85k posts, WP Rocket page cache on):** bypass WP Rocket for timing (a query string it does not cache, or a logged-in cookie for a subscriber), or the test times cached files. Time to first byte with age-stale grids is no slower than with fresh grids, and those grids are fresh on the next request. Time to first byte after a save is no slower than beta.4. A cold page is no slower than with the cache off.
- **WP Rocket after a save, on eurweb:** save a post in a category a front-page grid shows, let WP Rocket purge and preload, then check the saved cache file contains the new post.
- **Busy site saving constantly, on eurweb (Redis):** run steady concurrent traffic across the front page and a spread of article pages for 10 minutes while a script bumps the `post` version every 30 seconds. Run it on beta.4 and on beta.5 and compare: grid queries per minute (beta.5 must not be higher), the longest time any PHP worker spends after a page, and time to first byte.
- **larrybrownsports (no Redis locally, Redis in production, 146k posts):** the same timing checks. Then concurrent requests to a page whose grids were aged past their soft lifetime: count grid queries per entry and compare with beta.4 hitting the same entries at expiry. Beta.5 may not run more.
- **Two small sites without Redis (for example agiindustries and livingwaterguidewp):** the churn scenario (bump before every request) is no slower than with the cache off, at the median.
- **visitsleepyhollow (The Events Calendar):** event grids render the same as with the cache off.
- Rendered grid post IDs are identical with the cache on and off on every page tested, once rebuilds have settled.

## Release

1. Release mai-cache 0.5.0: changelog, version in `init.php`, tag `v0.5.0`, push the tag.
2. In Mai Engine, raise the constraint to `^0.5.0` and update the vendored copy with Composer.
3. Changelog entries under `## 2.41.0 (TBD)` in Mai Engine's `CHANGES.md`.
4. Rewrite `STATE.md`.
5. Replace `@since TBD` with `2.41.0` and commit. Bump the plugin header to `2.41.0-beta.5`, regenerate the `.pot` with `composer i18n`, then run the repo's own `npm run beta` (`package.json:74`), which builds, regenerates the autoloader without dev packages, commits "Beta release", runs `deployable-guard check`, and pushes `develop` and `beta`.

Before the beta: `develop` is up to date with `origin/develop`, and there are no worktrees and no feature branches.

## Risks

- **A page cache plugin that saves the page in its own `shutdown` callback after Mai's.** Not found locally; WP Rocket saves in an output buffer callback, which runs before Mai finishes the response.
- **A PHP worker stays busy after the page is sent.** Bounded by the 1 second budget per request. It is the same query a visitor would otherwise have waited for.
- **Another plugin exits in an earlier `shutdown` callback, so Mai's never runs.** Results waiting to be stored are lost, and the next visitor gets a cold miss. Queued rebuilds are lost too, and the next visitor is served the aged note again and queues another job, which is lost the same way. On such a site aged notes stay until their 24-hour hard expiry. Saved posts still show, because a save makes the note version-stale. What lingers is views and trending order, and term or meta changes made without saving the post. Nothing wrong is stored. Unverified whether any plugin in the fleet does this.
- **Duplicate queries without Redis.** Two visitors arriving at the same moment at an aged-out note can each run the query once, as today, in a slightly longer window.
- **A grid query that fails on every view.** Each failure resets core's post-query cache, so while the failure lasts, core's own query cache on a Redis site works as it does on a site without Redis. Mai's grid notes are keyed to Mai's stamps, not core's, so they keep serving.
- **Hard lifetime without Redis.** 24 hours means rows for keys nobody reads any more stay up to 24 hours instead of 4 before WordPress clears them. With 2.41's shared entries the number of keys is small; the daily cron and next-read cleanup still apply.
