# State
Updated: 2026-09-30 by Claude (branch feat/grid-load-kept-posts)

## Now
Branch `feat/grid-load-kept-posts` stops deferring post grids from loading posts they will not show. It is not merged. It has three strategies behind a TEMPORARY `mai_grid_load_strategy` filter (`current`, `prime_late`, `kept_only`, default `kept_only`) so they can be benchmarked in one code tree.

- `prime_late`: the padded query runs with meta and term priming off, then Mai_Grid primes only the kept posts.
- `kept_only`: a hit at `posts_pre_query` priority 10 returns only the kept posts. A miss is answered by `Mai_Query_Cache::pre_query_kept()` at `PHP_INT_MAX`: it runs an ID-only version of the grid's own SQL, stores the padded list, then loads only the kept posts. It falls back to the old filtering when the SQL was rewritten, selects more than `wp_posts.*`, counts rows, or another callback answered.

## Next
- Benchmark the three strategies on eurweb's front page and a small site.
- Pick one, then delete the filter and the other two code paths before merge.
- Add a CHANGES.md line once the strategy is chosen.

## Blocked / waiting on
The benchmark and the choice of strategy.

## Verify
- `composer install -d tests` if `tests/vendor` is missing.
- `composer test-unit` and `composer test-integration` both pass.
- `tests/phpunit/integration/GridLoadStrategyTest.php` runs every strategy against the same grids.

## Gotchas
- kept_only turns `cache_results` off for the grid query. Core would otherwise hash `mai_grid_keep`, which holds the per-view excludes, and write a post-queries entry on every page view.
- Core sorts `post__not_in` in place when it builds `NOT IN`. The restore puts the asked order back, so compare query_vars with that array sorted.
- A non-null `posts_pre_query` answer no longer proves a cache hit. Count the grid's SELECTs instead.
- `mai-sites push` reads `.distignore`, not `.gitattributes` `export-ignore`. Dev files and `docs/` must be listed in `.distignore` to stay off servers.
- The editor loads its column CSS after block editor styles, so a block overriding column rules at equal specificity loses in the editor only. Prefer custom properties like `--columns-display`.
