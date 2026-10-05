# State
Updated: 2026-10-05 by Claude

## Now
The grid taxonomy optimizer is built on branch `grid-taxonomy-optimizer` (from `develop` at `ce73a9f52`, not merged, not pushed). Spec `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`, plan `docs/plans/2026-10-04-grid-taxonomy-query-rewrite.md`.

- Mai post grid queries and Mai's ID-only copy send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, only when every check proves the same posts. On by default (`mai_post_grid_optimize_query`). MySQL 8.0.16+ only for now; MariaDB off until Task 8.
- Only date, author and ID sorts are swapped. Other sorts were slower on a mid-size category (MySQL 9.7.1) and keep today's statement.
- Plan Tasks 1 to 7, 7b and 9 Steps 1 to 3 are done, each reviewed. A five-agent pr-review-toolkit review found no wrong-posts path in normal flows; its 44 findings and one measured residual are fixed (last re-check of `21d8b09e1` running at this write).
- Local eurweb (MySQL 9.7.1): a fully cold article view is 1,769 ms with the swap on against 3,576 ms off; no measurable cost when grid notes are current. Numbers in the spec, "Results".
- 2.41.0-beta.5 is still what live eurweb and larrybrownsports run.

## Next
Mike decided on 2026-10-05 (walk): Docker option a (its open-dashboard-on-start setting is now off, backup `/tmp/docker-settings.json.bak`, and it was started with `open -g -a Docker`); tiebreaker option a. Resume with the subagent-driven-development skill on `docs/plans/2026-10-04-grid-taxonomy-query-rewrite.md`; the ledger is `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/progress.md`.

1. Task 7c, the tiebreaker (one implementer plus a task review). In `Mai_Grid::add_grid_orderby_tiebreaker()`: an ORDER BY that already names `{posts}.ID` stays as it is; when the last sort key is `{posts}.post_date` or `{posts}.post_author`, append `, {posts}.ID` in that key's direction; otherwise append `, {posts}.post_date DESC, {posts}.ID DESC`, or only `, {posts}.ID DESC` when the ORDER BY already names `{posts}.post_date`. Update `GridTiebreakerTest`, any same-posts or tiebreaker expectations, spec section 2 and "Ties", and `CHANGES.md:25`. Also move the `swap()` docblock paragraph above `@since` (deferred minor). Measured: date ASC with `ID DESC` took about 190 ms more per rebuild on local eurweb's biggest category than with `ID ASC`.
2. Task 8: check `docker info` answers first. Run one container at a time with a large buffer pool (for example `--innodb-buffer-pool-size=2G`): MySQL 8.0 at several tags from the oldest available up to the latest 8.0, then 8.4, then MariaDB 10.6, 10.11, 11.4 and 11.8. Per container: integration suite through `WP_TESTS_DB_HOST` (MariaDB with `WP_TESTS_MARIADB_MIN=10.6.0`), load the three sites' tables, verify row counts, capture and replay pairs. Set `MYSQL_MIN` and `$mariadb_min` to the lowest versions that pass everything (and fix `test_mariadb_is_off_by_default`), record results in the spec, stop and remove every container. If a covered shape or a MySQL version misses the bar, stop and walk it with Mike.
3. Task 9 Steps 4 to 6: final verification, rewrite this file, commit.
4. Walk Mike through how the branch reaches `develop`, then the beta push commands for eurweb and larrybrownsports, then totalprosports.
5. From before: watch beta.5 on live; check MySQL's buffer pool size on the main hosts before 2.41.0 final; the rest of `TODO.md`.

## Blocked / waiting on
- Nothing on Mike right now; both walk items are answered.
- Hindsight is off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- `composer test-unit` (245, 11 skipped libxml goldens) and `composer test-integration -- --order-by=random` (639). `php vendor/bin/deployable-guard check` passes.
- Integration tests on another database: `WP_TESTS_DB_HOST=127.0.0.1:<port> composer test-integration`; add `WP_TESTS_MARIADB_MIN=10.6.0` on MariaDB.
- Measurement tools: `bin/grid-optimizer-probe.php` (temporary mu-plugin), `bin/grid-optimizer-pairs.php` (`wp eval-file`), `bin/grid-optimizer-replay.php` (plain PHP CLI). Usage in each file's docblock.

## Gotchas
- `WP_Query` has a magic `__get()`: `$query->prop['k'] = $v` on an unset property is silently dropped. Assign whole values.
- `WP_Tax_Query::get_sql()` never resets its alias list; the optimizer rebuilds with a fresh object.
- Local eurweb and larrybrownsports run `SCRIPT_DEBUG` true, so no grid defers. Probes and timings follow `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: back up `wp-config.php`, restore with `cp`, check with `cmp`.
- Never `wp cache flush` on Redis sites; use `mai_cache( 'grid' )->flush()` through `wp eval`.
- Local MySQL has a 128 MB buffer pool against eurweb's 896 MB posts table, so small timings are noisy; Docker containers for Task 8 need a big pool and an idle machine.
- MySQL 8.0 and 8.4 have bug 120943 (duplicate weedout drops rows); every swapped `EXISTS` carries `/*+ NO_SEMIJOIN(DUPSWEEDOUT) */` on MySQL.
- `mai-sites run` treats `wp db query "DESCRIBE ..."` as a read; `EXPLAIN` is not on its read list.
- Docker Desktop's dashboard-on-start is off now; start it only with `open -g -a Docker`.
- `npm run beta` ends with `composer install`, which leaves a dev autoloader. Run `composer dump-autoload --no-dev`. The committed autoloader must be no-dev, or every site crashes.
- The SDD ledger and reports for this plan are in `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/` (git-ignored). Raw timing data in `/tmp/t16-pages/` and `/tmp/t15-*`.
