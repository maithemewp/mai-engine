# Grids: cheaper taxonomy queries on big sites

Status: built on the `grid-taxonomy-optimizer` branch and measured on local eurweb (MySQL 9.7.1, 2026-10-05, see "Results"), then revised after the whole-branch review. Task 8, the runs in Docker on MySQL 8.0 and 8.4 and on MariaDB, is still to come. It sets the MariaDB minimum and confirms the MySQL one. Comes from `docs/ideas/2026-10-04-grid-related-posts-query-cost.md`.

## Why

On eurweb (85,000 posts) every article shows six related-posts grids. When their lists have to be rebuilt, after any post save and after a Mai Engine update, each grid sends one statement that takes about 250 ms. That is about 1.5 s on every cold article view (measured on the local copy, `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-results.md`, "Cold article view on eurweb").

The statement is slow because WordPress joins the category table, which lists a post once per matching category, and then removes the duplicates with `GROUP BY`. To do that, MySQL collects all 81,500 matching posts and sorts them, only to keep the newest few.

Written with `EXISTS` instead, the same filter returns the same posts in under 1 ms, because the database can walk the posts newest first and stop once it has enough. Mid-size terms gain less: a 33,408-post category took 103 ms today and 95 ms swapped.

## Goals

- Grid queries filtered by taxonomy cost a fraction of today's on big sites, on every Mai Engine site where it is proven safe. Mike, 2026-10-04: worth it, fast, and able to run on 5,000 sites.
- On by default. The filter only turns it off.
- Every grid shows the posts it shows today, in the same order.
- Never slower than today, on any site or database.
- Not brittle. Mai never parses SQL. It rebuilds what WordPress would have written, with WordPress's own code, and compares it exactly. Any difference means today's statement goes out unchanged. A failed check costs the speedup, never correctness.

## Non-goals

- Queries that are not Mai post grids: archive pages, other plugins' queries. A later idea, once this has run for a while.
- Grids with Load More (they count rows with `SQL_CALC_FOUND_ROWS`), random order, meta queries, search, nested taxonomy filters, or no taxonomy join at all (only `NOT IN` filters, or terms that no longer exist).
- MySQL before 8.0.16. MySQL 5.7 reached end of life in October 2023, and before 8.0.16 MySQL cannot plan an `EXISTS` like a join (MySQL 8.0 manual, "Optimizing IN and EXISTS Subquery Predicates with Semijoin Transformations").
- Database layers other than WordPress's own `wpdb` and Query Monitor's (section 4).
- Fix 2 from the idea doc (one fetch per page for grids that differ only in count). With this change those statements are cheap.

## How it works, in plain terms

1. **A Mai post grid builds its query, as today.** Mai marks the grid's query, and the ID-only copy it runs for grids that defer their excludes.
2. **WordPress builds the SQL.** Mai records each part (join, where, group-by, DISTINCT, fields) twice: when WordPress hands it to the first plugin filter, and after the last plugin filter. It does the same for the finished statement. These are string comparisons and cost microseconds.
3. **Nothing more happens unless the statement actually goes to the database.** Most grid views are answered by Mai's or WordPress's cache and send nothing.
4. **When the statement goes to the database,** Mai checks it at the last moment. It rebuilds WordPress's taxonomy SQL with WordPress's own code and compares. If anything differs, or anything changed between the two looks, the statement goes out unchanged. Otherwise Mai swaps it for the `EXISTS` form.
5. **The database returns the same posts, fast.** Everything after that is today's code.
6. **If a swapped statement fails, or takes over 1 second,** Mai turns the swap off on that site for 24 hours and writes one line to the PHP error log. A failed statement is sent again unswapped, so the page still shows its grid.

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

After the swap, on MySQL:

```
SELECT eur_posts.ID FROM eur_posts
WHERE 1=1 AND EXISTS ( SELECT /*+ NO_SEMIJOIN(DUPSWEEDOUT) */ 1 FROM eur_term_relationships WHERE eur_term_relationships.object_id = eur_posts.ID AND eur_term_relationships.term_taxonomy_id IN (1,40271,...) )
AND eur_posts.post_type = 'post' AND ((eur_posts.post_status = 'publish'))
ORDER BY eur_posts.post_date DESC, eur_posts.ID DESC
LIMIT 0, 32
```

Three exact text replacements on the finished statement, and nothing else:

- WordPress's taxonomy joins become empty.
- WordPress's taxonomy condition becomes the rewritten condition (below).
- `GROUP BY {posts}.ID` becomes empty.

Each piece must be non-empty and appear exactly once in the statement, or Mai steps aside. Every `IN` list comes from WordPress's own SQL text, never from the query's settings: Mai grids pass term IDs, and WordPress turns them into different numbers, adding child terms (5,847 eurweb terms have a term ID that differs from the number WordPress puts in the SQL).

