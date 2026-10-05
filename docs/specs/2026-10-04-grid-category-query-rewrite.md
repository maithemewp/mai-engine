# Grids: cheaper "current category" queries on big sites

Status: draft for Mike's review (2026-10-04). Not built. Comes from `docs/ideas/2026-10-04-grid-related-posts-query-cost.md`.

## Why

On eurweb (85,000 posts) every article shows six related-posts grids. When their lists have to be rebuilt, after any post save and after a Mai Engine update, each grid sends one statement that takes about 250 ms. That is about 1.5 s on every cold article view (measured on the local copy, `task-14-results.md`, "Cold article view on eurweb").

The statement is slow because WordPress joins the category table, which lists a post once per matching category, and then removes the duplicates with `GROUP BY`. To do that, MySQL collects all 81,500 matching posts and sorts them, only to keep the newest few.

Written with `EXISTS` instead, the same filter returns the same posts in under 1 ms, because MySQL can walk the posts newest first and stop once it has enough.

## Goals

- The six heavy eurweb grids, and grids like them on other big sites, cost under 1 ms per statement instead of about 250 ms.
- Every grid shows exactly the posts it shows today, in the same order.
- Never slower than today, on any site or database Mai Engine runs on.
- Not brittle: wherever Mai cannot prove the rewrite is safe, it sends today's statement unchanged. A failed check costs the speedup, never correctness.
- Off by default. A site turns it on with one filter.

## Non-goals

- Rewriting the grid's own query. Only Mai's ID-only copy is rewritten (`Mai_Query_Cache::fetch_ids()`). Grids that do not defer their excludes never run a copy, so they are not sped up.
- Grids with more than one taxonomy filter, `NOT IN`, `AND` or `EXISTS` operators, meta queries, search, or a taxonomy other than category and tag. lamag and orangecoast add a second category filter to every grid, so they keep today's statement.
- MySQL before 8.0.16. MySQL 5.7 reached end of life in October 2023, and before 8.0.16 MySQL cannot plan an `EXISTS` like a join (MySQL 8.0 manual, "Optimizing IN and EXISTS Subquery Predicates with Semijoin Transformations").
- Fix 2 from the idea doc (one fetch per page for grids that differ only in count). With this rewrite those statements cost under 1 ms each.
- The June optimizer (`Mai_Post_Grid_Query_Optimizer`). It stays off and is removed separately, as already planned.

## How it works, in plain terms

1. **A grid misses its note and Mai builds the ID-only copy, as today.** If the switch is on and the database is one that passed testing, Mai marks the copy.
2. **WordPress builds the copy's SQL.** Mai records each part (join, where, group-by, DISTINCT, fields) twice: when WordPress hands it to the first plugin filter, and after the last plugin filter. It does the same for the finished statement.
3. **Mai checks the copy.** If any part changed between the two looks, or the filter is not exactly one category or tag `IN` filter, Mai steps aside.
4. **At the last moment before the statement goes to the database,** Mai swaps it for the `EXISTS` form. It does this only if the statement is still exactly the one WordPress finished building.
5. **The database returns the same IDs, fast.** Everything after that is today's code: the key check, the note, the kept posts.
6. **If the swapped statement fails,** Mai runs the copy again with today's statement, so the page still gets its grid. It also turns the rewrite off on that site for 24 hours and writes one line to the debug log.

The copy's own SQL text, as WordPress stores it on the query, never changes. So Mai's key check and WordPress's query cache keys work exactly as they do today.

## What ships

### 1. The swap

