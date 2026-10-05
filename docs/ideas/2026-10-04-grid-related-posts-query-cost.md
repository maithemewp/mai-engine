# Grids: make "current category" queries cheap on big sites

Status: fix 1 is specced in `docs/specs/2026-10-04-grid-category-query-rewrite.md` (draft, 2026-10-04). Fix 2 is left out there. Earlier: idea, to test after 2.41.0-beta.5 (Mike, 2026-10-04: "sounds good if not fragile. Worth testing").

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

## Safe fallbacks (Mike, 2026-10-04: "I don't like the fragility but maybe we can do it safely with fallbacks")

Fix 1 should only ever take effect where it is known to be safe, and fall back to today's SQL everywhere else.

- **Only Mai's own ID-only copy.** For grids that defer their excludes, the copy is the only statement that hits the database on a miss, and its answer is just a list of IDs. The page's own query is never rewritten. All six slow eurweb grids are of this kind.
- **Only plain category and tag filters.** One or more terms with `IN`, the shapes the no-drift tests cover. Anything else (`AND`, `NOT IN`, `EXISTS` operators, meta queries mixed in, custom taxonomies Mai has not tested) keeps today's SQL.
- **Only when nobody else touched the SQL.** At the last moment before the statement runs, Mai compares the join, where and group-by clauses with what WordPress itself built for that tax query. If another plugin changed any of them, Mai leaves the statement alone. A plugin rewriting the query can then never be broken or silently undone.
- **A switch, off by default.** A filter turns it on, so a beta can ship it to a few sites first, and any site can turn it off again.
- **Same posts, proven.** The no-drift tests compare the IDs from both forms for every covered shape, on MySQL and MariaDB.

## How to find out

1. Run one read-only `EXPLAIN` of the rewritten statement on live eurweb's MySQL 8.0.46 (`mai-sites run`).
2. Scan the fleet for plugins hooking the SQL clause filters, and list what each does to a grid's query.
3. Build fix 1 behind a filter that defaults to off, with no-drift tests for every tax query shape, then measure on local eurweb, larrybrownsports and a small site.
4. Build fix 2 the same way, if fix 1 does not already make it unnecessary.