**Rebuilding WordPress's taxonomy SQL.** Mai makes one fresh `WP_Tax_Query` from `$query->tax_query->queries`, sets its public `primary_table` and `primary_id_column`, and calls its public `get_sql_for_clause()` on each filter in order, passing the filter list, the same way WordPress's own `get_sql_for_query()` does (`class-wp-tax-query.php:302`, `:384`). That gives every filter's join and condition with WordPress's own table names (the table itself, then `tt1`, `tt2`), and WordPress's own sharing of one table between OR filters. Mai puts them back together the way WordPress does and checks that the result equals WordPress's join and condition exactly. A fresh object is needed because `get_sql()` never resets its table-name list (`class-wp-tax-query.php:60`, `:419`), so calling it again on WordPress's object names the tables differently.

**The rewritten condition:**

- **Filters joined with AND:** each `IN` filter becomes its own `EXISTS`, on the table name WordPress gave it. `NOT IN`, `AND` and `EXISTS` filters already use their own subquery and stay exactly as WordPress wrote them. The pieces are joined with plain `AND`, without WordPress's outer brackets, so each `EXISTS` sits directly in the top-level `AND`. MySQL needs that to plan it like a join.
- **Filters joined with OR, all of them `IN`, sharing one table:** WordPress's whole condition, including its own brackets, goes inside one `EXISTS`. Without the brackets the `OR` escapes and the statement returns the newest posts on the site (tested).
- **OR with any filter that is not `IN`, or OR filters with more than one table:** Mai steps aside. An `EXISTS` inside an OR cannot be planned like a join. Filters written with a lowercase `in` get their own table under OR in WordPress (`class-wp-tax-query.php:509`), so they land here.
- **A filter whose condition is `0 = 1`** (its terms or its taxonomy no longer exist): Mai steps aside. The grid is already broken, and there is nothing to gain.

**The MySQL hint.** MySQL 8.0 and 8.4 have a bug that discards valid rows when the database picks one particular plan for an `EXISTS` (duplicate weedout fed by a sort; MySQL bug 120943, fixed in 9.2.0, not fixed in 8.0 or 8.4). The bug's own example forces that plan with hints, and whether a grid query can reach it on its own is unknown. Mai rules it out: on MySQL, every `EXISTS` carries the hint `NO_SEMIJOIN(DUPSWEEDOUT)`, which forbids only that plan. The Docker tests check that every plan on MySQL 8.0 and 8.4 honours the hint. MariaDB gets no hint.

**Why this is the same filter.** A post passes today's statement when its rows in the term table match. The `GROUP BY` only removes the duplicate copies of a post the join made. `EXISTS` asks the same question once per post. Several joins become several `EXISTS`, each asking about its own filter, which is exactly what the joins checked. The `ORDER BY` ends with the post ID, so the order has no ties, and `LIMIT` picks the same posts. A reviewer tested every covered shape, sort and offset on eurweb's data and found no difference.

### 2. Which queries Mai marks

- **The grid's own query.** `Mai_Grid::get_query()` marks it before it runs, for every post grid. WordPress usually splits a grid query: it first sends an ID-only statement built from the same template with `{posts}.ID` as the select list (`class-wp-query.php:3418-3440`), then loads the posts. Without a split (500 or more posts and no persistent object cache) it sends the full `{posts}.*` statement. Mai can swap either.
- **Mai's ID-only copy,** in `Mai_Query_Cache::fetch_ids()`, during the page and after it. Only the copy may have `{posts}.ID` as its select list. A grid that a plugin turned into an ID query is not swapped.

The mark is a property on the query, never a query var.

**Every grid without Load More gets the ID tiebreaker.** Today only grids that defer their excludes get `, {posts}.ID` added to their `ORDER BY` (`class-mai-grid.php:1100`). Without it, posts that tie on the sort can come back in either order, so no form can promise the same posts. Grids that do not count rows now all get it:

- It is set in the same place as today, after `can_defer_excludes()` decides, and removed from the query afterwards for every grid, not only deferring ones (`class-mai-grid.php:380-386`).
- Load More grids are left out, because their second page would be ordered without it.
- Posts that tie now always come back in ID order. On sorts where almost every post ties, such as menu order on a site that never set it, the tiebreaker decides the whole grid (see "Ties" below).
- It is always `, {posts}.ID DESC`, whatever the sort's direction. Today it copies the direction of the last sort key (`class-mai-grid.php:1118-1122`), which changes.
- Cache keys of grids that did not defer change once. A Mai Engine update flushes grid results anyway (`lib/admin/upgrade.php:58`).

### 3. When Mai swaps

All of these must hold. Any one failing means today's statement goes out unchanged.

