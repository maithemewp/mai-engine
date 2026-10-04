# State
Updated: 2026-10-04 by Claude

## Now
2.41.0-beta.5 (the grid cache work) is ready for `npm run beta` on `develop`, waiting on Mike's yes. Nothing is pushed yet. `develop` is 62 commits ahead of `origin/develop` and 0 behind.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`, with local verification under "Results" and two accepted costs under "Risks". Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md` (Task 15 is the release).
- mai-cache ships as 0.6.0 (tagged on GitHub), loaded by mai-package-loader 0.1.0 through Composer. Its code matches what was measured, minus five `ABSPATH` lines.
- Every comparison was against 2.40.1. The ledger with every ruling and measurement is `.superpowers/sdd/2026-10-01-grid-cache-beta-5/progress.md` (git-ignored).

## Next
1. Ask Mike, then run `npm run beta`. It builds, commits "Beta release", runs `deployable-guard check`, and pushes `develop` and `beta`.
2. After it: restore `vendor/composer/installed.php` if only its `reference` changed (see Gotchas), and check `git status` is clean.
3. Watch the beta on real sites. Before 2.41.0 final, check MySQL's buffer pool size on the main hosts (`TODO.md`).
4. After beta.5: the rest of `TODO.md`.

## Blocked / waiting on
- Mike's yes for `npm run beta`.
- Hindsight is switched off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.
- Opus subagents hit the weekly limit until 2026-10-06 11:00 ET. Use Sonnet for reviews until then.

## Verify
- Mai Engine: `composer test-unit` (235, 11 skipped libxml goldens) and `composer test-integration` (245). Both pass with `--order-by=random`.
- `php vendor/bin/deployable-guard check` passes. `vendor/composer/autoload_files.php` loads `maithemewp/mai-package-loader/init.php`, not mai-cache's old `init.php`.
- Local sites load `Mai\Cache\Cache` from this repo's `vendor/`: `wp eval 'echo (new ReflectionClass("Mai\\Cache\\Cache"))->getFileName();'`.

## Gotchas
- `vendor/composer/installed.php` is tracked now (the loader reads it, and deployable-guard v1.1.0 requires it). Any `composer install`, including the one at the end of `npm run beta`, rewrites its `reference` to the current commit and dirties the tree. If only `reference` changed, restore it with `git checkout vendor/composer/installed.php`.
- The committed autoloader must be no-dev. A dev autoloader crashes every site (a876863db, fixed by 4c8c6131e). Run `composer dump-autoload --no-dev` after anything that regenerates it.
- Speed tests follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: `wp-config.php` backup and `cmp` restore, load under 20, never `wp cache flush` on Redis sites, `/bin/rm` for temp files.
- Switching a site between 2.40.1 and beta.5 orphans beta.5 notes. Clean up with `delete_transient()` then `mai_cache( 'grid' )->flush()`.
- `run_queue()` catches `Throwable`, so assertions inside hook callbacks during a job are swallowed. Assert after `run_queue()` returns.