Today WordPress sends this for a "current category" grid copy (eurweb, whitespace trimmed):

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
WHERE 1=1 AND EXISTS ( SELECT 1 FROM eur_term_relationships WHERE eur_term_relationships.object_id = eur_posts.ID AND ( eur_term_relationships.term_taxonomy_id IN (1,40271,...) ) )
AND eur_posts.post_type = 'post' AND ((eur_posts.post_status = 'publish'))
ORDER BY eur_posts.post_date DESC, eur_posts.ID DESC
LIMIT 0, 32
```

Three exact text replacements on the finished statement, and nothing else:

- WordPress's tax join becomes empty.
- WordPress's tax condition is moved, character for character, inside `EXISTS ( SELECT 1 FROM {term_relationships} WHERE {term_relationships}.object_id = {posts}.ID AND <condition> )`.
- `GROUP BY {posts}.ID` becomes empty.

Each piece must appear exactly once in the statement, or Mai steps aside. After the swap, the term relationships table must appear only inside the `EXISTS`.

**Why this is the same filter.** A post passes today's statement when at least one of its category rows is in the list. The `GROUP BY` only removes the duplicate copies of a post the join made. `EXISTS` asks the same question once per post. The `ORDER BY` ends with the post ID, so the order has no ties, and `LIMIT` picks the same posts.

**Why it stays fast on MySQL.** The `EXISTS` sits directly in the top-level `AND` list, which MySQL 8.0.16+ needs to plan it like a join. A single tax clause guarantees that.

### 2. When Mai swaps

All of these must hold. Any one failing means today's statement goes out unchanged.

- **The switch is on** (`mai_query_cache_tax_exists`, below).
- **The rewrite is not turned off on this site** after a failure (section 4).
- **The database passed testing** (section 3).
- **The copy is Mai's ID-only copy.** Marked by a property `fetch_ids()` sets on the copy before it runs, never a query var, so a copy another plugin builds does not carry it.
- **The tax query is one clause:** operator `IN`, taxonomy `category` or `post_tag`, no nesting. Read from `$query->tax_query->queries`.
- **No search and no meta query.** `s` is empty, and the search part of the where is empty after the last `posts_search` callback.
- **Every part is as WordPress built it.** For join, where, group-by, DISTINCT and fields, the value after the last filter equals the value at the first filter. First looks are at `PHP_INT_MIN` on `posts_where`, `posts_join`, `posts_groupby`, `posts_distinct` and `posts_fields`. The last look is at `PHP_INT_MAX` on `posts_clauses_request`, after every clause filter (WC Memberships edits at `posts_clauses` 999).
- **A missing record means step aside.** list-category-posts, My Content Dash and The Blog Fixer remove other plugins' filters mid-request (`remove_all_filters`), so a first look can be missing.
- **The join and the where are exactly WordPress's tax SQL.** Mai builds the tax SQL again from a fresh `WP_Tax_Query` made from `$query->tax_query->queries`. The first-look join must equal that join exactly (so nothing else is joined). The first-look where must contain that tax condition exactly once. Group-by must be exactly `{posts}.ID`, DISTINCT empty, and fields exactly `{posts}.ID`.
  - A fresh object is needed because `WP_Tax_Query::get_sql()` never resets its alias list (`class-wp-tax-query.php:60`, `:419`), so a second call on core's object names the join differently. Checked on local eurweb: the fresh join equals core's, and its condition appears once in core's where.
- **The order has no ties.** The final `ORDER BY` ends with `{posts}.ID ASC` or `{posts}.ID DESC` (Mai's tiebreaker, `class-mai-grid.php:1100`).
- **There is a `LIMIT`.** Swiftype removes it on its search pages, and without one the speed gain is unmeasured.
- **The statement is unchanged at `posts_request`.** Recorded at `PHP_INT_MIN` and compared at `PHP_INT_MAX` (Revisionary rewrites it during logged-in revision previews).
- **The statement has no `%` placeholder escape.** WordPress strips those in its own `query` filter at priority 0 (`class-wpdb.php:2428`), so a statement with one would never match. Grid copies have none today.
- **The statement reaching the database is the one WordPress finished.** The swap happens in `$wpdb`'s `query` filter at `PHP_INT_MAX`, added by `fetch_ids()` around the copy and removed after it, and it only acts on an exact text match. A plugin that changed the statement in its own `query` callback makes it not match.

**Gotcha for the build.** `WP_Query` has a magic `__get()` (`class-wp-query.php:4117`), so `$query->prop['key'] = $value` on a property that is not set yet is silently dropped. Assign whole values: `$query->prop = $array`.

### 3. Which databases

Mai reads `$wpdb->db_server_info()` once per copy. It sends no statement.

- **MySQL from 8.0.16.** Live eurweb's 8.0.46 already picks the fast plan (section "What we found").
- **MariaDB from the lowest tested version that passes** (section "Measurements before release"), provided every tested version above it passes too. Otherwise MariaDB keeps today's statement.
- **Anything else keeps today's statement:** older MySQL, servers whose version string Mai cannot read, and anything unknown.

There is only a lowest version per database, not a list. A new version above it gets the rewrite without a Mai Engine update. Correctness does not depend on the version, only speed does, and every new major version gets checked by the same speed test.

MariaDB's version string can carry a `5.5.5-` prefix (`5.5.5-10.11.6-MariaDB`), so its version is read from the number right before `-MariaDB`. `$wpdb->db_version()` alone would read that as 5.5.5.

### 4. If the swapped statement fails

`fetch_ids()` checks right after the copy runs. The swapped statement failed when Mai swapped it, `$wpdb->last_error` is set, and `$wpdb->last_query` is the swapped text. Then Mai:

1. resets WordPress's query cache, as today's failure path does (`forget_failure()`), because WordPress stored the failed empty result under the copy's key
2. turns the rewrite off on this site for 24 hours, with a transient
3. writes one line to the debug log, when `WP_DEBUG_LOG` is on
4. runs the copy again without the swap, and carries on exactly as today

A failure that is really something else, such as a lost connection, also turns the rewrite off for a day. That costs only the speedup.

### 5. Changes to existing code

- `Mai_Query_Cache::fetch_ids()` (`class-mai-query-cache.php:942`): mark the copy, add and remove the `query` callback around it, then add the failure check above before its own checks. The key check (`:1006`) and the ID-only check (`:1000`) are unchanged, because the copy's request text is unchanged. After a swap, `$wpdb->last_query` is the swapped text, so the existing error check at `:969` does not match a failure. The new check comes first for that reason.
- `mai_register_query_cache()` (`lib/functions/query-cache.php:21`): register the first-look and last-look callbacks once, like the tiebreaker. Each returns at once unless the query carries the copy's mark.
- New class `Mai_Query_Cache_Exists` in `lib/classes/class-mai-query-cache-exists.php`, next to `Mai_Query_Cache_Queue`. It holds the switch, the database check, the records, the checks, and the swap.

### New filter

- `mai_query_cache_tax_exists` (bool, default `false`): turns the rewrite on for a site. The name follows the existing `mai_query_cache_*` filters. To turn it on:

```php
add_filter( 'mai_query_cache_tax_exists', '__return_true' );
```

## Tests

Integration tests, on a real database:

- **Same posts.** For each covered shape, the IDs from the swapped statement equal the IDs from today's statement:
  - one category with child categories, one tag, many terms (29, like eurweb's), a term with no posts
  - with `post__not_in`, with several post types, with `post_status` publish and private
  - `LIMIT` 2, 32 and 200, posts with the same date
- **The swap happens** when the switch is on and every check passes. This test fails if a WordPress update changes core's SQL so Mai stops swapping, which would otherwise go unnoticed.
- **Today's statement goes out unchanged** when the switch is off, and for each case below:
  - two clauses, `NOT IN`, `AND`, `EXISTS` operators, a custom taxonomy, a meta query, a search
  - a plugin changes the where, join, group-by, DISTINCT or fields at default priority
  - a plugin changes the where at `posts_clauses` 999
  - a plugin adds to the search part with `s` empty
  - a plugin changes the statement at `posts_request` or in its own `query` callback
  - the tiebreaker is removed, or there is no `LIMIT`
  - a first-look callback was removed mid-request
  - the statement has a `%` placeholder escape
- **The rebuild after the page swaps too** (`run_queue()`), and the copy's key still matches the grid's, so the note is stored.
- **Failure:** a `query` callback added after Mai's breaks the swapped statement. The grid still gets the right IDs, the 24-hour transient is set, one log line is written, and WordPress's query cache was reset.
- **Database check** (unit tests, plain strings): MySQL `8.0.15` off, `8.0.16` on, `8.0.46-0ubuntu0.22.04.4` on, Percona `8.4.11-11` on, `5.7.44` off, Aurora `8.0.mysql_aurora.3.04.0` off, and MariaDB strings with and without the `5.5.5-` prefix against the minimum the measurements set.

The integration suite runs on local MySQL 9.7, and in Docker on MySQL 8.0 and 8.4 and MariaDB 10.6, 10.11, 11.4 and 11.8, through `WP_TESTS_DB_HOST`.

## Measurements before release

On each database above, in Docker, with copies of local eurweb (85,000 posts), local larrybrownsports (145,000 posts) and one small local site:

- **Statements:** each site's real grid copy statements, captured on article and home page views, plus the 11 eurweb terms from 18 to 64,000 posts used in the earlier lab. Each at the grid's LIMIT and at LIMIT 2 and 32.
- **Same IDs** from both forms, every time.
- **Speed:** both forms alternating in one session, at least 10 runs each, medians, plus the plan from `EXPLAIN`.
- **The bar:** for every statement, the swapped median is no more than today's median plus 0.5 ms or 10%, whichever is larger. A database version that misses the bar on any statement keeps today's statement. That sets the MariaDB minimum, and confirms the MySQL one.
- **Whole pages:** cold article views on local eurweb with the switch on and off, following `task-14-rules.md`, to confirm the 1.5 s gain.

## Rollout

1. Ship in a beta with the switch off. No site changes.
2. Turn it on for live eurweb with a one-line mu-plugin. That is a write, so Mike runs the command.
3. Measure cold article views on live eurweb and watch its PHP error log for the failure line.
4. Then larrybrownsports. Turning it on for everyone is a later decision, after the beta.

No release, tag or push without Mike asking.

## What we found (2026-10-04)

- **Live eurweb picks the fast plan.** One read-only `EXPLAIN FORMAT=TREE` of the swapped statement on MySQL 8.0.46, through `mai-sites run`: a backward scan of the posts index on post type, status and date, a nested-loop semijoin into term relationships, stopping at the `LIMIT` of 32. The local copy ran the same plan in 0.09 ms. Output kept in `/tmp/t15-explain-live.out`.
- **The registry runs MySQL 8.0.45 or newer.** One site per server, all 22 servers: 19 on 8.0, 3 on 8.4. None runs MariaDB or MySQL 5.7.
- **WordPress as a whole is different.** wordpress.org's figures, which come from the database version every site sends when it checks for updates (`wp-includes/update.php:79-103`): about 60% MariaDB, 22% MySQL 8 or newer, 17% older MySQL. Mai Engine's 5,000 sites outside the registry probably sit somewhere between the two.
- **No plugin in the fleet would break the swap or quietly undo it.** On 38 local sites, 65 plugins and 3 themes hook the SQL filters, and every callback that can reach a grid copy was read. These change a grid copy's SQL in some settings, so the checks would make Mai step aside there:
  - wpseo-local, secondary-title, Swiftype and the horizon_west_happenings theme, on search pages
  - co-authors-plus, when a grid filters by author
  - Revisionary, on logged-in revision previews
  - WP Fusion in "Advanced" filter mode, and WC Memberships in "hide completely" mode
- **On real page views, every grid copy reached the database exactly as WordPress built it.** A temporary probe on the article and home page of local eurweb and local larrybrownsports (36 copies) saw no change to any part, the request or the statement. eurweb's six heavy article grids and ten home grids all have the covered shape.
- **Found along the way, not part of this work:**
  - list-category-posts strips Mai's ID tiebreaker for the rest of a request after each `[catlist]`. The copy's key check already catches that (its ORDER BY no longer matches the grid's), so the grid's own query answers.
  - The two earlier attempts: the June args-strip optimizer (off by default; it can show wrong posts with plugins that read the query args) and a temporary `posts_clauses` fix on live eurweb, which is gone.

## Risks

- **A plugin's `query` callback registered at `PHP_INT_MAX` after Mai's sees the swapped statement.** Harmless unless it looks for the join text. None in the fleet does.
- **A database drop-in that never applies the `query` filter.** Then Mai never swaps, and the site keeps today's speed. Query Monitor's drop-in applies it.
- **A WordPress update changes how core writes this SQL.** The checks stop matching and the site quietly keeps today's speed. The "swap happens" test catches it on the next test run.
- **A future MySQL or MariaDB version plans the `EXISTS` worse.** It would still show the right posts. The speed test runs on each new major version.
- **A brief database error during a swapped statement turns the rewrite off for a day.** It costs only the speedup on that site.
