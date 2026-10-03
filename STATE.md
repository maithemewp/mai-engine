# State
Updated: 2026-10-02 by Claude

## Now
The grid cache work for 2.41.0-beta.5 is built and reviewed on `develop`, not pushed. Plan Tasks 1 to 13 are done, plus a final whole-branch review and its fixes. Task 14 (local verification) is in progress: the first eurweb run missed three bars, Mike decided each (spec "Verification on local sites" and the ledger), and empty grids now store an empty result instead of running the query twice. Task 15 (release) is left.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Read "How it works, in plain terms" first. Decisions made during the build are marked "(decided 2026-10-02)".
- Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`.
- mai-cache 0.5.0 is finished and release-reviewed on `develop` in `~/LocalPackages/mai-cache` (head `0beab0e`), not tagged or pushed. Mai Engine bundles that exact copy in `vendor/maithemewp/mai-cache`.
- The kept-only branch is merged (`6602154d5`). Its worktree and branch still exist until Mike removes them.
- Progress ledger with every ruling: `.superpowers/sdd/2026-10-01-grid-cache-beta-5/progress.md` (git-ignored).

## Next
1. Task 14, local verification, against the updated bars in the spec, with the rules in `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md` (wp-config backup and `cmp` restore, never `wp cache flush` on Redis sites). Re-run eurweb (part A, including the slider against beta.4 at expiry), then the 10-minute busy-site test, larrybrownsports with a local Redis drop-in on database 2, the no-Redis duplicate count on a small site, the two small sites and visitsleepyhollow. Results go in `task-14-results.md`, then the spec. Stop and tell Mike if any bar is missed.
2. After beta.5: `TODO.md` and `docs/ideas/2026-10-02-grid-cache-refresh-every-grid-after-page.md`.
3. Task 15, release. Every push, the `v0.5.0` tag and `npm run beta` need Mike's yes, each time.

## Blocked / waiting on
- Mike runs the cleanup (auto mode blocks it):
  - `git -C ~/Plugins/mai-engine worktree remove ~/Plugins/mai-engine-grid-loading`
  - `git -C ~/Plugins/mai-engine branch -d feat/grid-load-kept-posts`

## Verify
- mai-cache: `composer test-unit` in `~/LocalPackages/mai-cache` (99 tests).
- Mai Engine: `composer test-unit` (234, 11 skipped libxml goldens) and `composer test-integration` (235). Both also pass with `--order-by=random`.
- `npx gulp build:main-css` and `build:editor-css` rebuild the CSS. `gulp styles` does not exist.

## Gotchas
- Integration tests: every test runs with a queue whose `DONOTCACHEPAGE` step does nothing, and a check fails any test that defines the real constant in the main process. Separate-process tests skip wp-phpunit's database reinstall.
- `run_queue()` catches `Throwable`, so an assertion inside a hook callback during a job is swallowed. Assert after `run_queue()` returns.
- Inside one request, re-reading a cache entry returns that request's own copy, so a job never sees another request's write. Correctness comes from storing under the version read before the query.
- Local eurweb and larrybrownsports symlink `wp-content/plugins/mai-engine` to this checkout. After switching a symlink, PHP-FPM can keep old code; `kill -USR2` the pool master and probe.
- Speed tests: back up `wp-config.php`, set `WP_DEVELOPMENT_MODE` to `''` and `SCRIPT_DEBUG` to false, restore by copying the backup back and `cmp`. Never restore with `wp config set`.
- `mai-sites push` reads `.distignore`, not `.gitattributes`. Dev files and `docs/` must be listed there.
