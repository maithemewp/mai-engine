# Grid cache: refresh every grid after the page, not only Exclude grids

Status: follow-up to 2.41.0-beta.5 (asked for by Mike, 2026-10-02). Test whether it is faster AND safe across ~5,000 sites before building it into a release.

## The problem

In 2.41.0-beta.5 every grid is served from the cache while its note is current. When a note only aged out (over 4 hours, no post saved), only grids using "Exclude current" or "Exclude displayed" are refreshed after the page. Every other grid is rebuilt during the page, so one visitor waits, as in beta.4 when a note expires.

The reason is how notes are built. An Exclude grid builds its note with a cheap copy of its query that asks only for post IDs (`fetch_ids()`), and a refresh after the page reruns that same copy, so it stores exactly what the page would. Every other grid builds its note from its full query, after other plugins' `posts_results` and `the_posts` filters have run. Rerunning that full query after the page would run those filters at a different moment, so the stored list could differ from what visitors normally see. The beta.5 spec ruled that out.

Measured on local eurweb (2026-10-02): the front page's top slider grid has no Exclude setting. When its note aged, the page took 342 ms longer than with fresh notes, with WordPress's own query cache cold.

## The idea

Build every eligible grid's note with the ID-only copy, the way Exclude grids do, and mark it `'by' => 'ids'`. Then any grid's aged note can be refreshed after the page, and no visitor waits for it. Grids that cannot use the copy (counting grids for pagination or Load More, sticky posts on, a query that holds the current time, a copy that declines) keep today's path.

## What must hold before it ships

- **No drift.** For every grid shape, the list a refresh after the page stores equals what a normal page load shows, with the cache on and off. This is the bar beta.5 already holds for Exclude grids.
- **Other plugins see the same posts.** Their `posts_results` and `the_posts` filters still run on the posts the grid shows, on a miss and on a hit.
- **No slower anywhere.** A cold page, a page after a save, and a page with aged notes are no slower than beta.5, on Redis and non-Redis sites, at the median.
- **Nothing written before the page** on sites without Redis, apart from minting a missing version token.
- **Safe on every host in the fleet**: PHP-FPM and LiteSpeed, PHP 8.1 to 8.4, small shared hosts with tight memory, and WP Engine.

## How to find out

1. Scan the fleet for plugins that hook `posts_pre_query`, `posts_results` or `the_posts` and could touch a grid's query. List what each does.
2. Count how many grids across the fleet use no Exclude setting, to size the benefit.
3. Build it behind a filter that defaults to off, with no-drift tests for every non-Exclude grid shape (fixed category, views or trending order, taxonomy and meta queries, multiple post types, custom order).
4. Measure on local eurweb, larrybrownsports (with Redis), two small sites without Redis, and visitsleepyhollow: time to first byte with aged notes, grid queries per minute, and identical post IDs with the cache on and off.
5. Turn it on for a handful of sites in a beta, watch error logs and page timings for a week, then decide on the default.
