# State
Updated: 2026-10-04 by Claude

## Now
Spec drafted and revised after three reviews: `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`. One walk question open (tie direction on ascending sorts, spec "Ties"), then Mike's review. No code written.

- Mai post grid queries, and Mai's ID-only copy, would send an `EXISTS` form instead of WordPress's join plus `GROUP BY`, only when every check proves no plugin touched the SQL. It replaces the old `Mai_Post_Grid_Query_Optimizer` under its name and its filter `mai_post_grid_optimize_query`, now on by default.
- Mike decided (2026-10-04): test MariaDB too and give it the swap only on versions that pass; on by default; cover several taxonomy filters and IN mixed with NOT IN; cover grids without excludes; cover custom taxonomies; totalprosports after eurweb and larrybrownsports. Nothing fragile or brittle, and it has to run on 5,000 sites.
- Read-only findings are in the spec under "What we found": live eurweb's MySQL 8.0.46 picks the fast plan, the registry is all MySQL 8.0.45+, and no fleet plugin would break the swap.

2.41.0-beta.5 is released and live on larrybrownsports.com and eurweb.com since 2026-10-04 (spec `docs/specs/2026-10-01-grid-cache-beta-5.md`).

## Next
1. Mike reviews the spec. On approval, write the plan in `docs/plans/` with the writing-plans skill.
2. Build per the plan: tests first, then the Docker runs (MySQL 8.0, 8.4, MariaDB 10.6, 10.11, 11.4, 11.8) and the speed bar in the spec.
3. Keep watching beta.5 on live: PHP error logs, page timings, grids showing the right posts after a save.
4. Before 2.41.0 final, check MySQL's buffer pool size on the main hosts (`TODO.md`, beta.5 spec Risks).
5. The rest of `TODO.md`.

## Blocked / waiting on
- Mike's review of the spec.
- Hindsight is off on purpose: top-level `"disabled": true` in `~/.agents/hindsight/coding-agent.json` (backup `/tmp/hindsight-coding-agent.json.bak`), `launchctl` service `com.jivedig.hindsight` booted out and disabled. Turn it back on only when Mike says.

## Verify
- Mai Engine: `composer test-unit` (235, 11 skipped libxml goldens) and `composer test-integration` (245). Both pass with `--order-by=random`.
- `php vendor/bin/deployable-guard check` passes.
- Integration tests take the database from `WP_TESTS_DB_HOST` (and `_NAME`, `_USER`, `_PASS`), so the same suite can point at a Docker container.

## Gotchas
- `WP_Query` has a magic `__get()`, so `$query->prop['key'] = $value` on an unset property is silently dropped. Assign whole values.
- `WP_Tax_Query::get_sql()` never resets its alias list. Build a fresh `WP_Tax_Query` to get core's tax SQL again.
- Local eurweb and larrybrownsports run `SCRIPT_DEBUG` true, so mai-cache refuses to store and no grid defers or runs a copy. Any probe follows `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: back up `wp-config.php`, set `WP_DEVELOPMENT_MODE` `''` and `SCRIPT_DEBUG` false, restore with `cp`, check with `cmp`.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'grid' )->flush()` through `wp eval`.
- `mai-sites run` treats `wp db query "DESCRIBE ..."` as a read. `EXPLAIN` is not on its read list, and `DESCRIBE` is the same statement in MySQL.
- `npm run beta` ends with `composer install`, which leaves a dev autoloader. Run `composer dump-autoload --no-dev`. The committed autoloader must be no-dev, or every site crashes.
- Scratch from 2026-10-04 in `/tmp/t15-*` (live EXPLAIN output, probes, `wp-config.php` backups). Safe to delete once the spec is approved.
