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

Two repos change: the cache library `maithemewp/mai-cache` (`~/LocalPackages/mai-cache`, released as 0.6.0, see Release) and Mai Engine (`~/Plugins/mai-engine`, released as 2.41.0-beta.5).

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
- **An empty result is a real answer** (decided 2026-10-02 after local verification on eurweb, where an empty grid on every page ran its query twice). The ID copy's empty result is stored with the marker like any other. If `$wpdb->last_error` is set after an empty copy, a statement sent during the copy means the copy failed (nothing stored, core's query cache reset), and no statement sent means a stale error (the grid's own query answers).

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

The release holds soft and hard expiry, the injectable clock, both fixes, and `unlock()`, which Mai Engine calls when a rebuild after the page gives up (decided 2026-10-02). `lock()` does not change. It shipped as mai-cache 0.6.0, loaded by mai-package-loader instead of its own bootstrap (decided 2026-10-04, see Release).

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

- **eurweb (Redis, 85k posts, WP Rocket page cache on):** bypass WP Rocket for timing (a query string it does not cache, or a logged-in cookie for a subscriber), or the test times cached files. Time to first byte with age-stale grids is no slower than with fresh grids, for grids using Exclude current or Exclude displayed. Other grids rebuild an aged note during the page, as spec flow 4 says, so a page with one is held to beta.4 instead: no slower than beta.4 when the same note expires (decided 2026-10-02). Aged grids are refreshed within a few visits, since each visit starts refreshes for up to the 1 second budget. Nobody waits for a refresh. With Redis, no grid is refreshed twice. This replaces "fresh on the next request" (decided 2026-10-02). Time to first byte after a save is no slower than beta.4. A cold page, the first view after Mai's cache is emptied, may be slower than with the cache off by no more than about 1 ms per grid over 2.40.1's gap, once per emptying (decided 2026-10-03): the extra is the ID-only lookup that lets pages share one note. All comparisons are against 2.40.1, the last real release (decided 2026-10-03). WP Rocket after a save: no saved page ever has an old list (decided 2026-10-03). Bursts: nobody waits for a grid refresh, and waits for a free PHP worker are no worse than 2.40.1 (decided 2026-10-03).
- **WP Rocket after a save, on eurweb:** save a post in a category a front-page grid shows, let WP Rocket purge and preload, then check the saved cache file contains the new post.
- **Busy site saving constantly, on eurweb (Redis):** run steady concurrent traffic across the front page and a spread of article pages for 10 minutes while a script bumps the `post` version every 30 seconds. Run it on beta.4 and on beta.5 and compare: grid queries per minute (beta.5 must not be higher), the longest time any PHP worker spends after a page, and time to first byte.
- **larrybrownsports (Redis in production, 146k posts):** run with a local Redis drop-in on its own Redis database, as production runs (decided 2026-10-02), then remove it. The same timing checks. Then concurrent requests to a page whose grids were aged past their soft lifetime: count grid queries per entry and compare with beta.4 hitting the same entries at expiry. Beta.5 may not run more.
- **Duplicate refreshes without Redis, on a small site:** with realistic, spaced-out traffic to a page whose grids aged, count grid queries per entry on beta.4 at expiry and on beta.5. Report the numbers. Without Redis there is no shared lock, so beta.5 may run more queries after the page than beta.4 did; nobody waits for them (accepted 2026-10-02).
- **Two small sites without Redis (for example agiindustries and livingwaterguidewp):** the churn scenario (bump before every request) is no slower than with the cache off, at the median.
- **visitsleepyhollow (The Events Calendar):** event grids render the same as with the cache off.
- Rendered grid post IDs are identical with the cache on and off on every page tested, once rebuilds have settled.

## Results (local, 2026-10-03 to 2026-10-04)

Beta.5 (the `develop` branch) was compared with 2.40.1, the last release, on local Herd sites. Herd runs PHP-FPM with 5 workers, and local MySQL has a 128 MB buffer pool, far smaller than eurweb's 2.5 GB database, so timings of heavy grid queries here will differ from production. A difference is beta.5 minus 2.40.1 with a 95% confidence interval in brackets, and the versions ran in alternating blocks. An aged note is one past its 4 hour soft lifetime with no post saved. Core's cache is WordPress's own cache of post query results, and it goes cold on every post save or post meta change. Raw data, drivers and probe logs stay out of the repo.

**eurweb (Redis, 85k posts, WP Rocket page cache on, bypassed for timing).**

- **PASS. Aged grids using Exclude current or Exclude displayed load as fast as fresh ones.** Aged minus fresh over 5 articles, 105 pairs each. Core warm +4.7 ms [−6.4, +18.6], core cold −4.8 ms [−19.1, +4.6]. 0 grid statements during the page in 210 of 210 aged requests. Measured at `b4f440e6e`, before two small priming trims.
- **PASS. The front page's top slider grid, which has no Exclude setting, is no slower aged than expired on 2.40.1.** Core cold −9.3 ms [−41.5, +60.6], medians 1,300.9 vs 1,310.2 ms. Core warm −35.6 ms [−75.3, −7.6], medians 896.0 vs 931.6 ms. 22 requests each. Both versions ran only the slider's 1 statement with core cold, and none with it warm.
- **PASS. Aged grids are refreshed within a few visits.** Spaced visits left every grid fresh by visit 3 in 21 of 21 trials, and a burst of 4 overlapping visits left every grid fresh in 15 of 15. Measured at `b4f440e6e`.
- **PASS. With Redis no grid is refreshed twice.** 0 of 36 trials rebuilt any of 18 keys twice, and 0 gave up. In 24 bursts of 4 visitors, 18 grids were rebuilt after the page in every burst, 0 twice. In the busy-site test below, 0 notes were refreshed twice after the same save on either version.
- **PASS. Nobody waits for a grid refresh.** In 24 bursts of 4 visitors on aged notes, 0 of 96 beta.5 visitors waited on another request's refresh, and 0 ran a deferring grid's SQL during the page. On 2.40.1 at expiry, 73 of 96 waited, median 1,712 ms, max 2,307 ms. Median time to first byte was 1,186 ms (all 4 at once) and 924 ms (0.25 s apart) on beta.5, against 3,024 and 2,655 ms on 2.40.1.
- **PASS. Waits for a free PHP worker are no worse than 2.40.1 in those bursts.** 13 of 96 beta.5 visitors waited over 250 ms, against 14 of 96. An earlier run saw the 4th of 4 visitors wait 314 to 757 ms in 9 of 15 bursts. Mike changed the bar to compare with 2.40.1 on 2026-10-03.
- **PASS. The first request after a save is no slower than 2.40.1.** −33.0 ms [−58.6, +24.7], medians 3,013.3 vs 3,046.3 ms, 132 requests each. Both versions ran the same 19 grid statements on the front page and 10 per article.
- **Accepted by Mike on 2026-10-03. A cold page costs about 1 ms per grid over 2.40.1's gap.** A cold page is the first view after Mai's cache is emptied. The allowance is 18 ms on the front page and 9 per article, 10.5 ms averaged over the 6 pages. Beta.5's gap minus 2.40.1's gap was −0.6 ms [−11.4, +12.3] with core warm and +13.9 ms [+0.3, +34.5] with core cold, 132 pairs per version per state, so the core-cold median is 3.4 ms over the average allowance and its interval includes it. The front page alone was +0.9 ms. A profiling run put about 6 ms of PHP on the ID-only lookup (a second query that fetches only post IDs, which lets pages share one note), and found no other part whose interval excludes 0. Mike accepted this cost with these numbers in front of him, and the bar was reworded to match.
- **PASS. WP Rocket never saves a page with an old list after a save.** Over 6 save events per version (3 publishes and 3 deletes), all 27 files beta.5 saved matched a cache-off render. No front page file was saved in any event, because all 12 front page preload requests split the grid locks and set `DONOTCACHEPAGE`. On 2.40.1 the race reproduced in 1 of 6 events, and none of its 12 front page preload requests set `DONOTCACHEPAGE`. The saved mobile front page had 8 of 16 grids wrong, 7 of them exactly the list from before the delete, and the `/news/` and an author page files got old lists too.
- **PASS. Rendered post IDs are identical with the cache on and off.** 6 of 6 pages in two passes (front page 18 grids and 60 posts, each article 9 grids and 40 posts).

**eurweb, busy site (10 minutes, 4 clients, a simulated save every 30 seconds, 4 runs per version).**

- **PASS. Beta.5 runs fewer grid queries per minute.** Median of 4 runs 174.5 vs 234.1 (−25%). Every beta.5 run (174.8, 174.2, 175.4, 171.6) was below every 2.40.1 run (234.6, 233.6, 229.3, 245.6). After each save beta.5 refreshed 94 notes and 2.40.1 refreshed 129, because beta.5's article grids share notes.
- **PASS. Nobody waits for a refresh, and no worker works after a page.** 0 waits on another request's refresh in 7,009 beta.5 requests (2.40.1 had 0 in 7,110), and 0 of 14,119 responses were 5xx. The longest time a worker spent after a page was 0, because a save every 30 seconds keeps notes out of date instead of aged, so the after-page queue never ran. The burst test above covers that path, at a median of 529 ms of work per visitor and a max of 1,017 ms.
- **PASS. Time to first byte is no slower.** Median of 4 runs 1,042.7 vs 1,057.6 ms, pooled difference −11.3 ms [−15.6, −7.7]. 95th percentile 3,110.8 vs 3,139.1 ms, pooled difference +13.5 ms [−48.7, +66.2].
- **Accepted by Mike on 2026-10-04. Waits for a free PHP worker, busy-site test, judged on the runs with stall detection.** The raw numbers missed. The waits came from machine-wide stalls, not from beta.5.
  - **The numbers.** 55 of 7,009 beta.5 requests waited over 250 ms for a worker, against 2 of 7,110 on 2.40.1. Median of 4 runs 14 vs 0.5 waits per run, 99th percentile 205.8 vs 25.3 ms, 95th percentile 21.5 vs 18.6 ms, median 13.4 vs 13.2 ms.
  - **The measured cause.** 54 of the 55 fell in two short windows (90 seconds of run 2, and the first 47 seconds of run 4), and both match floods of 12,000 to 20,000 log events a second from macOS's sandboxd, which hit the whole machine. eurweb had at most 3 requests in PHP during each wait, so at least 2 of the pool's 5 workers were not running eurweb. Beta.5 ran no work after the page and sent no early response in these runs.
  - **The cross-checks.** Runs 5 to 8 added two stall detectors that do not touch PHP. Each long wait in them overlapped a stall the detectors saw, and the count was 1 vs 1. On larrybrownsports the same bar shows 17 of 144 beta.5 visitors against 26 of 144.

**larrybrownsports (146k posts, a local Redis drop-in on its own database 2, removed afterwards).**

- **PASS. Everyday views are no slower.** −11.2 ms [−20.9, −4.0], medians 577.7 vs 588.9 ms, 120 requests each over the front page and 5 articles. 0 grid statements in all 240.
- **PASS. Aged grids using Exclude load as fast as fresh ones.** Core warm +0.2 ms [−8.3, +3.9], core cold −6.1 ms [−14.3, +3.1], 100 pairs each. 0 grid statements during the page in 200 of 200 aged requests.
- **PASS. The front page's one grid with no Exclude setting is no slower aged than expired on 2.40.1.** Core cold −33.1 ms [−54.0, +0.7], medians 709.0 vs 742.1 ms. Core warm −6.2 ms [−25.2, +4.8], medians 557.3 vs 563.5 ms. 22 requests each.
- **Accepted by Mike on 2026-10-04 for the beta. The first view after a save is slower on 2 of 6 pages, because MySQL picks a slower plan when its buffer pool is too small.** Recorded in Risks, and the buffer pool size on the main hosts gets checked before 2.41.0 final.
  - **The numbers.** Beta.5 minus 2.40.1 on the burrow article (football, 2026) was +119.7 ms [+78.6, +143.4], and on the seahawks article (football, 2023) +38.2 ms [+0.3, +70.5]. Pooled over the 6 pages +19.8 ms [−30.0, +67.5], medians 1,038.8 vs 1,019.0 ms, 120 requests each. The other 4 pages' intervals include 0. Both versions ran the same statements (4 on the front page, 5 per article).
  - **The measured cause.** Beta.5's ID-only lookup for the football grids has no `NOT IN` list, so MySQL's row estimate for the normal plan doubles (87,400 against 43,700). When an earlier request has pushed `wp_term_relationships` out of the 128 MB buffer pool (3 to 5 of its 3,402 index pages cached), MySQL prices another plan cheaper (97,110 to 100,021 against 106,589 to 106,659) and takes it in 4 of 4 rounds. That plan fetches each football post from `wp_posts` one by one and reads about 13,000 pages from disk plus about 9,000 read-ahead pages. The two heavy statements then take 252 and 214 ms instead of 204 and 158 ms. 2.40.1 stayed on the normal plan in 24 of 24 requests.
  - **The same without Redis.** Beta.5 took that plan after a front page request in 12 of 18 rounds and after a burrow request in 0 of 18, so the miss does not need Redis. The front page request pushes `wp_term_relationships` out on both versions alike. At 128 MB, burrow was +30.8 ms [−12.7, +95.6] and the 6 pages pooled −32.3 ms [−80.6, +45.2].
  - **At 1 GB the slowdown is gone and beta.5 is faster.** Burrow −100.5 ms [−195.5, −25.6], 6 pages pooled −273.8 ms [−318.2, −226.2], with 0 disk reads. Where beta.5 still took the other plan, it ran in 146 and 101 ms against 197 and 158 ms on the normal plan.
  - **Production is inferred, not measured.** It can only happen on a MySQL server that cannot keep `wp_posts` (472 MB here) and `wp_term_relationships` (54 MB here) cached. No production server was checked. What to look up is each database server's `innodb_buffer_pool_size`, and whether it runs MariaDB, which prices plans differently and was not tested.
- **PASS. A cold page costs no more than 1 ms per grid over 2.40.1's gap.** Beta.5's gap minus 2.40.1's gap was −1.9 ms [−5.1, +3.2] with core warm and −5.3 ms [−14.7, +4.9] with core cold, 120 pairs per version per state. The allowance is 4 ms on the front page (4 grids) and 5 ms per article (5 grids).
- **PASS. Aged grids are refreshed within a few visits.** Every grid was fresh by visit 2 in 20 of 20 spaced trials (10 on the front page, 10 on burrow), and after each burst of 4 in 36 of 36.
- **PASS. Nobody waits for a refresh.** 0 waits on another request's refresh in 20 spaced trials and 144 burst visitors, and 0 Exclude grids ran SQL during the page. On 2.40.1, 59 of 144 visitors made 164 waits, median 55 to 165 ms, max 228 ms. The one grid with no Exclude setting was rebuilt during the page by exactly one visitor per trial or burst.
- **PASS. No grid is refreshed twice, and no entry runs more queries than on 2.40.1.** Both versions ran exactly 1 statement per entry in all 168 entries (72 bursts). Beta.5 ran 24 during the page and 132 after it, 2.40.1 ran 156 during.
- **PASS. Waits for a free PHP worker are no worse than 2.40.1.** 17 of 144 beta.5 visitors waited over 250 ms, against 26 of 144. Longest wait per burst, median, 471 vs 707 ms (4 at once, front page), 36 vs 111 ms (0.25 s apart), 540 vs 863 ms (4 at once, burrow).
- **PASS. Rendered post IDs are identical with the cache on and off.** 6 of 6 pages in two passes (core warm, then core cold).

**Small sites without Redis (agiindustries, livingwaterguidewp, sportsdataio, visitsleepyhollow).**

- **Report only. Duplicate refreshes were 0 on either version.** Every entry ran 1 statement. On agiindustries that was 24 entries on each version, but it has no Exclude grids, so the after-page path never ran there. On sportsdataio, which has them, 2.40.1 ran 51 entries and 51 statements, beta.5 53 and 53. Details are under "Also found".
- **PASS. Churn on agiindustries is no slower than with the cache off.** A simulated save before every request, bumping every post type the site's grids show. Cache on minus cache off +0.8 ms [−10.8, +15.6], medians 553.7 vs 552.9 ms, 60 requests each. 2.40.1 for reference +8.1 ms [+1.4, +17.3].
- **PASS. Churn on livingwaterguidewp is no slower than with the cache off.** −7.3 ms [−16.9, +2.1], medians 584.3 vs 591.6 ms, 60 requests each. 2.40.1 for reference +8.3 ms [−11.7, +21.2].
- **PASS. visitsleepyhollow (The Events Calendar) renders event grids the same as with the cache off.** The only Mai grid that shows events, on `/guides/halloween-in-sleepy-hollow/`, rendered the same 17 IDs in the same order settled, after a flush, after a save and aged. An events-only grid rendered from WP-CLI holds the current time in its query, so beta.5 does not cache it, and it showed the same 6 events as with the cache off.
- **PASS. Rendered post IDs are identical with the cache on and off.** 13 of 13 pages (agiindustries 3, livingwaterguidewp 3, sportsdataio 4, visitsleepyhollow 3), each in 4 passes (settled, flushed, after a save, aged).

**Also found.**

- **The grid cache pays off only when core's cache is cold.** On eurweb with core cold, a page with every note current took 1,262 ms against 3,088 ms with the grid cache off, about 1.9 s less per page (−1,855 ms [−1,876, −1,820]). With core warm the difference is +3.1 ms [−8.6, +10.0], because core's cache already answers the grids. On livingwaterguidewp, without Redis, −0.7 ms [−5.7, +6.9]. Each heavy grid statement on eurweb takes 245 to 300 ms here, so production times will differ.
- **`DONOTCACHEPAGE` counts.** In the four busy-site beta.5 runs it was set on 92, 105, 135 and 106 requests (6 to 7% of requests) and on none in the four 2.40.1 runs. These are the visitors who lose the lock after a save and get the old list. It was set in 0 of 264 everyday requests and 0 of 264 first-request-after-a-save requests.
- **Switching between 2.40.1 and beta.5 orphans beta.5 notes.** 2.40.1's flush changes the grid token without deleting rows, so beta.5's next flush cannot find the rows written before the switch (84 notes under 6 old tokens on larrybrownsports). They sit in `wp_options` until they expire. Only a back-and-forth switch does this. 2.40.1 itself also leaves its notes' rows behind (78 on agiindustries).
- **Duplicate refreshes on sportsdataio.** 53 entries ran 53 statements on beta.5, 24 during the page (the front page's 4 grids without Exclude) and 29 after it, with 0 twice and 0 given up. The 29 refreshes after the page took 3.7 ms median and 16.6 ms max, and the longest work after any page was 22.8 ms. No two requests overlapped (closest gap 642 ms on 2.40.1 and 688 ms on beta.5), and a duplicate needs a second visitor within about 4 to 17 ms.

## Release

1. mai-cache shipped as 0.6.0 (decided 2026-10-04): 0.5.0's code, loaded by mai-package-loader 0.1.0 instead of its own bootstrap, and numbered 0.6.0, above the copies that register through mai-cache's old bootstrap (2.40.1's registers 0.2.0, the copy bundled on `develop` for this work 0.5.0). Its `src/` differs from the copy measured above only by five dropped `ABSPATH` guard lines. No `v0.5.0` tag.
2. In Mai Engine, require `^0.6` through Composer with mai-package-loader, as plan Task 15 step 2 says.
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
- **An empty grid copy that failed in a way Mai cannot attribute.** If another plugin changes the ID copy's statement through the `query` filter and it fails, `last_query` no longer matches. Mai then checks whether the copy sent any statement. If it did, the error is treated as the copy's failure: nothing is stored and WordPress's own query cache is reset, so the failed empty list cannot be read back on a later view. If it sent none (core answered from its cache), the error is stale and the grid's own query answers. The cost is on an already broken site: if some plugin errors inside every post query, each view of an empty Exclude grid resets core's query cache, as if the site had no Redis (decided 2026-10-02).
- **A database server too small to keep the post tables in memory (decided 2026-10-04).** A deferring grid's ID-only lookup has no `NOT IN` list, so MySQL expects twice the rows. When `wp_term_relationships` is not cached, MySQL can then pick a plan that reads `wp_posts` from disk. On local larrybrownsports with a 128 MB buffer pool, the two heavy football grids took 25 to 55 ms more per statement, and the first view after a save of one article was +119.7 ms. With 1 GB the same pages were faster than 2.40.1. It only costs time when a grid's list is rebuilt. Check `innodb_buffer_pool_size` on the main hosts before 2.41.0 final. MariaDB prices plans differently and is untested.
- **Hard lifetime without Redis.** 24 hours means rows for keys nobody reads any more stay up to 24 hours instead of 4 before WordPress clears them. With 2.41's shared entries the number of keys is small; the daily cron and next-read cleanup still apply.
