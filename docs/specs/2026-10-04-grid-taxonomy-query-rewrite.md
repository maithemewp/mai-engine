# Grids: cheaper taxonomy queries on big sites

Status: draft for Mike's review (2026-10-04). Not built. Comes from `docs/ideas/2026-10-04-grid-related-posts-query-cost.md`.

## Why

On eurweb (85,000 posts) every article shows six related-posts grids. When their lists have to be rebuilt, after any post save and after a Mai Engine update, each grid sends one statement that takes about 250 ms. That is about 1.5 s on every cold article view (measured on the local copy, `task-14-results.md`, "Cold article view on eurweb").

The statement is slow because WordPress joins the category table, which lists a post once per matching category, and then removes the duplicates with `GROUP BY`. To do that, MySQL collects all 81,500 matching posts and sorts them, only to keep the newest few.

Written with `EXISTS` instead, the same filter returns the same posts in under 1 ms, because the database can walk the posts newest first and stop once it has enough.

## Goals

- Grid queries filtered by taxonomy cost under 1 ms instead of hundreds on big sites, on every Mai Engine site where it is proven safe. Mike, 2026-10-04: worth it, fast, and able to run on 5,000 sites.
- On by default. The filter only turns it off.
- Every grid shows the posts it shows today, in the same order.
- Never slower than today, on any site or database.
- Not brittle. Mai never parses SQL. It rebuilds what WordPress would have written and compares it exactly. Any difference means today's statement goes out unchanged. A failed check costs the speedup, never correctness.

## Non-goals

- Queries that are not Mai post grids: archive pages, other plugins' queries. A later idea, once this has run for a while.
- Grids with Load More (they count rows with `SQL_CALC_FOUND_ROWS`), meta queries, search, or nested tax queries.
- MySQL before 8.0.16. MySQL 5.7 reached end of life in October 2023, and before 8.0.16 MySQL cannot plan an `EXISTS` like a join (MySQL 8.0 manual, "Optimizing IN and EXISTS Subquery Predicates with Semijoin Transformations").
- Fix 2 from the idea doc (one fetch per page for grids that differ only in count). With this change those statements cost under 1 ms each.

## How it works, in plain terms

1. **A Mai post grid builds its query, as today.** Mai marks the query, and the ID-only copy it runs for grids that defer their excludes.
2. **WordPress builds the SQL.** Mai records each part (join, where, group-by, DISTINCT, fields) twice: when WordPress hands it to the first plugin filter, and after the last plugin filter. It does the same for the finished statement.
3. **Mai checks.** It rebuilds WordPress's taxonomy SQL itself and compares. If any part differs, or anything changed between the two looks, Mai steps aside.
4. **At the last moment before the statement goes to the database,** Mai swaps it for the `EXISTS` form, but only if the statement is still exactly the one WordPress finished building.
5. **The database returns the same posts, fast.** Everything after that is today's code.
6. **If a swapped statement fails,** Mai sends the original statement in its place, so the page still shows its grid. It turns the swap off on that site for 24 hours and writes one line to the debug log.

The query's own SQL text, as WordPress stores it on the query, never changes. So Mai's key check and WordPress's query cache keys work exactly as they do today.

## What ships

### 1. The swap

Today a "current category" grid copy sends this (eurweb, whitespace trimmed):

```
SELECT eur_posts.ID FROM eur_posts
LEFT JOIN eur_term_relationships ON (eur_posts.ID = eur_term_relationships.object_id)
WHERE 1=1 AND ( eur_term_relationships.term_taxonomy_id IN (1,40271,...) )
AND eur_posts.post_type = 'post' AND ((eur_posts.post_status = 'publish'))
GROUP BY eur_posts.ID
ORDER BY eur_posts.post_date DESC, eur_posts.ID DESC
LIMIT 0, 32
```

After the swap:

```
SELECT eur_posts.ID FROM eur_posts
WHERE 1=1 AND EXISTS ( SELECT 1 FROM eur_term_relationships WHERE eur_term_relationships.object_id = eur_posts.ID AND eur_term_relationships.term_taxonomy_id IN (1,40271,...) )
AND eur_posts.post_type = 'post' AND ((eur_posts.post_status = 'publish'))
ORDER BY eur_posts.post_date DESC, eur_posts.ID DESC
LIMIT 0, 32
```

