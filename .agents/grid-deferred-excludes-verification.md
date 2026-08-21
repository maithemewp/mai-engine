# Deferred grid excludes: Mai Load More page 2, observed

Written 2026-08-21 against branch `feat/grid-deferred-excludes`. This is the record that the load-more path was exercised for real, in a browser, rather than reasoned about. Everything below is what was seen, not what was expected.

## Setup

- Site: `~/Herd/totalprosports`, served at `https://totalprosports.test`. Its `wp-content/plugins/mai-engine` is a symlink to this repo, so the branch under test was live.
- Mai Load More 0.4.3 was symlinked in from `~/Plugins/mai-load-more` and activated for the run.
- Category: `NFL`, `term_id` 6, 52,983 published posts at the time. The block resolves it to 33 `term_taxonomy_id` values because child categories are included.
- Test page: one `page` holding two `acf/mai-post-grid` blocks. Same query on both (category NFL, 6 posts, date DESC, excludes `exclude_displayed` + `exclude_current`). The second block carried `className: "mai-grid-load-more"` as a top-level block attribute. Page ID 2593053, deleted afterwards.
- SQL was captured with a temporary mu-plugin on `posts_request`, filtered to queries carrying the `mai_cache` query var. The object cache was flushed before each capture so the grid result cache could not serve a hit and skip the SQL.
- Rendering was checked over HTTP with `curl`, and the click was driven in a real browser. ACF blocks do not render under `wp eval`, so nothing here went through WP-CLI.

## Grid 1, no load-more class: it deferred

```sql
SELECT wp_posts.* FROM wp_posts
LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
WHERE 1=1
  AND ( wp_term_relationships.term_taxonomy_id IN (6,44588,...,44619) )
  AND wp_posts.post_type = 'post'
  AND ((wp_posts.post_status = 'publish'))
GROUP BY wp_posts.ID
ORDER BY wp_posts.post_date DESC, wp_posts.ID DESC
LIMIT 0, 7
```

Observed: no `NOT IN` at all, `LIMIT 0, 7` (6 asked plus the one effective exclude, the current page), and `, wp_posts.ID DESC` appended as the tiebreaker. No `SQL_CALC_FOUND_ROWS`.

Rendered six entries, IDs in order: 2592982, 2592788, 2592938, 2588318, 2592896, 2592910. Those are rows 1 to 6 of the same category ordered by `post_date DESC, ID DESC` in the database. The seventh fetched row was dropped by the slice, as intended.

## Grid 2, with `mai-grid-load-more`: it did not defer

```sql
SELECT SQL_CALC_FOUND_ROWS wp_posts.* FROM wp_posts
LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
WHERE 1=1
  AND wp_posts.ID NOT IN (2588318,2592788,2592896,2592910,2592938,2592982,2593053)
  AND ( wp_term_relationships.term_taxonomy_id IN (6,44588,...,44619) )
  AND wp_posts.post_type = 'post'
  AND ((wp_posts.post_status = 'publish'))
GROUP BY wp_posts.ID
ORDER BY wp_posts.post_date DESC
LIMIT 0, 6
```

Observed: the `NOT IN` list is intact and holds exactly grid 1's six rendered IDs plus the current page 2593053. `LIMIT 0, 6` is the original page size, unpadded. No `ID DESC` tiebreaker. `SQL_CALC_FOUND_ROWS` is present, which is Mai Load More flipping `no_found_rows` to false, which is the condition `can_defer_excludes()` refuses on. The guard fired.

The rendered container reported `data-total-entries="53186"`, an accurate site-wide count for that term set. Nothing inflated it, which is the practical reason the guard exists.

## Batch 1, on page load

1. 2592870 Travis Kelce Missed 2 Back-To-Back Events With Taylor Swift ...
2. 2592911 New Development Emerges Following Aldon Smith's Sudden Passing
3. 2592813 "Could The Browns Actually Trade Shedeur Sanders This Summer?" ...
4. 2592764 Taylor Swift & Travis Kelce Refusing To Invite 2 Major Celebrities ...
5. 2588279 10 NFL Players Their Teams Quietly Want To Trade
6. 2592301 Cam Skattebo Shows Off Insane Custom Flooring In His Garage ...

These are rows 7 to 12 of the database ordering, which is what you get when rows 1 to 6 are excluded by ID.

## Batch 2, after clicking Load More

The AJAX query, captured by the same probe:

```sql
SELECT wp_posts.* FROM wp_posts
LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
LEFT JOIN wp_term_relationships AS tt1 ON (wp_posts.ID = tt1.object_id)
LEFT JOIN wp_term_relationships AS tt2 ON (wp_posts.ID = tt2.object_id)
WHERE 1=1
  AND wp_posts.ID NOT IN (2588318,2592788,2592896,2592910,2592938,2592982,2593053)
  AND ( wp_term_relationships.term_taxonomy_id IN (6,...) AND tt1.term_taxonomy_id IN (6,...) AND tt2.term_taxonomy_id IN (6,...) )
  AND wp_posts.post_type = 'post'
  AND ((wp_posts.post_status = 'publish'))
GROUP BY wp_posts.ID
ORDER BY wp_posts.post_date DESC
LIMIT 6, 6
```

`LIMIT 6, 6`: offset 6, page size 6. The page size is the original 6, so no padded `posts_per_page` leaked into the serialized query. The `NOT IN` list survived the round trip unchanged.

7. 2592276 Hanna Cavinder Announces She's Also Dating a Dallas Cowboys Star ...
8. 2592246 Travis Kelce Gets Called Out For "Inappropriate" Outfit In Public
9. 2591842 Heartbreaking Clip Of Aldon Smith Talking About His Struggles ...
10. 2591828 ESPN's Computer Reveals Which NFL Teams Will Have The Most Wins ...
11. 2591773 NFL Expansion Rumors Spreading As Owner Who's Close With Tom ...
12. 2591667 NFL Fans Want Lamar Jackson Suspended Immediately [PHOTO]

These are rows 13 to 18 of the database ordering. Batch 2 picks up exactly where batch 1 stopped.

## Repeats, gaps, cross-grid leakage

- Twelve distinct posts in the load-more grid. Zero duplicate hrefs.
- No gap between batch 1 and batch 2. Row 12 is followed by row 13.
- Zero overlap between grid 1's six and the load-more grid's twelve, checked by href in the live DOM. `exclude_displayed` worked across blocks, which only holds because grid 1's rendered IDs land in the static before grid 2 builds its query.
- The current page, 2593053, appears in neither grid and is present in grid 2's `NOT IN` list.

The whole sequence was reproduced a second time from a fresh page load with the object cache flushed and exactly one click, to rule out the first run's duplicated click. Identical SQL, identical twelve posts.

## Side observations, not defects of this change

- Mai Load More's AJAX query joins `wp_term_relationships` three times (`tt1`, `tt2`) because it reserializes an already-resolved `tax_query`. On a 53k-post category that request took roughly a minute locally. That is a Mai Load More issue and is unrelated to deferred excludes, but it is why the button sits disabled for a while after the click.
- The initial load-more render pays for `SQL_CALC_FOUND_ROWS` over the whole category. That is inherent to wanting an accurate `found_posts`, and is exactly the cost the deferral guard declines to make worse.

## Cleanup

Probe mu-plugin, probe log, and test page 2593053 removed. Mai Load More was left symlinked and active on the mirror.
