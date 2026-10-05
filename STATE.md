# State
Updated: 2026-10-05 by Claude

## Now
The grid taxonomy optimizer is built, reviewed and measured on branch `grid-taxonomy-optimizer` (from `develop` at `ce73a9f52`, not merged, not pushed). Spec `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`, plan `docs/plans/2026-10-04-grid-taxonomy-query-rewrite.md`.

- Mai post grid queries and Mai's ID-only copy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, only when every check proves the same posts. On by default (`mai_post_grid_optimize_query`).
- On for MySQL 8.0.16 and newer (measured on 8.0.16, 8.0.28, 8.0.46 and 8.4.11 in Docker, and 9.7.1 locally). Off for MariaDB.
- Date, author and ID sorts only. Other sorts keep today's statement, because they were slower on a mid-size category in the speed test (spec "Which grids").
- Tiebreaker: date and author sorts break ties by ID in their own direction. Other sorts break ties newest first.
- Key numbers: a fully cold eurweb article is 1,769 ms with the swap on against 3,576 ms off. Article grid statements drop from about 220 ms to about 1.1 ms on MySQL.
- 2.41.0-beta.5 is still what live eurweb and larrybrownsports run.

## Next
1. Mike decides how the branch reaches `develop` (walk it). Facts: local `develop` is at `ce73a9f52`, 13 commits ahead of `origin/develop` (`6cd628ef3` as last fetched), all by Mike. The branch's 32 commits on top of `develop` are all by Mike too, so a fast-forward of `develop` carries only Mike's work. Nothing is pushed.
2. Beta push commands for eurweb and larrybrownsports, then totalprosports. The next header is `2.41.0-beta.6`. The release steps are in `docs/specs/2026-10-01-grid-cache-beta-5.md` under "Release". `npm run beta` pushes `develop` and `beta`, so it needs Mike's explicit yes. Afterwards run `composer dump-autoload --no-dev` (see the dev autoloader gotcha).
3. Mike corrects post 203 on live larrybrownsports. This is a live write, so Mike runs it himself. It updates only `post_date`, clears that post's cache and prints the new date as its own check:
   ```
   mai-sites run larrybrownsports.com -- wp eval 'global $wpdb; $wpdb->update( $wpdb->posts, [ "post_date" => "2007-02-28 04:00:58" ], [ "ID" => 203 ] ); clean_post_cache( 203 ); echo get_post( 203 )->post_date, "\n";'
   ```
   After he runs it, a session confirms with this read:
   ```
   mai-sites run larrybrownsports.com --yes --safe-to-rerun -- wp db query "SELECT ID, post_date FROM wp_posts WHERE ID = 203"
   ```
4. Watch beta.5 on live.
5. Before 2.41.0 final: check the buffer pool size on the main hosts, then the rest of `TODO.md`.

## Blocked / waiting on
- Mike's decision on how the branch reaches `develop`.
- Hindsight is off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- `composer test-unit` (247 tests, 11 skipped libxml goldens), `composer test-integration -- --order-by=random` (663 tests), `php vendor/bin/deployable-guard check` (OK). All pass as of 2026-10-05.
- `GridCacheAfterPageTest` fails 2 tests on MySQL 8.0.28, MySQL 8.0.16, MariaDB 11.4 and MariaDB 11.8, also on `develop` (see `TODO.md`). That is not a regression.
- Integration tests on another database: `WP_TESTS_DB_HOST=127.0.0.1:<port> composer test-integration`. Add `WP_TESTS_MARIADB_MIN=10.6.0` on MariaDB.
- Measurement tools in `bin/`: `grid-optimizer-probe.php` (temporary mu-plugin), `grid-optimizer-pairs.php` (`wp eval-file`), `grid-optimizer-replay.php` (plain PHP CLI). Usage is in each file's docblock.
- The build ledger, task reports and review diffs are in `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/` (git-ignored).

## Gotchas
- `WP_Query` has a magic `__get()`: `$query->prop['k'] = $v` on an unset property is silently dropped. Assign whole values.
- `WP_Tax_Query::get_sql()` never resets its alias list. The optimizer rebuilds with a fresh object.
- Local eurweb and larrybrownsports run `SCRIPT_DEBUG` true, so no grid defers. Probes and timings follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: back up `wp-config.php`, restore with `cp`, check with `cmp`.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'grid' )->flush()` through `wp eval`.
- Local MySQL has a 128 MB buffer pool against eurweb's 896 MB posts table, so small timings are noisy. Docker containers need a big pool (for example `--innodb-buffer-pool-size=2G`) and an idle machine.
- MySQL 8.0 and 8.4 have bug 120943 (duplicate weedout drops rows). Every swapped `EXISTS` carries `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */` on MySQL.
- `mai-sites run` treats `wp db query "DESCRIBE ..."` as a read. `EXPLAIN` is not on its read list.
- Docker Desktop's open-dashboard-on-start is off (backup `/tmp/docker-settings.json.bak`). Start it only with `open -g -a Docker`.
- The Homebrew mysql 9.7 client cannot log in to MariaDB containers. Use `docker exec`.
- MySQL before 8.0.28 has no arm64 image. Run it with `--platform linux/amd64`.
- The replay's bar is 2 ms or 10%, whichever is larger (Mike, 2026-10-05).
- `npm run beta` ends with `composer install`, which leaves a dev autoloader. Run `composer dump-autoload --no-dev`. The committed autoloader must be no-dev, or every site crashes.
- Raw data is in `/tmp/t16-pages/` (whole-page timings) and `/tmp/task8-opt/` (Docker run numbers), and `/tmp/t15-*` holds the earlier probes. The spec's Results cites the first two. All of `/tmp` is outside the repo and is gone after a reboot.
