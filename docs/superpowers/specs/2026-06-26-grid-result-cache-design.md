# Mai Engine: Grid Result Cache — Design

Date: 2026-06-26
Status: Approved direction (cache-first; optimizer decoupled, off by default, measured). Ready for implementation plan.
Component: `mai-engine` `Mai_Grid` (`lib/classes/class-mai-grid.php`) plus a reusable grid-cache helper. Part of the Redis caching initiative; builds on the mai-cache foundation (group-flush token + object/transient stores).

## Background and the confirmed root cause

The initiative's goal is to cut server load by caching expensive, rarely-changing work. The grid sub-project was driven by a real, observed symptom: on eurweb (a busy news site running Redis + WP 7.0), post-grid queries hammered the database even though a persistent object cache was active. We diagnosed this on a synced copy (`eurweb.test`) with Redis engaged, and the cause is now measured, not assumed:

- WP core 6.1+ already caches `WP_Query` results (post IDs + found count) in the object cache, and on a healthy Redis a warm grid query costs **zero** database queries. So core's caching is not broken, and the optimizer's SQL rewrite does not make grids uncacheable (both verified by measurement).
- Core salts every query-result cache key with the `posts` `last_changed` value (plus `terms` for tax queries). On a write-heavy editorial site, `last_changed` churns continuously: real edits, **autosave revisions** (every open editor autosaves on a ~60s heartbeat, and each revision insert calls `clean_post_cache`), and the revisionary plugin all bump it. We measured `last_changed` taking four distinct values in 30 seconds on production during editorial hours.
- The result: every grid's cached result is invalidated within seconds of being written, so grids re-run their filtering query over and over — even though the *published* content the grids display has not changed. The post-object cache survives (it is keyed by ID, not salted), so a miss is "only" one filtering query, but multiplied across many grids, many pages, and constant invalidation it is real, recurring database load.

This is WP core's own documented weakness ("`last_changed` is problematic on high-traffic and heavily updated sites"), now confirmed on a real site. It is not grid-specific — the same churn defeats every `WP_Query` result cache during editorial hours — which is why the fix is built to be reused.

## Problem

On write-heavy sites, core's `last_changed`-salted query caching is invalidated far faster than the underlying published content actually changes, so grid queries re-execute needlessly. We want grid results to stay cached through the editorial write churn, while still reflecting changes that actually affect what a grid displays.

## Goal

Cache the resolved grid result (ordered post IDs plus the found count) under a salt we control — a group-flush token rotated only on changes that affect grid output — instead of core's `last_changed`. A repeat request then skips the filtering query and hydrates posts straight from the object cache. Reduce aggregate database and server load on write-heavy sites (the proven pain), and provide the same benefit on sites with no persistent object cache (via a DB-backed store), where core's caching gives nothing cross-request at all.

Proven on `eurweb.test`: a token-keyed IDs cache served a full 12-post grid in **0 database queries immediately after a `last_changed` bump**, where core's cache took 2. That is the entire point of this design, validated against the real churn.

## Non-goals

- Not building or hardening the query optimizer here. It is decoupled from this work and disabled by default — the cache makes warm hits free for every shape and is fail-safe on a miss, so the optimizer is no longer a correctness dependency. Neither existing optimizer is validated, so adopting one is a separate, measure-first decision (see Optimizer below).
- Not fragment-caching rendered HTML in this phase. We cache result IDs; rendering stays normal. A fragment layer is a possible later phase (decided: IDs first).
- Not caching grids that are already fast: `query_by = 'id'` (already a direct `post__in`) and the pure optimized single-IN case the optimizer already owns.
- Not changing core's caching. We run a parallel, token-salted cache alongside it.

## The design

### What is cached

Per cacheable grid query: `[ 'ids' => int[], 'found' => int, 'token' => string ]` — the ordered result post IDs, the found-rows count for pagination, and the version token captured at write time (used for stale-while-revalidate; see below). The `ids`/`found` pair mirrors core's own cache value (`['posts' => ids, 'found_posts' => N]`), so the shape and hit path are proven.

### Cache key (borrowed from core's `generate_cache_key`)

A hash of the final query args plus the resulting SQL, built the way core builds its key so we inherit its correctness:

