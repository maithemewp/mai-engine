# State
Updated: 2026-10-01 by Claude

## Now
2.41.0-beta.4 is released (`develop` and `beta` at `1bb05a8fa`). The grid cache work for 2.41.0-beta.5 is designed and planned but not built:

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Read "How it works, in plain terms" first.
- Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`. Fifteen tasks: mai-cache 0.5.0 first (`~/LocalPackages/mai-cache`, Tasks 1 to 5), then merge the kept-only branch (Task 6), then the engine work, local verification and release.
- The kept-only branch `feat/grid-load-kept-posts` lives in the worktree `~/Plugins/mai-engine-grid-loading` (18 commits on `1bb05a8fa`). Its own `STATE.md` describes the benchmark switch that Task 6 removes.

## Next
- Execute the plan from Task 1, task by task, with `subagent-driven-development` or `executing-plans`.

## Blocked / waiting on
- Every push and tag needs Mike's explicit yes (plan Task 15).
- Branch and worktree deletion: hand Mike the commands (plan Task 6, Step 8).

## Verify
- mai-cache: `composer test-unit` in `~/LocalPackages/mai-cache`.
- Mai Engine: `composer test-unit` and `composer test-integration` (`composer install -d tests` first if `tests/vendor` is missing).
- `npx gulp build:main-css` and `build:editor-css` rebuild the CSS. `gulp styles` does not exist.

## Gotchas
- Settled decisions, do not reopen without Mike: after a post is saved, grids rebuild during the page as today (no old list shown, because host caches like SiteGround's ignore `DONOTCACHEPAGE`); only notes that aged out are rebuilt after the page; no new lock; every post type keeps bumping its stamp on save; core's query cache is left alone on a hit.
- Inside one request, re-reading a cache entry returns that request's own copy (Redis drop-in `object-cache.php:1920`, `get_option()`), so a job can never see another request's write. Correctness comes from storing under the version read before the query.
- Local eurweb and larrybrownsports symlink `wp-content/plugins/mai-engine` to this checkout. After switching a symlink, PHP-FPM can keep old code; `kill -USR2` the pool master and probe.
- Speed tests: back up `wp-config.php`, set `WP_DEVELOPMENT_MODE` to `''` and `SCRIPT_DEBUG` to false, restore by copying the backup back and `cmp`. Never restore with `wp config set`.
- `mai-sites push` reads `.distignore`, not `.gitattributes` `export-ignore`. Dev files and `docs/` must be listed in `.distignore` to stay off servers.
- The editor loads its column CSS after block editor styles, so a block overriding column rules at equal specificity loses in the editor only. Prefer custom properties like `--columns-display`.
