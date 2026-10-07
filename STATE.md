# State
Updated: 2026-10-06 by Claude

## Now
The grid taxonomy optimizer is built, reviewed and measured, and merged into local `develop` on 2026-10-06 (merge commit `6863ebc30`; the branch is deleted). Nothing is pushed to GitHub. On 2026-10-07 Mike pushed local `develop` as is to live eurweb.com and larrybrownsports.com with `mai-sites push`, so both run next's code while their header still says `2.41.0-beta.5`. A read-only check that morning found no Mai Engine lines and no fatal errors in either error log, and the grids matched on fresh renders. Spec `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`, plan `docs/plans/2026-10-04-grid-taxonomy-query-rewrite.md`.

- Mai post grid queries and Mai's ID-only copy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, only when every check proves the same posts. On by default (`mai_post_grid_optimize_query`).
- On for MySQL 8.0.16 and newer. Off for MariaDB.
- Date and author sorts only. ID sorts left on 2026-10-06 (Mike): local MySQL 9.7.1 flipped an ID sort's plan between runs, from about 1 ms to 63 ms on eurweb's biggest category. The grid setting offers no ID sort.
- Tiebreaker: date and author sorts break ties by ID in their own direction, other sorts newest first. Values that are not strings, or queries that are not a `WP_Query`, come back unchanged.
- The second pr-review-toolkit run's fix wave is finished. The pairs and replay tools are hardened: full form at `LIMIT 0, 1000`, classic EXPLAIN fallback, row-count check, and a miss counts only when 2 of 3 reruns alone miss too (Mike, 2026-10-05). Report: `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/toolkit-2-fix-report.md`.
- Rerun with those tools: MySQL 8.0.46, 8.0.16 and local 9.7.1 met the bar on every timed date and author pair of all three sites. 0 differ apart from post 203, and 0 weedout. Spec "Results", "Rerun with the hardened tools".
- 2.41.0-beta.5 is still what live eurweb and larrybrownsports run.
- Next timed against current on local eurweb and larrybrownsports (2026-10-06): slower on eurweb's first view after Mai's cache is emptied and on larrybrownsports' burrow article, faster or the same elsewhere. Spec "Results", "Next against current (2026-10-06)".

## Next
1. Local `develop` is ahead of GitHub's `develop` by 60 commits, all Mike's. None are on GitHub yet. The beta push below publishes them.
2. Beta push for eurweb and larrybrownsports, then totalprosports. Next header `2.41.0-beta.6`. Steps in `docs/specs/2026-10-01-grid-cache-beta-5.md` under "Release". `npm run beta` pushes, so it needs Mike's explicit yes. Afterwards `composer dump-autoload --no-dev`.
3. MariaDB stays off. Without ID sorts its Task 8 pairs met the bar, but the hardened replay (full-form show-all pairs, row-count checks, the rerun rule) never ran on MariaDB, and its plan reads every matching term row first, the shape that made local MySQL 9.7.1 miss on the full form. Before turning it on, rerun the hardened replay on MariaDB 10.6, 10.11, 11.4 and 11.8. (`TODO.md`)
4. Before 2.41.0 final: check the buffer pool size on the main hosts, then the rest of `TODO.md`.

## Blocked / waiting on
- Mike's yes for the beta push.
- Hindsight is off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json`, `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- `composer test-unit` (247 tests, 11 skipped libxml goldens), `composer test-integration -- --order-by=random` (687 tests), `php vendor/bin/deployable-guard check` (OK). All pass as of 2026-10-06.
- `GridCacheAfterPageTest` fails 2 tests on MySQL 8.0.28, 8.0.16, MariaDB 11.4 and 11.8, also on `develop` (see `TODO.md`). Not a regression.
- Integration tests on another database: `WP_TESTS_DB_HOST=127.0.0.1:<port> composer test-integration`. Add `WP_TESTS_MARIADB_MIN=10.6.0` on MariaDB.
- Tools in `bin/`: `grid-optimizer-probe.php` (temporary mu-plugin), `grid-optimizer-pairs.php` (`wp eval-file`), `grid-optimizer-replay.php` (plain PHP CLI). Usage and exit codes are in each docblock.

## Gotchas
- `WP_Query` has a magic `__get()`: `$query->prop['k'] = $v` on an unset property is silently dropped. Assign whole values.
- Local eurweb and larrybrownsports run `SCRIPT_DEBUG` true, so no grid defers. Probes and timings follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'grid' )->flush()` through `wp eval`.
- Local MySQL has a 128 MB buffer pool against eurweb's 896 MB posts table, so small timings there are noisy. Docker containers get `--innodb-buffer-pool-size=2G`.
- Every swapped `EXISTS` carries `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */` on MySQL (bug 120943).
- Docker Desktop: start only with `open -g -a Docker`. The Homebrew mysql 9.7 client cannot log in to MariaDB containers, use `docker exec`. MySQL before 8.0.28 needs `--platform linux/amd64`.
- The replay's bar is 2 ms or 10%, whichever is larger, and a miss counts only when 2 of 3 reruns alone miss too.
- MySQL can pick a different plan for the same statement on the same server from one run to the next (local 9.7.1, `big, ID ASC`), so one passing run per version does not settle a sort.
- `npm run beta` leaves a dev autoloader. Run `composer dump-autoload --no-dev`, or every site crashes.
- Raw data lives in `/tmp/task8-opt/` (this wave's files start `tk2-`, `tk2r-` and `tk2id-`), gone after a reboot.