- **Denylist the volatile vars** that do not change which posts are returned: `cache_results`, `fields`, `update_post_meta_cache`, `update_post_term_cache`, `update_menu_item_cache`, `lazy_load_term_meta`, `suppress_filters`. A denylist (not an allowlist) keeps any custom `mai_post_grid_query_args` additions in the key, so different custom args on a client site cache separately.
- **Normalize for stability**: cast and sort `post_type`; `array_unique` + sort the order-insensitive id arrays (`post__in`, `post_parent__in`, `post_name__in`). Two queries that differ only in argument order must hash identically.
- Hash `md5( serialize( normalized_args ) . $sql )`, where `$sql` is the final request (field list normalized to `{posts}.*` as core does). Including the SQL captures the optimizer's rewrite and any `posts_*` clause filters.
- The key is **stable** — it deliberately does *not* include the invalidation token. Versioning lives inside the value (see below), so a token rotation leaves the previous value reachable for stale-while-revalidate instead of orphaning it.

### The version token and stale-while-revalidate (not `last_changed`)

Instead of salting the key with core's `last_changed`, each post type has a mai-cache flush **token** — a random value rotated only by our invalidation hooks, so the draft/autosave/revision churn that bumps `last_changed` never touches it. The token is stored **inside the cached value** as a version stamp, not folded into the key, so the key stays stable and a rotation does not orphan the previous value. Granularity is **per post type**: a value records the token(s) of the post type(s) its query targets, so editing a live `post` only ages out `post` grids and leaves `product`/`page` grids fresh. (On a mono-type site this is a single token, still a large improvement over core.)

On read, with the value fetched by the stable key:

- **Fresh hit** — value present and its stored token equals the current token: hydrate from `ids`, no recompute.
- **Stale (token rotated since write)** — value present but its token is out of date: serve the stale `ids` immediately to every request, and let exactly one request win an atomic single-flight lock (`wp_cache_add( "lock:$key", … , $lock_ttl )`), recompute, and rewrite the value with the current token. The database sees one recompute per key per rotation and no request waits. A grid a second out of date is fine — the new post appears a beat later.
- **Cold (no value at all)** — first-ever request, or after a full flush / Redis restart / deploy: there is no stale value to serve, so the single-flight lock funnels callers to one recompute per key while the rest briefly wait or fall through. This is the irreducible case every cold cache has; single-flight still turns an N-way dogpile into one recompute per key.

