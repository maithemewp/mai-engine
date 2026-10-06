# State
Updated: 2026-10-05 by Claude

## Now
The grid taxonomy optimizer is built, reviewed and measured on branch `grid-taxonomy-optimizer` (from `develop` at `ce73a9f52`, not merged, not pushed). Spec `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`, plan `docs/plans/2026-10-04-grid-taxonomy-query-rewrite.md`.

- Mai post grid queries and Mai's ID-only copy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, only when every check proves the same posts. On by default (`mai_post_grid_optimize_query`).
- On for MySQL 8.0.16 and newer (measured on 8.0.16, 8.0.28, 8.0.46 and 8.4.11 in Docker, and 9.7.1 locally). Off for MariaDB. Date, author and ID sorts only.
- Tiebreaker: date and author sorts break ties by ID in their own direction, other sorts newest first. It returns a value that is not a string, or a query that is not a `WP_Query`, unchanged.
- The second pr-review-toolkit run's fix wave is in: the guard, more tie tests, hardened `bin/grid-optimizer-pairs.php` and `bin/grid-optimizer-replay.php`, doc fixes. Report: `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/toolkit-2-fix-report.md`.
- The replay counts a miss only when at least 2 of 3 reruns alone miss too (Mike, 2026-10-05).
- Re-measure with the hardened tools: MySQL 8.0.46 and emulated 8.0.16 passed every timed pair on all three sites (0 differ, 0 weedout; on 8.0.16 the classic EXPLAIN fallback read 272 plans, no weedout). It stopped on local MySQL 9.7.1: eurweb `big, ID ASC` swapped took about 63 ms against about 1 ms today, and `big, ID DESC` in its full form at `LIMIT 0, 1000` 77 against 30 ms, every rerun too. The swapped plan there materializes 478,486 term rows. In Task 7 the same server and statement walked the posts (0.73 ms). larrybrownsports and the small site were not replayed locally.
- 2.41.0-beta.5 is still what live eurweb and larrybrownsports run.

## Next
1. Mike decides about ID sorts (walk it): local MySQL 9.7.1 flipped `big, ID ASC` to a materialized plan about 60 times slower, the same shape that turned MariaDB off. Options include taking `ID` out of `SORT_COLUMNS`. Numbers in `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/toolkit-2-fix-report.md`.
2. Then finish the local 9.7.1 replays (larrybrownsports, small site MyISAM and InnoDB), write the dated "Rerun with the hardened tools" note in the spec's Results, and rewrite this file. Driver `/tmp/task8-opt/tk2r-local.sh`; pairs are current in `/tmp/mai-optimizer-pairs-*.test.jsonl`; dumps must be made again for the InnoDB copy.
3. The merge to local `develop` waits on the controller. All branch commits and the 13 local `develop` commits ahead of `origin/develop` are Mike's. Nothing is pushed.
4. Beta push for eurweb and larrybrownsports, then totalprosports. Next header `2.41.0-beta.6`. Steps in `docs/specs/2026-10-01-grid-cache-beta-5.md` under "Release". `npm run beta` pushes, so it needs Mike's explicit yes. Afterwards `composer dump-autoload --no-dev`.
5. Mike corrects post 203 on live larrybrownsports (a live write he runs himself):
   ```
   mai-sites run larrybrownsports.com -- wp eval 'global $wpdb; $wpdb->update( $wpdb->posts, [ "post_date" => "2007-02-28 04:00:58" ], [ "ID" => 203 ] ); clean_post_cache( 203 ); echo get_post( 203 )->post_date, "\n";'
   ```
   Then a session confirms with `mai-sites run larrybrownsports.com --yes --safe-to-rerun -- wp db query "SELECT ID, post_date FROM wp_posts WHERE ID = 203"`.
6. Before 2.41.0 final: check the buffer pool size on the main hosts, then the rest of `TODO.md`.

## Blocked / waiting on
- Mike's decision on ID sorts after the local MySQL 9.7.1 misses, and the controller on the merge to `develop`.
- Hindsight is off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json`, `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- `composer test-unit` (247 tests, 11 skipped libxml goldens), `composer test-integration -- --order-by=random` (685 tests), `php vendor/bin/deployable-guard check` (OK). All pass as of 2026-10-05.
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
- MySQL can pick a different plan for the same statement on the same server from one day to the next (local 9.7.1, `big, ID ASC`), so one passing run per version does not settle a sort.
- `npm run beta` leaves a dev autoloader. Run `composer dump-autoload --no-dev`, or every site crashes.
- Raw data lives in `/tmp/task8-opt/` (this wave's files start `tk2-`), gone after a reboot.
