# State
Updated: 2026-10-03 by Claude

## Now
The grid cache work for 2.41.0-beta.5 is built and reviewed on `develop`, not pushed. Plan Tasks 1 to 13 are done, plus a final whole-branch review and its fixes. Task 14 (local verification) is in progress. Task 15 (release) is left.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Read "How it works, in plain terms" first. Decisions made during the build are marked "(decided 2026-10-02)" or "(decided 2026-10-03)", including the reworded bars in "Verification on local sites".
- Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`.
- Every comparison is against 2.40.1, the last real release (`origin/master` `44308c8dc`), copied to `/tmp/mai-engine-2401`. Not beta.4.
- Since the final review: empty grids store an empty result instead of running the query twice, an empty copy whose failure cannot be attributed resets WordPress's query cache, and a priming trim removes repeated work on cached views.
- mai-cache 0.5.0 is finished and release-reviewed on `develop` in `~/LocalPackages/mai-cache` (head `0beab0e`), not tagged or pushed. Mai Engine bundles that exact copy.
- Progress ledger with every ruling and every measurement: `.superpowers/sdd/2026-10-01-grid-cache-beta-5/progress.md`. Measurements: `task-14-results.md` in the same folder (git-ignored).

## Task 14 so far (eurweb)
- Against 2.40.1, beta.5 passed: everyday views, the aged slider, the first view after a save, bursts of 4 visitors (about 2 s faster, nobody waits for a refresh), the WP Rocket preload race (2.40.1 saved old lists, beta.5 never did), identical posts with the cache on and off.
- The first view after Mai's cache is emptied costs up to about 1 ms per grid more than 2.40.1. Accepted by Mike; the bar was reworded.
- The grid cache saves about 1.9 s per eurweb page when WordPress's own query cache is cold, and nothing measurable when it is warm or on a small site.

## Next
1. Task 14 part B, the 10-minute busy-site test on eurweb against 2.40.1 (running when this was written; results go to `task-14-results.md`).
2. Part C: larrybrownsports with a local Redis drop-in on Redis database 2 (wp-config backup, `wp redis enable`, then `wp redis disable`, `cp` + `cmp`, `redis-cli -n 2 flushdb`), and the no-Redis duplicate count on a small site.
3. Part D: two small sites without Redis (churn), visitsleepyhollow (event grids render the same as with the cache off), identical IDs.
4. Write the results into the spec under "Results" and commit. Stop and tell Mike if any bar is missed.
5. Task 15, release. Every push, the `v0.5.0` tag and `npm run beta` need Mike's yes, each time.
6. After beta.5: `TODO.md`.

## Blocked / waiting on
- Mike runs the cleanup (auto mode blocks it):
  - `git -C ~/Plugins/mai-engine worktree remove ~/Plugins/mai-engine-grid-loading`
  - `git -C ~/Plugins/mai-engine branch -d feat/grid-load-kept-posts`
- Hindsight is switched off on purpose for the test window: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), and `launchctl` service `com.jivedig.hindsight` booted out and disabled. It leaked stuck Claude processes and pushed load above 200. Turn it back on only when Mike says.

## Verify
- mai-cache: `composer test-unit` in `~/LocalPackages/mai-cache` (99 tests).
- Mai Engine: `composer test-unit` (235, 11 skipped libxml goldens) and `composer test-integration` (245). Both also pass with `--order-by=random`.

## Gotchas
- Speed tests follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: `wp-config.php` backup and `cmp` restore (never `wp config set`), wait for a 1-minute load under 20, never `wp cache flush` on Redis sites, `/bin/rm` for temp files (`rm` is aliased to Trash).
- Switching a site between versions can run Mai's upgrade routine and empty caches. Reload PHP-FPM (`kill -USR2`), probe, warm, then time.
- Integration tests: every test runs with a queue whose `DONOTCACHEPAGE` step does nothing, and a check fails any test that defines the real constant in the main process.
- `run_queue()` catches `Throwable`, so an assertion inside a hook callback during a job is swallowed. Assert after `run_queue()` returns.
- Local eurweb and larrybrownsports symlink `wp-content/plugins/mai-engine` to this checkout.
