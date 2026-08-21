# mai-engine — Grid result cache on ElasticPress sites (TODO / untested)

**Repo:** maithemewp/mai-engine · **Branch:** develop
**Created:** 2026-08-21 · **Status:** deliberately deferred, not started

## The gap

`Mai_Query_Cache::is_cacheable()` (`lib/classes/class-mai-query-cache.php:159-182`) bails on any query carrying `ep_integrate`:

```php
// ElasticPress offloads to ES and hooks posts_pre_query itself; that interaction is
// unverified (eurweb is not on EP), so skip ep_integrate grids until it is tested.
if ( ! empty( $query_vars['ep_integrate'] ) ) {
	$cacheable = false;
}
```

Mai ElasticPress sets that flag on **every** post grid query, unconditionally:

```php
// mai-elasticpress/mai-elasticpress.php:340, function edit_query()
$query_args['ep_integrate'] = true;
```

Net effect: **on any site running Mai ElasticPress, the grid result cache has never done anything.** Not a bug, a deliberate bail, but nobody has checked whether it needs to be.

Verified live 2026-08-21 by running the real filter chain on three local mirrors:

| site | published posts | `ep_integrate` on grids | grid cache |
|---|---|---|---|
| larrybrownsports | 145,646 | no | working |
| totalprosports | 114,981 | no | working |
| tvnewscheck | 180,083 | **yes** | **off, site-wide** |

totalprosports has mai-elasticpress active but elasticpress inactive, and that is harmless: mai-elasticpress self-disables at `mai-elasticpress.php:655` (`class_exists( 'ElasticPress\Feature' )`), so `edit_query` never gets hooked. Do not "fix" that, it already works.

## What to test

Reproduce on `~/Herd/tvnewscheck` (both elasticpress and mai-elasticpress active, 180k posts, and its `mai-engine` is a real directory, unlike totalprosports which symlinks to this repo).

1. **Hook ordering.** Mai Engine registers `posts_pre_query` at priority 10 (`lib/functions/query-cache.php:22`). Find EP's own `posts_pre_query` priority in `ElasticPress\Indexable\Post\QueryIntegration`. A cache hit should short-circuit before EP is consulted, so a warm grid never makes a network call at all. Confirm that is what actually happens rather than assuming it from priority numbers.
2. **Cache key.** `cache_key()` hashes `$query->request`. Confirm `$query->request` is populated and stable for an EP-integrated query, since EP short-circuits after the SQL string is built but before it runs. If it is empty or varies, the key needs a different basis for this path.
3. **Miss path.** On a miss, EP returns posts via `posts_pre_query` and `the_posts` should still fire so the result gets stored. Verify the stored IDs match what EP returned, in EP's order.
4. **found_posts / max_num_pages.** EP sets these itself. Check `serve()` (`class-mai-query-cache.php:261-272`) does not clobber them in a way that breaks pagination or Mai Load More.
5. **Invalidation.** EP reindexes on save independently of our version bump. Confirm a publish rotates our token *and* that serving a stale entry cannot outlive an EP reindex in a way that shows content EP would have excluded.
6. **Whether it is worth it.** The payoff here is saving a network round trip per grid, not saving the database. Measure the actual ES round-trip time on tvnewscheck before building anything. If it is single-digit ms, this may not be worth the risk surface.

## Why it was deferred

Decided 2026-08-21 during the grid cache key work. ElasticPress is already doing the job the cache exists to do, which is keeping grid queries off MySQL. Covering it is a different problem with a different payoff, and it is untested territory that would have roughly doubled the size of that change.

Unknown and worth establishing before picking this up: **how many of the ~200 client sites actually run Mai ElasticPress.** Only 3 of 23 local mirrors have ElasticPress installed at all, and only tvnewscheck routes grids through it. That is not a sample. If the real fleet ratio is much higher, this moves up the list.

## Related

- `.claude` memory: `project-grid-cache-key-shatter` (the measured `post__not_in` key shatter and its fix)
- `docs/superpowers/specs/2026-06-26-grid-result-cache-design.md`
- `lib/classes/class-mai-post-grid-query-optimizer.php:44-47` already bails on `ep_integrate` for the same reason
