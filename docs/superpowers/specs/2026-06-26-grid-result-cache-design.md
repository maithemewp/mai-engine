# Mai Engine: Grid Result Cache — Design

Date: 2026-06-26
Status: Design pending user review
Component: `mai-engine` `Mai_Grid` (`lib/classes/class-mai-grid.php`) plus a small grid-cache helper. Part of the Redis caching initiative; builds on the mai-cache foundation (object-cache-only mode) and the `mai_cache()` wrapper.

## Background

The Redis caching initiative's goal is to cut server load by caching expensive, rarely-changing work when a persistent object cache (Redis) is present.

The post-grid query optimizer (shipped on `develop`) rewrites the common single-tax-IN grid into a fast correlated subquery and was verified to give large improvements on a production-scale site (eurweb). But it deliberately handles only that one shape: it bails on multi-clause tax queries (`relation` AND/OR, `NOT IN`) and leaves `meta_query` filtering on the stock JOIN + filesort path. Extending the optimizer to those shapes was evaluated and rejected as too fragile (correctness-critical SQL rewriting on huge sites, and a collision with ElasticPress's arg handling).

So the shapes the optimizer cannot make fast (multi-tax, meta-filtered) still pay the full filtering cost at production scale.

## Problem

Grids whose queries the optimizer cannot resolve run the expensive filtering (JOIN + DISTINCT + filesort, plus `SQL_CALC_FOUND_ROWS` for paginated grids) on every request, on every site including the largest. The result set only changes when content changes, so most of that work is repeated for no reason.

## What we measured (and the honest caveat)

- On totalprosports (local, warm, smaller data, local DB): grid queries are sub-millisecond even for multi-tax with a found-rows count, and single-IN is ~1x (the optimizer already made it as fast as a `post__in`). So a local-warm box is not where this cache pays off, and these numbers must not be used to judge the feature.
- On eurweb (production scale): the optimizer produced a large, observed improvement, which means single-IN grid queries were genuinely slow there. The bail shapes (multi-tax, meta) carry more JOINs than that single-IN did, so they are at least as slow at scale. That is the regime this cache targets. This benefit must be re-confirmed on a representative slow environment before shipping (see Verification); it is currently a strong inference from the optimizer's eurweb result plus the query mechanism, not a fresh measurement.
- The cache-hit path keeps one light query: a `post__in` PK lookup (which rides WP's existing post object cache) plus a Redis GET. It replaces the slow filtering with a fast lookup; it does not eliminate the query. Caching full post objects (zero query on a hit) was considered and rejected as heavier for marginal additional gain.

## Goal

Cache the resolved grid result (ordered post IDs plus the found count) so a repeat request skips the expensive filtering query and serves a fast `post__in` fetch instead. Cover every query shape uniformly, since the cache memoizes the result and does not care how it was computed (MySQL, ElasticPress, optimized, or bailed), with no SQL-rewrite risk. Reduce aggregate server and DB load, per the initiative goal, including where it "barely helps query time" on warm boxes but adds up across volume on busy sites.

## Non-goals

- Not extending the query optimizer (separate, rejected as too fragile).
- Not fragment-caching rendered HTML. The IDs approach leaves rendering normal; HTML/fragment caching is a possible later phase.
- Not caching grids that are already fast: `query_by = 'id'` grids (already a direct `post__in`, zero benefit) and the pure optimized single-IN case (the optimizer already owns it).

## The design

### What is cached

The resolved result for a grid query: the ordered post IDs (the `WP_Query` result) and the found count for pagination. Value shape: `[ 'ids' => int[], 'found' => int ]`.

### Storage

Object-cache-only mode (mai-cache's `Cache::object`, `wp_cache_*`, no DB fallback), in a dedicated group (`grid`). Grids are churny, so on a non-Redis site a DB-transient cache would add `wp_options` write churn on every miss; object-only mode no-ops there instead. Huge sites run Redis, which is the target. This sub-project adds a small object-mode accessor alongside the existing transient-mode `mai_cache()` wrapper (the foundation supports both; the wrapper currently only exposes transient mode).

### Freshness

- A long TTL as a backstop (e.g. `DAY_IN_SECONDS`), adjustable per grid via filter (a grid with a relative date window can opt to a shorter TTL).
- Broad group-flush (one mai-cache token rotation clears every grid entry) on content changes: `save_post`, `deleted_post`, `transition_post_status`, and term changes (`created_term`/`edited_term`/`delete_term`, `set_object_terms`).
- Rationale (the reasoning behind long-TTL-plus-flush over short-TTL): on a busy site pageviews vastly outnumber post saves, so even a broad flush leaves a high hit rate (one cold recompute per save, then warm until the next save). A short TTL only adds downside: it expires needlessly under high traffic and goes cold between visits on low-traffic sites. Flush handles freshness; the TTL is only a backstop.

### Scope: which grids to cache

Cache grids where expensive filtering remains; skip the ones already fast:

- Skip `query_by = 'id'` (already a direct `post__in`, nothing to save).
- Skip the pure optimized single-IN case: when the optimizer activated (its `mai_post_grid_tt_ids` marker is present) and there is no `meta_query` (a `date_query` stays a clean scan, so it is fine), the query is already `post__in`-fast and caching it is marginal overhead.
- Cache everything else: multi-clause tax (AND/OR/NOT), any `meta_query` (the meta JOIN re-adds filesort even when the tax part is optimized), and any grid where the optimizer bailed.
- Expose a `mai_post_grid_cache` filter (default per the above) so a site can force a specific grid in or out.

### Cache key

A hash of the final query args (post-filter, as they stand at the `WP_Query` call), excluding a small denylist of keys that do not change which posts are returned (`fields`, the `update_*_cache` flags, `cache_results`). A denylist rather than an allowlist is deliberate: a site's `mai_post_grid_query_args` filter can inject arbitrary args, and a denylist keeps those in the key so different custom args cache separately. Recursive key-sort then serialize then hash. `no_found_rows` stays in the key (a paginated grid that needs the count must not share an entry with one that does not). "Exclude current entry" grids carry `post__not_in => [current ID]`, so they key per current post (correct, lower reuse).

### Hook point

Wrap the `new WP_Query( $this->query_args )` in `Mai_Grid::get_query()` (the `post` branch, around line 244):

- Compute the cache key from `$this->query_args`. If the grid is not cacheable (per Scope), run normally.
- On a hit: build the query via `post__in => cached ids` (`orderby => post__in`, `ep_integrate => false` so it is a plain MySQL PK lookup, `no_found_rows => true`), and set `found_posts` / `max_num_pages` from the cached count. Assign to `$this->query`; the existing render loop and pagination are untouched.
- On a miss: run the real `WP_Query`, render from it, and store `wp_list_pluck( $query->posts, 'ID' )` plus `$query->found_posts`.

### ElasticPress and pagination

- EP-agnostic by construction: the cache sits above the query engine, so a hit skips ES and MySQL filtering entirely (just the `post__in` lookup) and a miss lets EP or MySQL compute and we cache the IDs. The `post__in` hit fetch sets `ep_integrate => false` (a PK lookup needs no search engine), so there is no collision with EP like the optimizer's arg-stripping had.
- One honest nuance: EP reindex is async, so a recompute in the brief window right after a save (cache already flushed, ES not yet reindexed) can cache a momentarily-stale result, bounded by the next flush and the TTL. That is EP's existing lag, not something this worsens.
- Pagination/load-more: each page's args (`paged`/`offset`) hash to a distinct key, and the cached found count lets pagination render on a hit. This depends on the mai-load-more `no_found_rows => false` gate already tracked for the optimizer.

## Backward compatibility

- Additive and gated: object-only mode no-ops without a persistent object cache, so non-Redis sites are unaffected (no new `wp_options` writes). The `mai_post_grid_cache` filter allows opt-out.
- A cached ID pointing at a since-deleted or unpublished post is prevented by the flush on `deleted_post`/`transition_post_status`; the TTL bounds any gap (a grid would render a slightly short page until the flush or TTL lands).

## Verification (mandatory before ship)

- Benefit, on a representative slow environment: re-run `bin/grid-cache-benefit.php` (extended for the target site's terms/meta) on eurweb or production (remote DB, cold caches, full data) for the bail shapes (multi-tax, meta), and confirm the `post__in` hit path is materially faster than the filtering query there. Local-warm totalprosports showed sub-millisecond and is not representative. If the benefit is not real on the slow environment, do not ship.
- Correctness/invalidation: a hit returns the same IDs (same order) as a fresh compute for the same args; after `save_post`/delete/term change the next request recomputes (group flushed). Verify with a probe.
- No-Redis: object-only mode performs no writes and no-ops without a persistent object cache.
- EP: on an EP site a hit serves correct posts via the `post__in` fetch, and a content change flushes.

## Testing (unit, where practical)

- Cache-key builder: stable hash for equivalent args; arbitrary custom args change the key; denylisted keys do not.
- Cacheability predicate: skips `query_by = 'id'` and the optimized single-IN case (tt_ids present, no meta_query); caches multi-tax and meta grids; honors the `mai_post_grid_cache` filter.
- The hook integration (hit/miss/pagination) is verified on-site (integration), not in the unit suite.

## Where this sits

The grid result cache is the catch-all for grid performance, complementary to the optimizer: the optimizer owns the single-IN fast path, and the cache covers the shapes it cannot (multi-tax, meta) plus repeat-request savings across all shapes. Part of the Redis caching initiative, alongside the shipped mai-cache foundation and mai-engine adoption.