Three exact text replacements on the finished statement, and nothing else:

- WordPress's taxonomy joins become empty.
- WordPress's taxonomy condition becomes the rewritten condition (below).
- `GROUP BY {posts}.ID` becomes empty.

Each piece must appear exactly once in the statement, or Mai steps aside.

**Several taxonomy filters.** WordPress writes one condition per filter. Mai rebuilds each one on its own, with a fresh `WP_Tax_Query` per filter, and checks that putting them back together the way WordPress does gives exactly WordPress's full condition. If it does not, Mai steps aside. Only then does it rewrite:

- **Filters joined with AND:** each `IN` filter becomes its own `EXISTS`, using the table name WordPress gave it (`tt1`, `tt2` and so on). `NOT IN`, `AND` and `EXISTS` filters already use their own subquery and stay exactly as written. The pieces are joined with plain `AND`, without the outer brackets, so each `EXISTS` sits directly in the top-level `AND`. MySQL 8.0.16+ needs that to plan it like a join.
- **Filters joined with OR, all of them `IN`:** WordPress gives them one shared join. The whole OR condition goes inside one `EXISTS`, which also sits directly in the top-level `AND`.
- **OR with any filter that is not `IN`:** Mai steps aside. An `EXISTS` inside an OR cannot be planned like a join, so it could be slower.

**Why this is the same filter.** A post passes today's statement when its category rows match. The `GROUP BY` only removes the duplicate copies of a post the join made. `EXISTS` asks the same question once per post. Several joins become several `EXISTS`, each asking about its own filter, which is exactly what the joins checked. The `ORDER BY` ends with the post ID, so the order has no ties, and `LIMIT` picks the same posts.

### 2. Which queries Mai marks

- **The grid's own query.** `Mai_Grid::get_query()` marks it before it runs, for every post grid. WordPress usually splits a grid query: it first sends an ID-only statement built from the same template with `{posts}.ID` as the select list (`class-wp-query.php:3418-3440`), then loads the posts. Mai prepares the swap for both forms, the full one and the split one, and uses whichever WordPress sends.
- **Mai's ID-only copy,** in `Mai_Query_Cache::fetch_ids()`, during the page and after it.

The mark is a property on the query, never a query var, so a copy another plugin builds from the grid's vars does not carry it.

**Grids without the ID tiebreaker get it.** Today only grids that defer their excludes get `, {posts}.ID DESC` added to their `ORDER BY` (`class-mai-grid.php:1100`). Without it, posts with the same date can come back in either order, so no form can promise the same posts. Every grid that does not count rows (`no_found_rows`, so not Load More) now gets the tiebreaker. Posts with the same date then always come back highest ID first. That is the trade already accepted for deferring grids in beta.5: the order among ties was arbitrary, and now it is fixed. Load More grids are left out, because their second page would be ordered without it.

### 3. When Mai swaps

All of these must hold. Any one failing means today's statement goes out unchanged.

- **The swap is not turned off:** by the filter, or for 24 hours after a failure (section 5).
- **The database passed testing** (section 4).
- **The query is marked** (section 2).
- **The taxonomy filters are a covered shape:** one level, no nesting, and the rules in section 1 for AND and OR. Any taxonomy.
- **No search, no meta query, no row counting.** `s` is empty, the search part of the where is empty after the last `posts_search` callback, and there is no `SQL_CALC_FOUND_ROWS`.
- **Every part is as WordPress built it.** For join, where, group-by, DISTINCT and fields, the value after the last filter equals the value at the first filter. First looks are at `PHP_INT_MIN` on `posts_where`, `posts_join`, `posts_groupby`, `posts_distinct` and `posts_fields`. The last look is at `PHP_INT_MAX` on `posts_clauses_request`, after every clause filter (WC Memberships edits at `posts_clauses` 999).
- **A missing record means step aside.** list-category-posts, My Content Dash and The Blog Fixer remove other plugins' filters mid-request (`remove_all_filters`), so a first look can be missing.
- **The join and the taxonomy condition are exactly WordPress's.** The first-look join must equal the joins of a fresh `WP_Tax_Query` built from `$query->tax_query->queries`, so nothing else is joined. The first-look where must contain its condition exactly once. Group-by must be exactly `{posts}.ID`, DISTINCT empty, and fields exactly `{posts}.*` or `{posts}.ID`.
  - A fresh object is needed because `WP_Tax_Query::get_sql()` never resets its alias list (`class-wp-tax-query.php:60`, `:419`), so a second call on WordPress's own object names the joins differently. Checked on local eurweb, for one filter and for two filters joined with OR: the fresh join equals WordPress's, and its condition appears once in WordPress's where.
