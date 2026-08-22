# mai-load-more: page 2 takes about a minute on a large category

**Repo:** maithemewp/mai-load-more (NOT mai-engine)
**Found:** 2026-08-22 · **Status:** not fixed, pre-existing, unrelated to any mai-engine change

Filed here because it was found during mai-engine's grid deferred-excludes work and would otherwise be buried in `.agents/grid-deferred-excludes-verification.md`.

## The symptom

Clicking Load More on a grid filtered to a large category takes roughly **one minute** locally, on totalprosports against a 53,000-post category. It looks like the button did nothing, so a user clicks again.

## The cause

`PostGrid::get_data()` (`classes/class-post-grid.php:96`) serializes the whole of `$query->query_vars` into a DOM attribute:

```php
'query' => $query->query_vars,
```

By that point WordPress has already resolved the grid's `tax_query` and ALSO populated the legacy vars it derives from it (`cat`, `category__in`, and friends). The browser posts all of it back, and `class-ajax.php:187` rebuilds a `WP_Query` from it.

`WP_Query::parse_tax_query()` then derives tax clauses from those legacy vars *again*, on top of the explicit `tax_query` that is already there. The result is the same taxonomy joined to `wp_term_relationships` three times for one filter.

## Why it is not this repo's problem

The serialization dates to mai-load-more's first commit (`b874877`), long before any of the grid cache work. mai-engine's deferred-excludes branch changes nothing about how a load-more grid builds its query: those grids are refused the new path by the `no_found_rows` guard, verified in a browser on 2026-08-22.

## Where to look

- `mai-load-more/classes/class-post-grid.php:96` (what gets serialized)
- `mai-load-more/classes/class-ajax.php:120-131` (offset arithmetic) and `:187` (the rebuild)
- WordPress core `WP_Query::parse_tax_query()` (the double derivation)

Likely fix: send the block's own args and re-derive the query server-side, the way mai-testimonials does (`classes/class-testimonials.php`, rebuilt from block args rather than from query vars), instead of round-tripping resolved query vars through the browser. That also closes the related problem that a serialized query var blob is a large attribute on every load-more grid.
