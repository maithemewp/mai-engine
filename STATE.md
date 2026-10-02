# State
Updated: 2026-10-02 by Claude

## Now
The grid cache work for 2.41.0-beta.5 is built and reviewed on `develop`, not pushed. Plan Tasks 1 to 13 are done, plus a final whole-branch review and its fixes. Tasks 14 (local verification) and 15 (release) are left.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Read "How it works, in plain terms" first. Decisions made during the build are marked "(decided 2026-10-02)".
- Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`.
- mai-cache 0.5.0 is finished and release-reviewed on `develop` in `~/LocalPackages/mai-cache` (head `0beab0e`), not tagged or pushed. Mai Engine bundles that exact copy in `vendor/maithemewp/mai-cache`.
- The kept-only branch is merged (`6602154d5`). Its worktree and branch still exist until Mike removes them.
- Progress ledger with every ruling: `.superpowers/sdd/2026-10-01-grid-cache-beta-5/progress.md` (git-ignored).

## Next
1. Get Mike's answer on the larrybrownsports duplicate-query test (see Blocked).
2. Task 14, local verification, exactly as the plan says, including the `wp-config.php` backup and `cmp` restore. Stop and tell Mike if any check misses its bar.
3. Task 15, release. Every push, the `v0.5.0` tag and `npm run beta` need Mike's yes, each time.

## Blocked / waiting on
- Mike: larrybrownsports no-Redis test. Without Redis, rebuilds after the page write late, so a load test can show more grid queries per note than beta.4, which the spec bar forbids. Recommended: test larrybrownsports with a local Redis drop-in (as production runs) and count no-Redis duplicates separately on a small site.
- Mike runs the cleanup (auto mode blocks it):
  - `git -C ~/Plugins/mai-engine worktree remove ~/Plugins/mai-engine-grid-loading`
  - `git -C ~/Plugins/mai-engine branch -d feat/grid-load-kept-posts`

## Verify
- mai-cache: `composer test-unit` in `~/LocalPackages/mai-cache` (99 tests).
- Mai Engine: `composer test-unit` (234, 11 skipped libxml goldens) and `composer test-integration` (222). Both also pass with `--order-by=random`.
- `npx gulp build:main-css` and `build:editor-css` rebuild the CSS. `gulp styles` does not exist.

## Gotchas
- Integration tests: every test runs with a queue whose `DONOTCACHEPAGE` step does nothing, and a check fails any test that defines the real constant in the main process. Separate-process tests skip wp-phpunit's database reinstall.
- `run_queue()` catches `Throwable`, so an assertion inside a hook callback during a job is swallowed. Assert after `run_queue()` returns.
- Inside one request, re-reading a cache entry returns that request's own copy, so a job never sees another request's write. Correctness comes from storing under the version read before the query.
- Local eurweb and larrybrownsports symlink `wp-content/plugins/mai-engine` to this checkout. After switching a symlink, PHP-FPM can keep old code; `kill -USR2` the pool master and probe.
- Speed tests: back up `wp-config.php`, set `WP_DEVELOPMENT_MODE` to `''` and `SCRIPT_DEBUG` to false, restore by copying the backup back and `cmp`. Never restore with `wp config set`.
- `mai-sites push` reads `.distignore`, not `.gitattributes`. Dev files and `docs/` must be listed there.