This stale-while-revalidate plus single-flight is the stampede protection core lacks, and it is necessary rather than optional here: token rotation is *synchronized* (a whole post type's grids invalidate at the same instant), which is exactly the condition that produces a thundering herd.

### Invalidation (proactive + TTL backstop)

- **Rotate a post type's token** on the events that change what its grids display: `transition_post_status` into or out of `publish` (publish / unpublish / trash) and `deleted_post`, and — per the safety preference — `save_post` for an **already-published** post (a live edit). Explicitly *not* on draft saves, autosaves, or revisions (post type `revision`), which is what defeats core today.
- **TTL backstop**: a default of `4 * HOUR_IN_SECONDS` on the stored value, filterable per grid (up for sites that want a longer backstop). The token does the real invalidation; the TTL only bounds changes that bypass our hooks (direct SQL, hook-suppressed imports) and eventually evicts cold entries. It is a safety net, not the freshness mechanism.
- **Cold-start spikes** (full flush / Redis restart / deploy) are the one case stale-while-revalidate cannot smooth, since there is no prior value to serve. Bound them by running full flushes off-peak; proactive cache warming is a noted follow-up, designed separately.

### Hit path (no re-query)

On a hit — fresh, or stale (the stale path serves these same ids while one request refreshes in the background) — we hydrate posts directly from the object cache, never a second `WP_Query` (a re-query would be re-salted by `last_changed` and miss again):

- `_prime_post_caches( $ids, $update_term_cache, $update_meta_cache )` bulk-loads only the uncached posts (zero queries when warm, proven).
- Build `$query->posts` from `array_map( 'get_post', $ids )`, set `found_posts` and `max_num_pages` from the cached count. The existing render loop and pagination are untouched.

### Storage backend (auto-selected)

Same key, shape, and logic; only the store differs, abstracted by the mai-cache foundation:

- **Object-cache mode** when `wp_using_ext_object_cache()` is true (eurweb and any Redis site): no `wp_options` writes, entries ride Redis.
- **Transient / DB mode** otherwise (e.g. totalprosports today, low-traffic theme customers with no object cache): the token and values persist in `wp_options`, swapping each heavy filtering query for a light indexed `wp_options` read. This is the case core cannot help with at all, since `last_changed` itself does not persist without an object cache.

The token must live in the same persistent store as the data (so it survives without Redis); this is exactly mai-cache's existing group-flush behavior.

### ElasticPress

The cache sits above the query engine, so it is EP-agnostic and reduces ES load as well as DB load. On a miss, EP resolves the query via ElasticSearch and we cache the resulting IDs; on a hit we hydrate from the object cache (`_prime_post_caches`, no `WP_Query`), skipping both ES and MySQL. The optimizer already bails on EP grids (its `ep_integrate` guard), and the cache does not care how the IDs were produced, so the two coexist. One nuance: EP reindex is asynchronous, so a recompute in the brief window right after a publish (token rotated, ES not yet reindexed) can cache a momentarily-stale result, bounded by the next rotation or the TTL. That is EP's existing lag, not something this adds.

### Scope: which grids to cache

- Skip `query_by = 'id'` (already a direct `post__in`).
- Skip the pure optimized single-IN case **only when an optimizer is active and has marked the query** (marker present, no `meta_query`): it is already `post__in`-fast. With the optimizer off (the default here), single-IN grids are stock-cost and *are* cached.
- Skip non-deterministic queries: `orderby` containing `RAND(` (core skips these too).
- Cache everything else — multi-clause tax, `meta_query`, and any bail shape — since those are the churn-vulnerable, expensive ones.
- A `mai_post_grid_cache` filter forces a specific grid in or out; a TTL filter overrides the backstop per grid.

The cache is shape-agnostic: it stores whatever IDs the query returns, so single-IN, multiple tax clauses, AND/OR, NOT IN, `meta_query`, and any non-`RAND` sort all get free warm hits. The cache reduces the *frequency* of cold queries, not their *cost* — a cold single-IN miss on a large term measured ~250ms on eurweb's 80k-post category (stock; ~0.5ms with an optimizer). With the optimizer off, every cold miss pays that stock cost, but the cache plus single-flight make misses rare and keep them off the user's critical path (served stale while one request refreshes). Whether that residual cold load justifies adding a validated optimizer is a measured follow-on (see Optimizer).

### Optimizer (decoupled, off by default for this work)

This work does not ship or harden a query optimizer. The cache makes warm hits free for every shape and is fail-safe on a miss (it falls through to the stock query), so the optimizer is no longer a correctness dependency — it would only cut the *cost* of the now-rare cold miss. It is disabled by default here (`mai_post_grid_optimize_query => false`).

Decoupling it is deliberate, because **neither existing optimizer is validated**:

- The **args-strip** version (on develop) is the more-reviewed of the two (subagent review, equivalence matrix on totalprosports, EP guard + test) but fails *dangerous*: it strips `tax_query` from the args, so any query-offloader that reads the args (ElasticPress, FacetWP, and others we cannot enumerate) can silently return the wrong posts. Guarding each offloader is whack-a-mole.
- The **`posts_clauses`** version (currently on eurweb prod) is an ~18-hour-old temporary fix, verified only to *cache* — not its correctness across shapes, its regex robustness, or its bail behavior. It fails *safe* (a regex miss bails to stock), but it is not proven.

If the measurement below shows the residual cold-miss load is worth optimizing, adopting an optimizer is its own task: do the real validation — equivalence across shapes on eurweb data, a `post_date`-orderby gate (today neither version bails on non-date sorts, where the subquery has no early-LIMIT win and can be *slower* than stock), and a robustness review — and choose on that plus the failure mode, not on which one happens to be deployed. Note that eurweb prod currently relies on the unvalidated temp fix for grid speed; shipping the cache reduces that reliance, and retiring or validating that temp code is a related cleanup.

### `wp cache flush` and full-flush coverage

- Object-cache mode: our entries are in the object cache, so `wp cache flush` clears them automatically.
- Transient / DB mode: register a WP-CLI `after_invoke:cache flush` hook (loaded only under WP-CLI) that rotates our grid tokens, so `wp cache flush` clears the DB-backed cache too.
- `wp mai flush` and plugin upgrades already flush the mai-cache groups, including this one.

### Reusability

The cache is a standalone helper (key builder, per-post-type token group, prime-and-hydrate hit path, invalidation hooks) driven entirely by the `mai_cache` query flag — nothing is inlined in `Mai_Grid`. Related-posts, archives, or any `WP_Query` adopt it by setting the flag (with an optional token scope), so the next surface is opt-in, not a re-implementation.

### Hook point

The cache is driven by an **opt-in query flag**, not by wrapping `Mai_Grid`'s query. When a grid is cacheable, `Mai_Grid::get_query()` sets `$query_args['mai_cache'] = true` (optionally `[ 'scope' => 'post', 'ttl' => … ]`). A generic helper hooks `posts_pre_query` and `the_posts`, acting only when `mai_cache` is set:

- `posts_pre_query`: read the stable key and apply the fresh / stale / cold logic. On a fresh or stale hit, **build the posts array directly** — `_prime_post_caches( $ids )` then `array_map( 'get_post', $ids )` — which preserves the cached result order with no `orderby` and no query (a `post__in` query would need `orderby => post__in` and would be re-salted by `last_changed`), and return it to short-circuit the query. On a miss, return `null` and flag the query for storage.
- `the_posts`: on a flagged miss (a cold key, or the single-flight refresh of a stale entry), store `[ wp_list_pluck( $posts, 'ID' ), $query->found_posts, current_token ]`.

This flag is the reusable surface: related-posts, archives, or any `WP_Query` opt in by setting `mai_cache`; the token scope defaults to the query's `post_type`(s).

## Relationship to core's caching, and why grids first

This runs alongside core's caching, not instead of it. We lean harder than ever on core's object cache — for post objects, terms, meta, and as our own store in object mode — and only core's `last_changed`-salted *query-result* cache becomes redundant, and only for the grids we wrap (ours serves them first). Every other query keeps using core's caching unchanged.

We deliberately scope this to grids rather than hijacking all `WP_Query`s, even though the helper is built to be reused. A global token cache would have to be correct for every query shape and every content change site-wide; a single invalidation gap would show stale or wrong content anywhere, whereas core's coarse `last_changed` is at least always safe. Grids are a bounded surface with a clear dependency (post type, optionally terms), they are the surface we actually measured hammering the database, and we do not own every `WP_Query` caller (admin, REST, other plugins). The plan is to fix the proven pain correctly, then extend the same helper to the next measured surface (related posts, archives) deliberately — not to swap core's safe-but-coarse salt for our finer one everywhere at once.

## Backward compatibility

- Additive and filterable; nothing changes for grids that are skipped or for sites that opt out via `mai_post_grid_cache`.
- A cached ID for a since-unpublished or deleted post is prevented by the token rotation on `transition_post_status` / `deleted_post`; the TTL bounds any change that bypassed our hooks.
- Runs alongside core's caching without conflict (separate group and key).

## Verification (mandatory before ship)

- **Churn survival (the core claim)**, on `eurweb.test` with Redis engaged: reproduce `_probes/churn-fix-demo.php` — after a `last_changed` bump, the token cache serves the grid in 0 queries while core takes >0. Already passing; keep as a regression check.
- **Correctness / equivalence**: a hit returns the same ordered IDs and found count as a fresh compute for the same args, across single-IN, multi-tax, and meta grids (extend `bin/grid-equivalence-matrix.php`).
- **Invalidation**: publishing / unpublishing / trashing / deleting, and editing an already-published post, rotate the right per-type token and force a recompute; draft saves, autosaves, and revisions do **not**.
- **No-Redis path**: with the object cache disabled, the transient store persists across requests and swaps the filtering query for a `wp_options` read; `wp cache flush` (via the WP-CLI hook) and `wp mai flush` both clear it.
- **Stampede / stale-while-revalidate** (the stampede claim, currently unproven): fire N concurrent requests at one grid immediately after a token rotation and confirm exactly one recompute runs while the rest serve the stale ids (and the served ids match the pre-rotation result); and that a fully cold key (after `wp cache flush`) funnels to one recompute per key, not N. Build a concurrency probe on `eurweb.test` (parallel `wp eval` / HTTP). This is a hard gate, not an assumption.
- **Cold-miss load (informs the optimizer decision, not a gate)**: with the cache live and the optimizer off, measure the background DB load from cold misses / single-flight refreshes on eurweb (count and total query time per editorial hour). This is the input to whether a validated optimizer is worth building later — not a pass/fail gate for the cache.

## Testing (unit, where practical)

- Key builder: stable hash for equivalent args; arbitrary custom args change the key; denylisted and order-insensitive keys are normalized out.
- Cacheability predicate: skips `query_by = 'id'`, optimized single-IN, and `orderby RAND`; caches multi-tax and meta; honors the `mai_post_grid_cache` filter.
- Token / invalidation: the right post-type token rotates for publish/unpublish/trash/delete/published-edit and does not for draft/autosave/revision.
- Stale-while-revalidate: a value whose stored token differs from the current token is treated as stale (served, and flagged for refresh) while a matching token is a fresh hit; the single-flight lock admits exactly one refresher.
- Hit/miss hydration and pagination are verified on-site (integration), not in the unit suite.

## Evidence appendix (why this design, not the previous draft)

The earlier draft proposed object-cache-only storage, a long TTL, and a broad flush on every `save_post` — which is core's `last_changed` behavior and would have re-created the exact churn we measured. The production diagnosis (Redis healthy, 93% hit ratio, 0 evictions, `last_changed` churning every few seconds from autosave revisions) is what redirected the design to a token salt we control plus targeted invalidation. The proof that this works is the churn demo's 0-vs-2 query result.
