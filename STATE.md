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
1. Mike answers two questions, one at a time: how to start Docker (walk item sent 2026-10-05), then the tiebreaker direction on ascending date and author sorts (`post_date ASC, ID DESC` forces a full sort, 25 to 50 ms instead of under 1 ms).
2. Task 8: Docker runs on MySQL 8.0 (several 8.0 tags, oldest to newest), 8.4 and MariaDB 10.6, 10.11, 11.4, 11.8; set `MYSQL_MIN` and `$mariadb_min` to the lowest versions that pass; record results in the spec.
3. Task 9 Steps 4 to 6: final verification, this file, commit.
4. Mike decides how the branch reaches `develop`, then the beta push commands for eurweb and larrybrownsports, then totalprosports.
5. From before: watch beta.5 on live; check MySQL's buffer pool size on the main hosts before 2.41.0 final; the rest of `TODO.md`.

## Blocked / waiting on
- Mike's Docker answer (Task 8) and the tiebreaker decision.
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
- Docker Desktop opens its dashboard on start (`openUIOnStartupDisabled` false); do not start it without Mike's answer.
- `npm run beta` ends with `composer install`, which leaves a dev autoloader. Run `composer dump-autoload --no-dev`. The committed autoloader must be no-dev, or every site crashes.
- The SDD ledger and reports for this plan are in `.superpowers/sdd/2026-10-04-grid-taxonomy-query-rewrite/` (git-ignored). Raw timing data in `/tmp/t16-pages/` and `/tmp/t15-*`.