- **The order has no ties and needs no join.** The final `ORDER BY` ends with `{posts}.ID ASC` or `{posts}.ID DESC`, and does not mention the term relationships table or any of its join names.
- **There is a `LIMIT`.** Swiftype removes it on its search pages, and without one the speed gain is unmeasured.
- **The statement is unchanged at `posts_request`.** Recorded at `PHP_INT_MIN` and compared at `PHP_INT_MAX` (Revisionary rewrites it during logged-in revision previews).
- **The statement has no `%` placeholder escape.** WordPress strips those in its own `query` filter at priority 0 (`class-wpdb.php:2428`), so a statement with one would never match. Grids have none today.
- **The statement reaching the database is the one WordPress finished.** The swap happens in `$wpdb`'s `query` filter at `PHP_INT_MAX`, on an exact text match only. Mai prepares the swap for that one statement and drops it once used or once the query is done. A plugin that changed the statement anywhere on the way, including `posts_request_ids` or its own `query` callback, makes it not match.

**Gotcha for the build.** `WP_Query` has a magic `__get()` (`class-wp-query.php:4117`), so `$query->prop['key'] = $value` on a property that is not set yet is silently dropped. Assign whole values: `$query->prop = $array`.

### 4. Which databases

Mai reads `$wpdb->db_server_info()`. It sends no statement.

- **MySQL from 8.0.16.** Live eurweb's 8.0.46 already picks the fast plan (section "What we found").
- **MariaDB from the lowest tested version that passes** (section "Measurements before release"), provided every tested version above it passes too. Otherwise MariaDB keeps today's statement.
- **Anything else keeps today's statement:** older MySQL, servers whose version Mai cannot read, and anything unknown.

There is only a lowest version per database, not a list to keep up to date. A new version above it gets the swap without a Mai Engine update. Correctness does not depend on the version, only speed does, and every new major version gets the same speed test.

MariaDB's version string can carry a `5.5.5-` prefix (`5.5.5-10.11.6-MariaDB`), so its version is read from the number right before `-MariaDB`. `$wpdb->db_version()` alone would read that as 5.5.5.

### 5. If a swapped statement fails

It failed when Mai swapped it, `$wpdb->last_error` is set, and `$wpdb->last_query` is the swapped text. Then:

- **The copy:** `fetch_ids()` runs the copy again without the swap, and carries on as today.
- **The grid's own query:** Mai's `posts_results` callback, already at `PHP_INT_MIN` for failed grid statements, sends the original ID statement once in its place and loads those posts, as WordPress would have.
- **Both:** Mai resets WordPress's query cache, as today's failure path does (`forget_failure()`), because WordPress stored the failed empty result under the query's key. It turns the swap off on the site for 24 hours with a transient, and writes one line to the debug log when `WP_DEBUG_LOG` is on.

A failure that is really something else, such as a lost connection, also turns the swap off for a day. That costs only the speedup.

### 6. Changes to existing code