- **The swap is on:** the filter allows it, and it was not turned off for 24 hours or for this request after a problem (section 5). The `query` callback checks this again when the statement is sent, since something can turn the swap off after the swap was prepared.
- **The database and database layer passed** (section 4).
- **The query is marked** (section 2).
- **The taxonomy filters are a covered shape:** one level, no nesting, any taxonomy, the rules in section 1, and at least one `IN` filter, so there is a join to remove.
- **No search, no meta query, no row counting, no random order.** `s` is empty, the search part of the where is empty after the last `posts_search` callback (`PHP_INT_MAX`), there is no `SQL_CALC_FOUND_ROWS`, and the `ORDER BY` has no `RAND(` (core uses the same test, `class-wp-query.php:3252`). A seeded `RAND(7)` gets the tiebreaker added and would otherwise pass, and the two forms then return different posts (tested).
- **Every part is as WordPress built it.** For join, where, group-by, DISTINCT and fields, the value after the last filter equals the value at the first filter. First looks are at `PHP_INT_MIN` on `posts_where`, `posts_join`, `posts_groupby`, `posts_distinct` and `posts_fields`. The last look is at `PHP_INT_MAX` on `posts_clauses_request`, after every clause filter (WC Memberships edits at `posts_clauses` 999).
- **A missing record means step aside.** list-category-posts, My Content Dash and The Blog Fixer remove other plugins' filters mid-request (`remove_all_filters`), so a first look can be missing.
- **The join and the taxonomy condition are exactly WordPress's,** rebuilt as in section 1. The first-look join must equal the rebuilt joins, so nothing else is joined. The first-look where must contain the rebuilt condition exactly once. Group-by must be exactly `{posts}.ID`, DISTINCT empty, and fields exactly `{posts}.*`, or `{posts}.ID` for Mai's copy.
- **The order has no ties and needs no join.** The final `ORDER BY` ends with `{posts}.ID ASC` or `{posts}.ID DESC`, and does not mention the term table or any of its table names. The sort is one that passed the speed test (section "Measurements").
- **There is a `LIMIT`.** Swiftype removes it on its search pages.
- **The statement is unchanged at `posts_request`.** Recorded at `PHP_INT_MIN` and compared at `PHP_INT_MAX` (Revisionary rewrites it during logged-in revision previews).
- **The statement has no `%` placeholder escape.** WordPress strips those in its own `query` filter at priority 0 (`class-wpdb.php:2428`), so a statement with one would never match. Grids have none today.
- **For the grid's own query, Mai's failure callback is still registered** on `posts_results`, both when the swap is prepared and when the statement is sent. My Content Dash and The Blog Fixer can remove every callback there, and then a failed swap could not be repaired.
- **The statement reaching the database is the one WordPress finished.** The swap happens in `$wpdb`'s `query` filter at `PHP_INT_MAX`, on an exact text match only. A plugin that changed the statement on the way, including `posts_request_ids` or its own `query` callback, makes it not match.

The tax rebuild (section 1) runs only at this last step, when the statement actually goes to the database. Its result is kept for the rest of the request, keyed by the tax filters and the posts table, so a grid and its copy rebuild once. Not keyed by the query object: PHP reuses an object's ID as soon as it is freed, so a later grid could pick up an earlier grid's rebuild. Reviewer's measure: 0.13 ms per rebuild for eurweb's biggest category, with no database queries.

