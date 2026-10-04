# Grids: make "current category" queries cheap on big sites

Status: idea, to test after 2.41.0-beta.5 (Mike, 2026-10-04: "sounds good if not fragile. Worth testing").

## The problem

On eurweb (85,000 posts), every article shows six related-posts grids. Each one shows posts from the article's own categories and leaves out what the grids above it showed. When a grid's list has to be rebuilt, each grid sends one statement that takes about 250 ms, about 1.5 s per article. That happens after every post save, which on eurweb comes every 15 to 75 minutes, and after a Mai Engine update. 2.40.1 is just as slow. Measured on the local copy of eurweb, 2026-10-04 (48 cold views, TTFB 2,956 ms, of which the six statements are 1,532 ms).

The statement joins `wp_term_relationships`, so a post in two of the article's categories appears twice. WordPress removes the duplicates with `GROUP BY wp_posts.ID`, which builds a temporary table of about 81,500 rows and then sorts for the newest few. It reads nothing from disk. It is CPU work. The six statements are the same query with LIMIT 2, 8, 14, 20, 26 and 32, and each smaller list was exactly the start of the LIMIT 32 list.

## Two fixes to test

1. **Write the category filter as `EXISTS` instead of a join with `GROUP BY`.** In the lab each statement went from 248 to 259 ms to under 1 ms, with the same post IDs on the 4 test articles and on 11 more categories of 18 to 64,000 posts. It was never slower. Gain: about 1.5 s per cold article.
2. **One fetch for grids on a page that differ only in how many posts they show.** LIMIT 2 and LIMIT 32 cost the same. Gain: about 1.25 s per cold article. It is new Mai code but cannot change which posts show.

Fix 1 also shrinks the one case where beta.5 is slower than 2.40.1: the first view after Mai's cache is emptied when no post has been saved since (spec Risks).

## What must hold

- **Not fragile.** Other plugins can rewrite a query's SQL through `posts_clauses`, `posts_join`, `posts_where` and `posts_groupby`. The rewrite must not break them or be silently undone by them. Find which plugins in the fleet hook those filters on grid queries.
- **Same posts, same order,** for every grid shape: one or several categories and tags, `AND` and `IN` and `NOT IN` operators, child categories, several post types, sticky posts, the ID-only copy and the full query alike.
- **Never slower,** on small sites and big ones, and on MySQL 8.0 and MariaDB.

## How to find out

1. Run one read-only `EXPLAIN` of the rewritten statement on live eurweb's MySQL 8.0.46 (`mai-sites run`).
2. Scan the fleet for plugins hooking the SQL clause filters, and list what each does to a grid's query.
3. Build fix 1 behind a filter that defaults to off, with no-drift tests for every tax query shape, then measure on local eurweb, larrybrownsports and a small site.
4. Build fix 2 the same way, if fix 1 does not already make it unnecessary.
