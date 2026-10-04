# State
Updated: 2026-10-04 by Claude

## Now
2.41.0-beta.5 is released. `npm run beta` pushed `develop` and `beta` on 2026-10-04 (commit `6cd628ef3`, "Beta release"). It ships the grid cache work and mai-cache 0.6.0, loaded by mai-package-loader 0.1.0.

- Spec: `docs/specs/2026-10-01-grid-cache-beta-5.md`. Local verification is under "Results", and two accepted costs are under "Risks". Plan: `docs/plans/2026-10-01-grid-cache-beta-5.md`.
- `develop` has local commits not on GitHub: STATE.md updates and the `installed.php` reference lines that `npm run beta`'s closing `composer install` rewrote. They go out with the next push.
- The working ledger and raw measurements stay in `.superpowers/sdd/2026-10-01-grid-cache-beta-5/` and `/tmp/t14-*` (git-ignored) until Mike decides whether to keep them.

## Next
1. Watch beta.5 on real sites: PHP error logs, page timings, and grids showing the right posts after a save. Live larrybrownsports.com and eurweb.com run beta.5 since 2026-10-04 (`mai-sites push <site> .` from this repo, then `mai-sites run <site> -- wp mai flush`, because a push skips the flush a plugin update does). Both checked healthy after the flush.
2. Before 2.41.0 final, check MySQL's buffer pool size on the main hosts (`TODO.md`, spec Risks).
3. Next piece of work: make "current category" grid queries cheap on big sites, safely, with fallbacks. Read `docs/ideas/2026-10-04-grid-related-posts-query-cost.md` (measured problem, two fixes, the fallback design Mike asked for). Start with its read-only steps: one `EXPLAIN` on live eurweb through `mai-sites run`, and a fleet scan for plugins hooking the SQL clause filters. Then a spec in `docs/specs/` before any code.
4. The rest of `TODO.md`: refresh every grid after the page (an idea to test), the local deployable-guard update, and the loader's self-version compare.

## Blocked / waiting on
- Hindsight is switched off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- Mai Engine: `composer test-unit` (235, 11 skipped libxml goldens) and `composer test-integration` (245). Both pass with `--order-by=random`.
- `php vendor/bin/deployable-guard check` passes. `vendor/composer/autoload_files.php` loads `maithemewp/mai-package-loader/init.php`, not mai-cache's old `init.php`.
- Local sites load `Mai\Cache\Cache` from this repo's `vendor/`: `wp eval 'echo (new ReflectionClass("Mai\\Cache\\Cache"))->getFileName();'`.

## Gotchas
- `npm run beta` ends with `composer install`, which leaves a dev autoloader in the working tree and rewrites two `reference` lines in `vendor/composer/installed.php`. Run `composer dump-autoload --no-dev` to restore the committed autoloader. Commit the `installed.php` lines with the next change, as deployable-guard's README says.
- The committed autoloader must be no-dev. A dev autoloader crashes every site (a876863db, fixed by 4c8c6131e).
- Speed tests follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: `wp-config.php` backup and `cmp` restore, load under 20, never `wp cache flush` on Redis sites, `/bin/rm` for temp files.
- Switching a site between 2.40.1 and beta.5 orphans beta.5 notes. Clean up with `delete_transient()` then `mai_cache( 'grid' )->flush()`.
- `run_queue()` catches `Throwable`, so assertions inside hook callbacks during a job are swallowed. Assert after `run_queue()` returns.
