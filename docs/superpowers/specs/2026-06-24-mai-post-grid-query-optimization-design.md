# Mai Post Grid Query Optimization — Design

Date: 2026-06-24
Status: Design approved, pending implementation plan
Component: mai-engine. New class `Mai_Post_Grid_Query_Optimizer` in `lib/classes/class-mai-post-grid-query-optimizer.php`, wired entirely through hooks. `Mai_Grid` (`lib/classes/class-mai-grid.php`) is not modified.
Target runtime: PHP 8.1+

## Background

Mai post grids that ask for "newest N posts in any of these taxonomy terms" generate the standard WordPress tax-query SQL: a `LEFT JOIN` to `wp_term_relationships`, `GROUP BY wp_posts.ID` to dedupe the join, `ORDER BY post_date DESC`, plus `SQL_CALC_FOUND_ROWS`. On large sites this is expensive.

On the site that prompted this (eurweb: ~374k posts, broad multi-term grids, many grids per page), these queries dominated database load. The top two query shapes in `performance_schema` accounted for roughly 4,200 seconds of total time and 9+ billion rows examined, each call examining ~700k rows to return a handful. `EXPLAIN` attributed the cost to `Using temporary; Using filesort` over ~124k matched rows. This is a scale problem, not a defect: the identical query is trivial on a small site, which is why it only surfaces at scale (and is why the thousands of other Mai sites running the same feature do not see it).

An unrelated bottleneck found during the same investigation (a PublishPress Revisions count query missing its `pp_revisions` index) is resolved separately and is not part of this spec.

## Goal

Make the Mai post grid tax query fast on every render, on every site, with no caching. Measured on eurweb (MySQL 8.0.45), the rewrite takes the query from ~0.65s to ~15ms, with a stable plan.

## Non-goals

- No result caching, no invalidation machinery, no `mai-cache` dependency. Caching was prototyped and rejected: it matches the rewrite only on warm hits, pays the full query on cold misses, churns hard on a constantly-publishing site, depends on a persistent object cache that most customer sites lack, and introduces staleness plus a version counter. The rewrite is flat-fast with none of that.
- No changes to term grids (`WP_Term_Query`). They are out of scope and structurally untouched.
- No optimizer hints and no engine or version specific SQL. The chosen query form is plan-stable on its own and portable across MySQL and MariaDB.

## The decision

Rewrite the simple "IN these terms" grid tax query from `LEFT JOIN` + `GROUP BY` into a correlated scalar subquery in `WHERE`:

```
AND (
  SELECT object_id FROM {prefix}term_relationships
  WHERE object_id = {prefix}posts.ID AND term_taxonomy_id IN (...)
  LIMIT 1
) IS NOT NULL
```

A scalar subquery is not eligible for MySQL's semi-join transform, so the optimizer keeps it correlated, drives from `wp_posts` in `post_date` order via the `type_status_date` index (backward index scan, no temp table, no filesort), checks each row, and stops at `LIMIT`. Verified on eurweb: `EXPLAIN` shows `eur_posts` driven first with `Backward index scan; Using index`, and timing is ~15ms stable across runs. The `EXISTS` form was also tested and is worse by default (MySQL flattens it into a LooseScan semi-join over the full relationships table); the scalar subquery avoids that without any hint, which is why it is the chosen form.

## Design

### Code structure

The optimization is a new, self-contained class `Mai_Post_Grid_Query_Optimizer` (`lib/classes/class-mai-post-grid-query-optimizer.php`), following the existing `class-mai-*.php` / `Mai_*` convention. `class-mai-grid.php` is already 680 lines and `Mai_Grid` already owns construction, rendering, entry iteration, both query builders, and the static exclude-displayed accumulation, so the optimization is extracted rather than added inline. It is wired entirely through hooks and does not modify `Mai_Grid`: it latches onto the existing `mai_post_grid_query_args` filter (`class-mai-grid.php:573`) to flag the query, set `no_found_rows`, and capture the simple-`IN` term filter, then a WordPress query-clause filter injects the scalar subquery and the `ID DESC` tiebreaker. The grid builds the grid; the optimizer makes the query fast; neither reaches into the other. The kill-switch gates registration. This is the same hook-based shape proven by the throwaway eurweb test filter, just additive instead of clause surgery and housed in a proper class.

### Scope and trigger

- Applies only to the post grid query path (`Mai_Grid::get_post_query_args` / `get_query`). Term grids are never considered.
- Applies only to the simple case: a `tax_query` whose clause(s) use the `IN` operator (the common "show posts in these categories/tags" grid, and the slow one).
- Everything else falls through to stock WordPress, unchanged: `NOT IN` / `AND` / `EXISTS` operators, multiple tax clauses with relations, cross-taxonomy combinations a single subquery cannot express, or no `tax_query` at all.
- Gated by the `mai_post_grid_optimize_query` filter (default `true`) so any site can disable it.