- **The old optimizer is replaced.** `Mai_Post_Grid_Query_Optimizer` (`lib/classes/class-mai-post-grid-query-optimizer.php`), its tests, its two `bin/` scripts and the `mai_post_grid_tt_ids` check in `Mai_Query_Cache::is_cacheable()` are removed. The new class takes its name and its filter, so Mai has one grid query optimizer.
- **`Mai_Grid::get_query()`:** marks the query, and adds the tiebreaker query var for every grid that does not count rows.
- **`Mai_Query_Cache::fetch_ids()`** (`class-mai-query-cache.php:942`): marks the copy, and adds the failure check above before its own checks. After a swap, `$wpdb->last_query` is the swapped text, so the existing error check at `:969` cannot match a failure. The key check (`:1006`) and the ID-only check (`:1000`) are unchanged, because the copy's request text is unchanged.
- **`Mai_Query_Cache::posts_results()`:** also treats the swapped text of the grid's statement as the grid's own failure. Today it compares `$wpdb->last_query` with the query's request, which would no longer match.
- **`mai_register_query_cache()`** (`lib/functions/query-cache.php:21`): registers the first-look and last-look callbacks and the `query` callback once. Each returns at once unless the query is marked, or, for `query`, unless a swap is prepared.

### Filter

- `mai_post_grid_optimize_query` (bool, default now `true`): the old optimizer's filter, kept for the new one. Return `false` to turn the swap off for a site.

## Which grids

**Covered:**

- Grids that defer their excludes, and grids without excludes.
- One or more taxonomy filters, joined with AND, or joined with OR when all are `IN`. `NOT IN`, `AND` and `EXISTS` filters can sit next to `IN` filters under AND.
- Sorted by any post column (date, title, modified, comment count, menu order) with the ID tiebreaker. Each sort is in the speed test, and one that misses the bar is left out.
- Any taxonomy: categories, tags and custom taxonomies, such as recipe or product categories (Mike, 2026-10-04). They all use the same table, so the SQL is the same.

**Not covered, so today's statement:**

- Load More grids, meta queries, search, nested taxonomy filters, OR with a filter that is not `IN`.
- Pages where a plugin changes the grid's SQL: search pages with wpseo-local, secondary-title or Swiftype, author filters with co-authors-plus, Revisionary previews, and WP Fusion or WC Memberships when they hide content.
- Databases that did not pass.

It only makes a real difference for big taxonomies on big sites. On small ones the database already answers quickly, and nothing changes.

## Tests

Integration tests, on a real database:

