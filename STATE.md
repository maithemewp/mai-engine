# State
Updated: 2026-09-30 by Claude (branch feat/grid-load-kept-posts)

## Now
Branch `feat/grid-load-kept-posts` stops deferring post grids from loading posts they will not show. It is not merged. It has three strategies behind a TEMPORARY `mai_grid_load_strategy` filter (`current`, `prime_late`, `kept_only`, default `kept_only`) so they can be benchmarked in one code tree.

- `prime_late`: the padded query runs with meta and term priming off, then Mai_Grid primes only the kept posts.
- `kept_only`: a hit at `posts_pre_query` priority 10 returns only the kept posts. A miss is answered by `Mai_Query_Cache::pre_query_kept()` at `PHP_INT_MAX`, only if nothing else answered. `fetch_ids()` runs an ID-only copy of the grid's WP_Query (built from `$query->query`, `fields => ids`, `mai_cache => false`, the asked `cache_results`), stores the padded list, then loads only the kept posts. It falls back to Mai_Grid's own filtering when the copy fails, finds nothing, selects more than the ID, or is not the grid's query.
- The kept-only marker is the `mai_grid_keep` property of the WP_Query, set before it runs. Never a query var: The Events Calendar answers event queries with a copy built from the vars.

Measured 2026-09-30 on local sites (absolute times inflated by `WP_DEVELOPMENT_MODE` `all`): eurweb front page, 18 deferring grids, Redis. kept_only is 48 ms faster per page on a Mai hit and 21 to 76 ms faster in grid time otherwise. A Mai miss with core's cache warm is no longer slower than `current`. larrybrownsports showed no page-time difference and identical ordered IDs for all strategies.

## Next
- Pick the strategy, then delete the filter and the other two code paths before merge.
- Add a CHANGES.md line once the strategy is chosen.

## Blocked / waiting on
The choice of strategy.

## Verify
- `composer install -d tests` if `tests/vendor` is missing.
- `composer test-unit` and `composer test-integration` both pass.
- `tests/phpunit/integration/GridLoadStrategyTest.php` runs every strategy against the same grids.

## Gotchas
- kept_only keeps `cache_results` off on the grid's own query. Core would otherwise store the kept answer under the padded query's key.
- The ID copy gets its own core cache key, not the full query's. Core's key swaps every `wp_posts.ID` in the statement for `wp_posts.*`, not only the select list.
- Build the copy from `$query->query`, not `query_vars`. Core writes `cat` and `category_name` into `query_vars`, and a query built from them joins the taxonomy twice.
- Core caches a failed statement's empty result like any other. That is why `fetch_ids()` never trusts an empty ID list.
- `cache_key()` truncates a query var to the hour only when it is within five minutes of now. TEC writes "now" to the second into its `meta_query`.
- With Events Calendar Pro, grid posts are occurrences with provisional IDs (10000000 plus the occurrence ID). On a recurring event, exclude current drops only the viewed occurrence, the same as without deferring.
- In tests, `go_to()` empties the object cache, so a test of core's query cache has to stay on one page.
- After switching a local site's `mai-engine` symlink, one PHP-FPM worker kept the old code well past 120 seconds. `kill -USR2` on the pool's master pid reloads it. Probe before trusting a benchmark.
- Core sorts `post__not_in` in place when it builds `NOT IN`. Compare query_vars with that array sorted.
- A non-null `posts_pre_query` answer no longer proves a cache hit. Count the grid's SELECTs instead.
- `mai-sites push` reads `.distignore`, not `.gitattributes` `export-ignore`.
