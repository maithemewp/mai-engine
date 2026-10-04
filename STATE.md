# State
Updated: 2026-10-04 by Claude

## Now
The grid cache work for 2.41.0-beta.5 is built and reviewed on `develop`, not pushed. Plan Tasks 1 to 13 are done. Task 14 (local verification) has finished every measurement, and three decisions are with Mike before the results go into the spec. Task 15 (release) is left.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`.
- Every comparison is against 2.40.1 (`/tmp/mai-engine-2401`), the last real release.
- Ledger with every ruling and measurement: `.superpowers/sdd/2026-10-01-grid-cache-beta-5/progress.md`. Measurements: `task-14-results.md`. Drafted spec section: `results-draft.md` (all three folder-local, git-ignored).

## Waiting on Mike (walk, one at a time)
1. **eurweb busy-site test, waits for a free PHP worker:** raw miss (55 of 7,009 vs 2 of 7,110 over 250 ms), 54 in two windows matching machine-wide stalls; runs with stall detectors 1 vs 1. Recommended: accept.
2. **larrybrownsports, first view after a save slower on 2 of 6 pages** (burrow +119.7 ms). Measured cause: the ID copy has no `NOT IN`, so MySQL's row estimate doubles, and with `wp_term_relationships` out of the 128 MB buffer pool it picks a plan that reads `wp_posts` from disk. At 1 GB beta.5 is faster (pooled −273.8 ms). Production depends on each DB server's buffer pool; MariaDB untested.
3. **Release step changed outside this session:** commit `002fa7c0a` (2026-10-03 20:45) says mai-cache shipped as `v0.6.0` (tagged and pushed) through mai-package-loader, and rewrote plan Task 15. The spec (lines 105, 240, 298-299), `CHANGES.md:8` and this file's old Next still say 0.5.0. v0.6.0's `src/` differs from the bundled copy only by five dropped `ABSPATH` lines (checked).

## Next
1. Apply Mike's three answers. Insert `results-draft.md` into the spec after "Verification on local sites", updating the two open bars. Commit "Grid cache beta.5: local verification results".
2. Task 15 as the plan now reads (mai-cache `^0.6` through Composer and mai-package-loader), after Mike confirms item 3. That is new loading code: run both suites, `deployable-guard check`, a review, and a local smoke test on eurweb before the beta.
3. Every push and `npm run beta` need Mike's yes, each time.
4. After beta.5: `TODO.md`.

## Blocked / waiting on
- Mike runs the cleanup (auto mode blocks it):
  - `git -C ~/Plugins/mai-engine worktree remove ~/Plugins/mai-engine-grid-loading`
  - `git -C ~/Plugins/mai-engine branch -d feat/grid-load-kept-posts`
- Hindsight is switched off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- Mai Engine: `composer test-unit` (235, 11 skipped libxml goldens) and `composer test-integration` (245). Both pass with `--order-by=random`.
- Every local site used in Task 14 was checked clean: symlinks on the repo, `wp-config.php` `cmp` clean, probes removed, no Redis drop-in, Redis database 2 empty, MySQL buffer pool back to 128 MB, 0 leftover grid rows.

## Gotchas
- Speed tests follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: `wp-config.php` backup and `cmp` restore, load under 20, never `wp cache flush` on Redis sites, `/bin/rm` for temp files.
- Switching a site between 2.40.1 and beta.5 orphans beta.5 notes (2.40.1's flush rotates the token without deleting rows). Clean up with `delete_transient()` then `mai_cache( 'grid' )->flush()`.
- Bump the post types a site's grids actually show; agiindustries shows none of type `post`.
- `run_queue()` catches `Throwable`, so assertions inside hook callbacks during a job are swallowed. Assert after `run_queue()` returns.