- **Same posts.** For each covered shape, the posts from the swapped statement equal the posts from today's statement:
  - one category with child categories, one tag, one custom taxonomy registered by the test, many terms (29, like eurweb's), a term with no posts
  - category and tag joined with AND, and with OR
  - category `IN` with category `NOT IN` (lamag's and orangecoast's shape), and with `mai_display` `NOT IN` (eurweb's home page shape)
  - with `post__not_in`, several post types, publish and private
  - `LIMIT` 2, 32 and 200, posts with the same date, each covered sort
  - the copy, the grid's own split statement, and its full statement
- **The swap happens** when every check passes. These tests fail if a WordPress update changes how core writes this SQL, which would otherwise go unnoticed.
- **Today's statement goes out unchanged** when the filter returns false, and for each case below:
  - nested filters, OR with `NOT IN`, a meta query, a search, Load More
  - a plugin changes the where, join, group-by, DISTINCT or fields at default priority, or the where at `posts_clauses` 999
  - a plugin adds to the search part with `s` empty
  - a plugin changes the statement at `posts_request`, `posts_request_ids` or in its own `query` callback
  - the tiebreaker is removed, the `ORDER BY` names a join, or there is no `LIMIT`
  - a first-look callback was removed mid-request
  - the statement has a `%` placeholder escape
- **The tiebreaker** is added to grids that do not count rows, and not to Load More grids.
- **The rebuild after the page swaps too** (`run_queue()`), and the copy's key still matches the grid's, so the note is stored.
- **Failure:** a `query` callback added after Mai's breaks the swapped statement, for the copy and for the grid's own query. The grid still shows the right posts, nothing wrong is stored, the 24-hour transient is set, one log line is written, and WordPress's query cache was reset.
- **Database check** (unit tests, plain strings): MySQL `8.0.15` off, `8.0.16` on, `8.0.46-0ubuntu0.22.04.4` on, Percona `8.4.11-11` on, `5.7.44` off, Aurora `8.0.mysql_aurora.3.04.0` off, and MariaDB strings with and without the `5.5.5-` prefix against the minimum the measurements set.

The integration suite runs on local MySQL 9.7, and in Docker on MySQL 8.0 and 8.4 and MariaDB 10.6, 10.11, 11.4 and 11.8, through `WP_TESTS_DB_HOST`.

## Measurements before release

On each database above, in Docker, with copies of local eurweb (85,000 posts), local larrybrownsports (145,000 posts) and one small local site:

- **Statements:** each site's real grid statements, captured on article and home page views, plus the 11 eurweb terms from 18 to 64,000 posts used in the earlier lab, and eurweb's custom `mai_display` taxonomy. Each at the grid's LIMIT and at LIMIT 2 and 32, in every covered shape and sort.
- **Same posts** from both forms, every time.
- **Speed:** both forms alternating in one session, at least 10 runs each, medians, plus the plan from `EXPLAIN`.
- **The bar:** for every statement, the swapped median is no more than today's median plus 0.5 ms or 10%, whichever is larger. A database version, shape or sort that misses the bar on any statement is left out. That sets the MariaDB minimum and confirms the MySQL one.
- **The checks' own cost:** time added to a page with ten grids and the swap on, against off. It should be well under a millisecond.
- **Whole pages:** cold article views on local eurweb with the swap on and off, following `task-14-rules.md`, to confirm the 1.5 s gain.

## Rollout

1. Ship in a beta, on by default. Live eurweb and larrybrownsports get it with the beta. Pushing to them is a write, so Mike runs the commands.
2. Measure cold article views on live eurweb, and watch both sites' PHP error logs for the failure line.
3. Once it feels solid, totalprosports.
4. Then a release for every site.

No release, tag or push without Mike asking.

## What we found (2026-10-04)

- **Live eurweb picks the fast plan.** One read-only `EXPLAIN FORMAT=TREE` of the swapped statement on MySQL 8.0.46, through `mai-sites run`: a backward scan of the posts index on post type, status and date, a nested-loop semijoin into term relationships, stopping at the `LIMIT` of 32. The local copy ran the same plan in 0.09 ms. Output kept in `/tmp/t15-explain-live.out`.
- **The registry runs MySQL 8.0.45 or newer.** One site per server, all 22 servers: 19 on 8.0, 3 on 8.4. None runs MariaDB or MySQL 5.7.
- **WordPress as a whole is different.** wordpress.org's figures, from the database version every site sends when it checks for updates (`wp-includes/update.php:79-103`): about 60% MariaDB, 22% MySQL 8 or newer, 17% older MySQL. Mai Engine's 5,000 sites probably sit somewhere between the two.
- **No plugin in the fleet would break the swap or quietly undo it.** On 38 local sites, 65 plugins and 3 themes hook the SQL filters, and every callback that can reach a grid query was read. Those that change a grid's SQL in some settings are listed under "Which grids". There Mai would step aside.
- **On real page views, every grid copy reached the database exactly as WordPress built it.** A temporary probe on the article and home page of local eurweb and local larrybrownsports (36 copies) saw no change to any part, the request or the statement. eurweb's six heavy article grids and ten home grids all have a covered shape.
- **Found along the way:**
  - list-category-posts strips Mai's ID tiebreaker for the rest of a request after each `[catlist]`. The swap then steps aside (no tiebreaker), and the copy's key check already catches it.
  - The two earlier attempts: the June optimizer (removes the taxonomy filter from the query args, so plugins that read the args can show wrong posts; off by default) and a temporary `posts_clauses` fix on live eurweb, which is gone.

## Risks

- **A plugin's `query` callback registered at `PHP_INT_MAX` after Mai's sees the swapped statement.** Harmless unless it looks for the join text. None in the fleet does.
- **A database drop-in that never applies the `query` filter.** Then Mai never swaps, and the site keeps today's speed. Query Monitor's drop-in applies it.
- **A WordPress update changes how core writes this SQL.** The checks stop matching and sites quietly keep today's speed. The "swap happens" tests catch it on the next test run.
- **A future MySQL or MariaDB version plans the `EXISTS` worse.** It would still show the right posts. The speed test runs on each new major version.
- **A brief database error during a swapped statement turns the swap off for a day.** It costs only the speedup on that site.
- **The tiebreaker changes which of two same-second posts shows first** on grids that did not have it. Both orders were possible before.