### The rewrite

- Emit the term filter as the correlated scalar subquery instead of the standard tax `LEFT JOIN` + `GROUP BY`. The clause is built from the resolved `term_taxonomy_id`s and added to `WHERE`; it is an addition, not surgery on WP's generated SQL. (The throwaway eurweb test filter did clause surgery because it lived outside mai-engine; the production version, building the query itself, does not need to.)
- Resolve `term_taxonomy_id`s honoring `include_children`, identically to stock `WP_Tax_Query`, so results match exactly. The exact resolution mechanism (reuse WP's resolution versus resolving independently) is settled in the implementation plan, under the hard constraint that output must equal stock.
- Set `no_found_rows` on the query (drop `SQL_CALC_FOUND_ROWS`). This is required, not incidental: with the count pass on, MySQL must evaluate the subquery for every matching row just to total them, which removes the early stop. See found-rows handling.

### Ordering determinism

- Append `ID DESC` to the date ordering, giving `ORDER BY post_date DESC, ID DESC`, applied only when the grid orders by date. `ID` is the fourth column of the `type_status_date` index, so the ordered read stays a backward index scan with no filesort. This makes the `LIMIT` boundary deterministic when posts share a `post_date`. Stock WP date-ordered grids are non-deterministic on ties today, so this is a strict improvement and prevents a post flickering in or out between renders.

### Found-rows / pagination handling

- `no_found_rows => true` is a default in `Mai_Grid::get_post_query_args()`, applied to every post grid (it is core grid behavior, not the optimizer's job). `SQL_CALC_FOUND_ROWS` forces a full scan of all matching posts ignoring `LIMIT`, and almost no grid needs that total; dropping it is the broad win that makes plain and `post__not_in` grids fast, independent of the tax rewrite. Keeping it in `Mai_Grid` also means the optimizer kill-switch reverts only the tax rewrite, not this safe default.
- Grids that need the total (numbered pagination, load-more) set `no_found_rows => false` through the existing `mai_post_grid_query_args` filter, which runs at the end of `get_post_query_args` and so overrides the default. The internal-only mai-load-more plugin does this for its grids. No dedicated found-rows filter is needed.

### Safe fallback

The optimization must never change which posts a grid shows. It runs only when the query matches the proven-equivalent simple-`IN` shape and the implementation can construct an equivalent query; in every other case it returns the stock query untouched.

### Kill switch

- `mai_post_grid_optimize_query` (bool, default `true`): return `false` to disable the rewrite for a site or a specific query.

## Filter API

- `apply_filters( 'mai_post_grid_optimize_query', bool $enabled )` — master on/off for the tax rewrite, default `true`.
- The `no_found_rows` default lives in `Mai_Grid::get_post_query_args()`; pagination consumers (mai-load-more) override it to `false` via the existing `mai_post_grid_query_args` filter. There is no dedicated found-rows filter.

## Edge cases and risks

- Exclude displayed (`post__not_in`): handled, and this is a primary win. `post__not_in` stays a cheap `ID NOT IN (...)` condition evaluated only for the rows the early-stop scan actually walks, so the dominant cost (full scan plus filesort) is gone. Only a pathologically huge exclude list that knocks out most recent posts would push the scan further back, which is not a realistic grid configuration. If it ever were, the same shape extends to a `NOT EXISTS`.
- Non-date orderby (`meta_value`, `title`, `rand`, etc.): the date tiebreaker does not apply, and the plan benefit depends on the available index. Treated as part of the fallback analysis; optimize only where equivalence and benefit both hold.
- Multiple taxonomies or relations: out of scope, fallback to stock.
- Result equivalence: verified by comparing rewrite versus stock output for representative grids; same posts, only tie order becomes deterministic.

## Verification

- Behavior: for the simple-`IN` case, the rewrite returns the same post IDs as stock (modulo deterministic tie ordering). For every non-simple case, the generated query is identical to stock.
- Performance: `EXPLAIN` shows no `Using temporary; Using filesort` and a backward index scan; query time drops from hundreds of ms to roughly 15ms on a large dataset.
- Integration: deploy on a mai-engine branch to eurweb, confirm grids render identically, and confirm the `performance_schema` digest for the grid query drops sharply.

## Rollout

1. Implement on a feature branch off `develop`, PHP 8.1+ idioms.
2. Verify locally and against a large dataset.
3. Beta release, install on eurweb plus one or two other Herd or staging sites, confirm identical output and the digest improvement.
4. Promote to stable once validated.

## Explicitly not doing

- Caching of any kind: dropped.
- `mai-cache` changes: none.
- Optimizer hints, engine detection, `db_server_info()` branching: none.
- Term grid changes: none.
- The PublishPress Revisions index fix: tracked separately.