**How prepared swaps are kept.** At `posts_request` `PHP_INT_MAX`, for a marked query that passed the cheap checks, Mai adds an entry to a list: the original text, the owner query, and its form (copy, split, full). A query has at most one entry: one that runs again replaces its earlier entry. The `query` callback is registered once and returns at once when nothing is prepared, which costs about 0.2 µs per statement (reviewer's measure). Registering it once also lets tests watch the swapped statement with a later callback at the same priority. When a statement matches more than one entry, the most recent wins: a copy runs inside its grid's query, and its text equals the grid's split form. While a copy runs, from the moment it is marked until its entry is dropped, only a copy's entry can be taken, so a copy that prepared no entry of its own can never take its grid's. The copy's entry is dropped in a `finally` after `$copy->query()` returns. The grid's entry is dropped at `posts_results`, which every grid query reaches, including ones answered by a cache, and again in a `finally` after `$query->query()` in `Mai_Grid::get_query()`, for a query that throws before `posts_results`. The whole list is cleared on any failure.

**Anything that throws inside the swap** (an object cache that fails on the transient read, a tax query holding a closure, a type error) sends the statement unchanged, turns the swap off for the rest of the request, clears the list and logs one line. The day-long transient is not written for it.

**Gotchas for the build.**

- `WP_Query` has a magic `__get()` (`class-wp-query.php:4117`), so `$query->prop['key'] = $value` on a property that is not set yet is silently dropped. Assign whole values: `$query->prop = $array`.
- Hook callbacks accept any value and hand back anything that is not a string unchanged. Another plugin can pass `null` down a filter, and a `string` parameter would turn that into a fatal error.

### 4. Which databases

Mai checks only when a swap is about to happen. The checks send no statement, so they come before the read of the 24-hour transient, which is a statement of its own on a site without a persistent object cache. A site that can never take the swap then pays nothing more. When a check fails, the swap is off for the rest of the request, so no more swaps are prepared, and the transient is not written. The server is read once per request and the answer kept. The layer and the mysqli mode are checked on every swap, since a plugin can change either during a request.

- **The database layer must be WordPress's own `wpdb` or Query Monitor's `QM_DB`,** which passes every statement through unchanged (`classes/DB.php`). Any other drop-in steps aside. That covers:
  - W3 Total Cache's database cache, which stores results under the text before the `query` filter, failures included. One failed swap could leave a grid empty until its cache expired.
  - HyperDB, LudicrousDB, the SQLite plugin, and anything unknown.
- **MySQL from 8.0.16.** Live eurweb's 8.0.46 already picks the fast plan (section "What we found"). Version strings marked `sqlite`, `Vitess` or `TiDB` are not MySQL and step aside.
- **MariaDB from the lowest tested version that passes** (section "Measurements"), provided every tested version above it passes too. Otherwise MariaDB keeps today's statement. Its version is the first `X.Y.Z` after an optional `5.5.5-` prefix (`5.5.5-10.11.6-MariaDB`). `$wpdb->db_version()` alone would read that as 5.5.5.
- **mysqli must not be in exception mode** (`MYSQLI_REPORT_STRICT`). WordPress turns it off when it connects (`mysqli_report( MYSQLI_REPORT_OFF )` in `wpdb::db_connect()`, `class-wpdb.php:1966`), but any code can turn it back on. A failed swapped statement would then throw out of `WP_Query`, before Mai could send it again.
- **Anything else steps aside,** including when `db_server_info()` returns `false` or an empty string, or throws. Core throws when the connection is not mysqli (`class-wpdb.php:4218-4220`), so the read is wrapped in `try`/`catch ( Throwable )`. A MariaDB minimum that is not written as `X.Y.Z` keeps MariaDB off.

There is only a lowest version per database, not a list to keep up to date. A version above it gets the swap without a Mai Engine update. The 1-second guard in section 5 turns the swap off on a site where a newer version plans it badly, and the speed test runs on each new major version.

### 5. If a swapped statement fails or is slow

When Mai swaps a statement, it records `$wpdb->num_queries`, the length of WordPress's list of failed statements (`$EZSQL_ERROR`), the swapped text and the owner query. The swapped statement failed when any of these holds:

- **Nothing was sent after the swap:** `$wpdb->num_queries` is what was recorded. A later `query` callback that returns an empty string or `false` stops `wpdb::query()` before it sends anything or clears its last result, so `get_col()` and `get_results()` hand back the rows of the statement before, with no error.
- **WordPress's list of failed statements gained one with the swapped text.** `wpdb::print_error()` adds every failed statement to `$EZSQL_ERROR` before it checks whether errors are shown, so this holds after a later statement cleared `$wpdb->last_error`.
- **`$wpdb->last_error` is set** and either `$wpdb->num_queries` is exactly one more than recorded, or `$wpdb->last_query` is the swapped text, or the query got no rows and at least one statement was sent since the swap. The last rule is the one Mai's ID-only copy already uses today for an empty copy, and it catches a plugin that both rewrites the statement and sends its own. The count holds whatever later `query` callbacks did to the text. The text holds when a later callback sent a statement of its own, or `$wpdb` reconnected and sent it twice. Either way the failure is pinned on the right query.

The 24-hour transient is read once per request, inside the `query` callback right before the first swap, after the database checks (section 4) and before the statement count is recorded, since on a site without a persistent object cache the read is a statement of its own.

On a failure, in this order:

1. Read `$wpdb->last_error`, before anything else sends a statement.
2. Reset WordPress's query cache (`Mai_Query_Cache::forget_failure()`, one helper for the copy and the grid), because WordPress stored the failed result under the query's key before Mai saw it. This comes before the transient write, which is a statement of its own and can fail too.
3. Turn the swap off for the rest of this request, and clear every prepared swap and record. Then write the 24-hour transient, which holds the reason and the error (`failed: ` and `$wpdb->last_error`), and read it back.
4. Write one line to the PHP error log, whatever `WP_DEBUG_LOG` says, with the error. It says "off for 24 hours", or "off for this request only" when the transient could not be read back.
5. Send the original statement once, unswapped:
   - **The copy:** `fetch_ids()` runs `$wpdb->get_col( $copy->request )` and carries on with today's checks.
   - **The grid's own query:** a callback on `posts_results` at `PHP_INT_MIN`, which runs whether or not the grid uses the result cache, resends `$query->request` the way WordPress sent it. For the split form that is `get_col`, then `_prime_post_caches()` with the query's flags, then `get_post`. For the full form it is `get_results`, then `get_post`. The list is exactly today's, so it is stored as usual. If the resend fails too, today's failure path applies and nothing is stored.

**Slow.** When a swapped statement takes over 1 second, Mai turns the swap off for 24 hours and logs one line, and the result stands. Both the copy's statement and the grid's own are timed: the copy from the swap until `$copy->query()` returns, whose statement is the only work inside it, and the grid from the swap until `posts_results`, so for the split form the time includes loading the posts. The line names the form (`copy form`, `grid split form`, `grid full form`), and the transient holds `slow: ` and the same. A good swap takes about a millisecond on the big categories it exists for, and mid-size ones take about as long as today (98 ms against 103 ms for a 33,408-post eurweb category). So the limit only catches a plan gone badly wrong, never a busy moment on a normal one.

**Costs.** WordPress logs every failed statement to the PHP error log itself (`class-wpdb.php:1823`), and prints it on the page when `WP_DEBUG_DISPLAY` is on. Mai's own line goes to the PHP error log whatever `WP_DEBUG_LOG` says, so a turn-off shows on a live site. With the 24-hour switch, that is at most one failed statement and one line per site per day. A transient that cannot be stored, or something that throws inside the swap, turns the swap off for one request only, so its line can repeat on every request until the cause is fixed. A failure that is really something else, such as a deadlock, also turns the swap off for a day. That costs only the speedup.

### 6. Changes to existing code

- **The old optimizer is replaced.** These are removed:
  - The old code in `lib/classes/class-mai-post-grid-query-optimizer.php`. The file keeps its name and holds the new class.
  - Its five tests, `tests/phpunit/unit/PostGridQueryOptimizer{Args,Classify,Orderby,Register,Where}Test.php`.
  - `bin/grid-query-equivalence.php` and `bin/grid-equivalence-matrix.php`.
  - The `mai_post_grid_tt_ids` check in `Mai_Query_Cache::is_cacheable()`, and its two tests in `tests/phpunit/unit/MaiQueryCacheabilityTest.php:28` and `:32`.
  - The comment about "the optimizer's fast path" in `class-mai-grid.php:927-928`, and the stale line numbers in `.agents/elasticpress-grid-cache.md:58`.
  - The new class takes the old name and filter, so Mai has one grid query optimizer.
- **`Mai_Grid::get_query()`:** marks the query, drops its prepared swap in a `finally` after the query runs, sets the tiebreaker for every grid that does not count rows, and removes it afterwards for every grid.
- **`Mai_Grid::add_grid_orderby_tiebreaker()`** (new in 2.41.0, and called `add_deferred_orderby_tiebreaker()` until it applied to every grid): always appends `, {posts}.ID DESC`, and its docblock says why.
- **`Mai_Query_Cache::fetch_ids()`** (`class-mai-query-cache.php:942`): marks the copy, drops its prepared swap in a `finally`, and runs the failure and slow checks before its own checks. The key check (`:1006`) and the ID-only check (`:1002`) are unchanged, because the copy's request text is unchanged. `forget_failure()` becomes public and static, so the optimizer resets WordPress's query cache with the same helper.
- **`Mai_Query_Cache::posts_results()`:** unchanged. The new failure callback runs before it, at the same priority but registered first. After a successful resend `$wpdb->last_error` is empty, so it stores the list as usual.
- **`mai_register_post_grid_query_optimizer()`** (`lib/functions/performance.php:300`), as today, but on `init` at priority 9: registers the first-look and last-look callbacks (including `posts_search` and both ends of `posts_request`), the `query` callback and the failure callback once. Priority 9 makes the failure callback on `posts_results` run before `Mai_Query_Cache::posts_results()`, which shares its priority and registers at 10. Each callback returns at once unless the query is marked.
- **Tests that use `mai_grid_tiebreak` to spot a deferring grid** need another way, since every grid without Load More now carries it (`GridCacheStoreTest.php:139`, `GridKeptOnlyTest.php:228`, `:809`, `:846`, `:885`, `:894`, `:1074`, `:1486`). Tests that assert only deferring grids get it change (`GridDeferredExcludesTest.php:308-316`, `GridKeptOnlyTest.php:424-425`, `:1295-1296`).
- **`CHANGES.md`:** a 2.41.0 entry for the faster grid queries and the filter.

### Filter

- `mai_post_grid_optimize_query` (bool, default now `true`): the old optimizer's filter, kept for the new one. Read once per request. Return `false` to turn the swap off for a site.

## Which grids

**Covered:**

- Grids that defer their excludes, and grids without excludes.
- One or more taxonomy filters, joined with AND, or joined with OR when all are `IN` and share one table. `NOT IN`, `AND` and `EXISTS` filters can sit next to `IN` filters under AND.
- Any taxonomy: categories, tags and custom taxonomies, such as recipe or product categories (Mike, 2026-10-04). They all use the same table, so the SQL is the same.
- Sorted by date, author or ID, with the ID tiebreaker. Title, slug, modified date, menu order and comment count sorts were slower on a mid-size category in the speed test, so they keep today's statement (measured on MySQL 9.7.1, 2026-10-05).

**Not covered, so today's statement:**

- Load More grids, random order, meta queries, search, nested taxonomy filters, OR with a filter that is not `IN`, grids with no `IN` filter, and filters whose terms no longer exist.
- Pages where a plugin changes the grid's SQL: search pages with wpseo-local, secondary-title or Swiftype, author filters with co-authors-plus, Revisionary previews, and WP Fusion or WC Memberships when they hide content.
- Sites with another database layer (W3 Total Cache's database cache, HyperDB, LudicrousDB, SQLite), and databases that did not pass.
- On WordPress 6.4 and 6.5, a grid's own split statement. Those versions lay it out differently (core ticket 56841), so `split()` finds no match. Copies and full statements are still swapped there, since every other check compares WordPress's text with itself.

It makes a real difference for big taxonomies on big sites. On small ones the database already answers quickly, and nothing changes.

## Ties

Tied posts show newest first on every sort (Mike, 2026-10-04). A grid where nobody set the sort field, such as menu order on a site that never used it, then looks like a normal latest-posts list instead of showing posts from years ago. Today it shows whatever order the database happens to pick.

This also changes beta.5's deferring grids, whose tiebreaker followed the sort direction, so on ascending sorts their ties showed oldest first.

## Tests

Integration tests, on a real database:

- **Same posts.** For each covered shape, the posts from the swapped statement equal the posts from today's statement, both at the grid's `LIMIT` and with no `LIMIT`, so the whole set is compared. Each case also checks its result differs from the same statement with no taxonomy filter, so a swap that drops the filter cannot pass by luck.
  - one category with child categories, one tag, one custom taxonomy registered by the test, many terms (29, like eurweb's), a term with no posts
  - small and old terms whose posts are not the newest on the site
  - terms whose term ID differs from the number WordPress puts in the SQL, made on purpose in the fixture (a fresh install never makes them)
  - category and tag joined with AND, with OR, and AND with three `IN` filters (`tt1`, `tt2`)
  - an OR case that returns wrong posts if the brackets are dropped
  - category `IN` with category `NOT IN` (lamag's and orangecoast's shape), with `mai_display` `NOT IN` (eurweb's home page), and with `AND` and `EXISTS` filters
  - with `post__not_in`, several post types, publish and private, an offset
  - `LIMIT` 2, 32 and 200, posts with the same date, each covered sort
  - the copy, the grid's own split statement, and its full statement
- **The swap happens** when every check passes. These tests fail if a WordPress update changes how core writes this SQL, which would otherwise go unnoticed.
- **Today's statement goes out unchanged** when the filter returns false, and for each case below:
  - nested filters, OR with `NOT IN`, OR with lowercase `in`, no `IN` filter, a filter whose terms are gone, a meta query, a search, Load More, `rand` and `RAND(7)`
  - a grid with the result cache off (`mai_post_grid_cache` false) still swaps, and still recovers from a failure
  - a plugin changes the where, join, group-by, DISTINCT or fields at default priority, or the where at `posts_clauses` 999
  - a plugin adds to the search part with `s` empty
  - a plugin changes the statement at `posts_request`, `posts_request_ids` or in its own `query` callback
  - the tiebreaker is removed, the `ORDER BY` names a join, or there is no `LIMIT`
  - a first-look callback, or the failure callback on `posts_results`, was removed mid-request
  - the statement has a `%` placeholder escape
  - `$wpdb` is a class other than `wpdb` or `QM_DB`
  - a hook callback is handed `null`
- **The tiebreaker** is added to grids that do not count rows and removed afterwards, and is not added to Load More grids. It is `{posts}.ID DESC` on ascending and descending sorts alike.
- **The rebuild after the page swaps too** (`run_queue()`), and the copy's key still matches the grid's, so the note is stored.
- **Failure,** for the copy and for the grid's own query, split and full: a `query` callback added after Mai's breaks the swapped statement. The grid still shows the right posts, nothing wrong is stored, every prepared swap is cleared, the 24-hour transient is set, a second grid on the same page is not swapped, one log line is written, and WordPress's query cache was reset.
- **Slow:** a swapped statement held over the limit, the copy's and the grid's own split and full forms (a test lowers the limit and a later `query` callback waits with `usleep()`), turns the swap off for the day and keeps the right list.
- **Not sent, or failed out of sight:** a later `query` callback that returns an empty string for the swapped text, and a refused statement whose error a later statement cleared, both count as failed.
- **Checked again at the last moment.** The swap was turned off, the filter says no, or the failure callback is gone after the swap was prepared, and the statement goes out unchanged. A copy cannot take its grid's entry. A query has one entry. A grid query that throws leaves nothing prepared.
- **Robustness.** An exception inside the swap sends the statement unchanged and turns the swap off for the request. mysqli in exception mode steps aside. Another database layer on the same connection steps aside, end to end. A failed database check reads no transient.
- **Database check** (unit tests, plain strings): MySQL `8.0.15` off, `8.0.16` on, `8.0.46-0ubuntu0.22.04.4` on, Percona `8.4.11-11` on, `5.7.44` off, `8.0.38-mysql-on-sqlite-3.0.2` off, a `-Vitess` string off, `8.0.11-TiDB-v7.5.0` off, MariaDB strings with and without `5.5.5-` against the minimum the measurements set, a MariaDB minimum that is not `X.Y.Z` (`'0'`, `'abc'`, `' '`) off, and `false`, `''` and a throw all off.

The integration suite runs on local MySQL 9.7, and in Docker on MySQL 8.0 and 8.4 and MariaDB 10.6, 10.11, 11.4 and 11.8, through `WP_TESTS_DB_HOST`. Those four MariaDB versions are the most used today (about 9%, 19%, 9% and 16% of WordPress sites).

## Measurements

On each database above, in Docker, with copies of local eurweb (85,000 posts), local larrybrownsports (145,000 posts) and one small local site:

- **Statements:** each site's real grid statements, captured on article and home page views, plus:
  - the 11 eurweb terms from 18 to 64,000 posts used in the earlier lab, and eurweb's custom `mai_display` taxonomy
  - a term with fewer posts than the `LIMIT`, all of them old
  - mid-size terms covering 10 to 50% of posts
  - several post types, and publish plus private
  - every covered shape and every sort Mai offers
  - each at the grid's `LIMIT` and at `LIMIT` 2 and 32
- **Same posts** from both forms, every time.
- **Speed:** both forms alternating in one session, at least 10 runs each, medians, plus the plan from `EXPLAIN`.
- **No duplicate weedout on MySQL.** Every plan on MySQL 8.0 and 8.4 is checked for the plan the hint forbids.
- **The bar:** for every statement, the swapped median is no more than today's median plus 0.5 ms or 10%, whichever is larger. A database version, shape or sort that misses the bar on any statement is left out. That sets the MariaDB minimum, confirms the MySQL one, and sets the list of sorts.
- **The checks' own cost:** time added to a page with ten grids, answered from the cache and on a miss. It should be well under a millisecond.
- **Whole pages:** cold article views on local eurweb with the swap on and off, following `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`, to confirm the 1.5 s gain.

## Rollout

1. Ship in a beta, on by default. Live eurweb and larrybrownsports get it with the beta. Pushing to them is a write, so Mike runs the commands.
2. Measure cold article views on live eurweb, and watch both sites' PHP error logs for the failure and slow lines. Check `wp transient get mai_post_grid_optimize_off` on both through `mai-sites run` as well, since the transient holds the reason when the swap turned off.
3. Once it feels solid, totalprosports.
4. Then a release for every site.

No release, tag or push without Mike asking.

## What we found (2026-10-04)

- **Live eurweb picks the fast plan.** One read-only `EXPLAIN FORMAT=TREE` of the swapped statement (without the hint) on MySQL 8.0.46, through `mai-sites run`: a backward scan of the posts index on post type, status and date, a nested-loop semijoin into the term table, stopping at the `LIMIT` of 32. The local copy ran the same plan in 0.09 ms. Output kept in `/tmp/t15-explain-live.out`.
- **The registry runs MySQL 8.0.45 or newer.** One site per server, all 22 servers: 19 on 8.0, 3 on 8.4. None runs MariaDB or MySQL 5.7.
- **WordPress as a whole is different.** wordpress.org's figures, from the database version every site sends when it checks for updates (`wp-includes/update.php:79-103`): about 60% MariaDB, 22% MySQL 8 or newer, 17% older MySQL. Mai Engine's 5,000 sites probably sit somewhere between the two.
- **No plugin in the fleet would break the swap or quietly undo it.** On 38 local sites, 65 plugins and 3 themes hook the SQL filters, and every callback that can reach a grid query was read. Those that change a grid's SQL in some settings are listed under "Which grids". There Mai would step aside.
- **On real page views, every grid copy reached the database exactly as WordPress built it.** A temporary probe on the article and home page of local eurweb and local larrybrownsports (36 copies) saw no change to any part, the request or the statement. eurweb's six heavy article grids and ten home grids all have a covered shape.
- **Three reviews of this spec,** for same posts, the hook plan, and 5,000 sites, found the gaps this revision closes: seeded random order, the OR brackets, the table names for several AND filters, empty joins, the MySQL 8.0 and 8.4 weedout bug, W3 Total Cache's database cache, failure detection and recovery, and the cost of rebuilding on every view.
- **Found along the way:**
  - list-category-posts strips Mai's ID tiebreaker for the rest of a request after each `[catlist]`. The swap then steps aside (no tiebreaker), and the copy's key check already catches it.
  - The two earlier attempts: the June optimizer (removes the taxonomy filter from the query args, so plugins that read the args can show wrong posts; off by default) and a temporary `posts_clauses` fix on live eurweb, which is gone.

## Risks

- **A database bug we do not know about.** The MySQL weedout bug shows they exist. The hint rules out the known one, the Docker plans are checked, and the tests compare posts on every database version. A new major version gets the same tests.
- **A plugin's `query` callback registered at `PHP_INT_MAX` after Mai's sees the swapped statement.** Harmless unless it looks for the join text. None in the fleet does.
- **A plugin that edits the clauses at `PHP_INT_MAX` on `posts_clauses_request`, registered after Mai.** Mai would not see the change. It most likely ends in a database error and the fallback, not wrong posts. None in the fleet does.
- **A WordPress update changes how core writes this SQL.** The checks stop matching and sites quietly keep today's speed. The "swap happens" tests catch it on the next test run.
- **A brief database problem during a swapped statement turns the swap off for a day.** It costs only the speedup on that site.
- **The tiebreaker changes which tied posts show** on grids that did not have it. See "Ties".

## Results

### Whole pages on local eurweb (2026-10-05)

**A fully cold article view is about 1.8 s faster with the swap on.** With every note current, no difference showed above noise. This section covers local eurweb only, which runs MySQL 9.7.1. The results on MySQL 8.0, 8.4 and MariaDB come later, from the Docker runs (Task 8), and are not in this section.

**Numbers.** Medians in milliseconds, time to first byte, with 95% bootstrap intervals in square brackets. "Off minus on" is the time the swap saves.

- **Fully cold article views, 4 articles with 12 runs per arm each (48 per arm):** on 1,769 [1,719, 1,818], off 3,576 [3,550, 3,649].
  - Off minus on: +1,807 [+1,746, +1,896] as a difference of medians, and +1,844 [+1,805, +1,870] as a paired difference over 48 pairs.
  - Per article, on / off / difference of medians: realistic-editorial-technology-photography 1,737 / 3,676 / +1,939, sherri-shepherd-son-college 1,705 / 3,574 / +1,868, costco-motor-oil 1,765 / 3,536 / +1,772, mayor-jayden-williams-removal 1,814 / 3,575 / +1,760.
  - The expected gain was about 1.5 s. The six heavy statements cost 1.69 s together in the off arm, and the page time around them goes too.
- **The statements themselves, one cold article request per arm (MySQL's own timer):**
  - Swap off: the six heavy statements took 271 to 307 ms each, 1,690 ms together.
  - Swap on: the same six were swapped and took 2.6 to 9.8 ms each, 38 ms together. The two requests differed by 1,623 ms at the page, against 1,652 ms saved in the statements.
  - A small single-term grid statement (`LIMIT` 2) went from 0.37 to 0.84 ms when swapped.
  - Statements swapped in one cold request per arm: 7 on the article, 13 on the home page. With the swap on, no grid statement read more than 500,000 rows on either page. With it off, six did on each page.
- **Every note current, so no grid statement is sent:** the checks cost nothing that these runs can see.
  - Home page, 60 runs per arm: on 1,213 [1,182, 1,242], off 1,197 [1,173, 1,206]. Off minus on: −16 [−51, +16] as a difference of medians.
  - Article (sherri-shepherd-son-college), 60 runs per arm: on 1,366 [1,319, 1,452], off 1,377 [1,330, 1,413]. Off minus on: +11 [−88, +74].
  - Both intervals include 0. Both pages together, 118 pairs (leaving out the two stalled pairs, see below): paired difference −13 [−52, +2], so the swap on was at most about 50 ms slower, and the data cannot tell it from none.
  - The first article run (20 per arm) read 83 ms slower with the swap on, paired, and its interval just missed 0 (−83 [−147, −2]). I repeated it with 40 runs per arm and the other arm going first, and got +4 [−54, +87]. I read the first as noise.
  - Each page sent the same number of statements in both arms (12 on the article, 6 on the home page), and none was swapped. Each page had one small grid-shaped statement (0.4 to 2.4 ms, a newer-than-a-date list for one term), in the joined form in both arms.
- **Notes emptied before every view, so statements are sent, 12 runs per arm:** the gain. The article's statement check above matches its gain, so the checks show no cost there. I did not make that check for the home page.
  - Home page: on 2,218 [2,006, 2,293], off 3,641 [3,551, 3,769]. Off minus on: +1,423 [+1,316, +1,684].
  - Article (sherri-shepherd-son-college): on 1,753 [1,661, 1,859], off 3,590 [3,520, 3,655]. Off minus on: +1,837 [+1,705, +1,970].

**Method.**

- **Site:** local eurweb through the plugin symlink, this branch at `4717e1486`. PHP 8.4.25, MySQL 9.7.1, Redis object cache, WP Rocket. Rules from `.superpowers/sdd/2026-10-01-grid-cache-beta-5/task-14-rules.md`: `WP_DEVELOPMENT_MODE` empty and `SCRIPT_DEBUG` false while timing, `wp-config.php` restored with `cp` and checked with `cmp`, no `wp cache flush`.
- **Arms:** a temporary mu-plugin returned `false` from `mai_post_grid_optimize_query` when the query string said `t16=off`. Requests alternated on and off, and the arm that went first alternated each pair. The `t16` query string keeps WP Rocket from answering. No response carried WP Rocket's footer, and every response was a 200 except one, below.
- **Fully cold:** before every request, `wp eval 'wp_cache_set_posts_last_changed(); mai_cache()->flush();'`. That moves WordPress's query cache on and empties Mai's notes.
- **Every note current:** four priming requests, then the runs with nothing in between.
- **Timing and statistics:** `curl` `time_starttransfer`. Intervals come from 4,000 bootstrap resamples. A pair is one on and one off request for the same page and round. The four articles are the four newest published posts.
- **The 24-hour off transient** (`mai_post_grid_optimize_off`) was unset before the first run, after every round of cold requests and after every run. `debug.log` has no Mai line from these runs.
- **Raw data and drivers:** `/tmp/t16-pages/`, not kept in the repository.

**Machine load.** The 1-minute load average at the start and end of each run, all under 20 at the start: cold articles 7.84 and 8.99, warm home page 8.99 and 7.70, warm article 7.32 and 7.19, cold home page 7.19 and 14.92, cold article 14.92 and 7.60, warm home page repeat 6.36 and 7.03, warm article repeat 7.03 and 9.57. The earlier run on beta.5 sat near 4. The off arm took 3.58 s here against 2.96 s then, and a warm article view took 1.37 s against 1.05 s. I think the busier machine explains that, but I did not test it. The comparison inside this section is unaffected, since both arms alternated through the same minutes.

**One stall.** In the repeat of the warm article run, five requests in a row took 2.6, 7.6, 33.4, 29.5 and 2.7 s, and the third was a 502 on the off arm (nginx: `upstream prematurely closed connection`). I did not find the cause. I kept those rows, because medians resist them. Dropping the two pairs they touch moves the pooled article difference from +11 to +13 ms.
