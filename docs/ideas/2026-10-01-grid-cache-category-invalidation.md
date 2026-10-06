# Grid cache: invalidate by category, not by post type

Status: not planned (2026-10-01). Sound after the review fixes below, but measured benefit is too small for the added complexity. Kept as a record in case the numbers change.

## The problem

Mai caches each post grid's list of post IDs. Every cached grid of a post type carries one version stamp for that post type. Any save that affects a published post changes the stamp (`Mai_Query_Cache::on_transition()` and `on_delete()`), so every post grid on the site goes stale at once.

On a site that publishes and edits all day, that means grids rebuild far more often than their content changes. A related-posts grid on a Football article goes stale when someone fixes a typo in a Basketball article. Visitors still get the old copy while one request rebuilds, so pages are not slow, but the rebuilds are wasted database work, and on the busiest sites that work is the expensive kind (category grids cost 160 ms to over a second of SQL on larrybrownsports and eurweb).

## The idea

A grid that filters by terms only depends on those terms. Give each term its own stamp. A grid filtered by terms is stamped with the stamps of the terms it names. A save changes only the stamps of the terms the saved post had before the save and has after it, plus each of those terms' ancestors.

A grid that does not filter by terms, or filters in a way this design does not fully understand, keeps depending on the whole post type stamp, exactly as today.

## Which grids get term stamps

Decide this in `pre_query`, from the query WordPress actually parsed (`$query->tax_query->queries` and `$query->meta_query->queries`), not from the `tax_query` query var. WordPress folds `cat`, `category__in`, `category__not_in`, `tag__not_in` and taxonomy query vars such as Polylang's `lang` into the parsed tax query (`class-wp-query.php:1301`, `1370`), and the `parse_tax_query` action can change it further. Deciding from the query var would miss those and stamp a grid with too few terms.

A grid gets term stamps only when every one of these is true. Anything else keeps the post type stamp.

1. The parsed tax query is not empty, and every clause uses the `IN` operator and resolves to real terms with `get_term()` in that clause's taxonomy. A term that does not resolve means fall back.
2. The clauses are joined by `AND` or `OR`. Either is fine, because the grid depends on every term it names (see "Football and Basketball" below).
3. No clause uses `NOT IN`, `EXISTS`, `NOT EXISTS` or a nested group.
4. The parsed meta query is empty, and `meta_key` is not set on its own (it limits results too).
5. None of these is set: `s`, any author var, `post__in`, `post_parent`, `post_parent__in`, `post_name__in`, `post_type` of `any`. `ignore_sticky_posts` must be true.
6. Its order is one of: date, modified date, title, menu order, ID. These only change when the post itself is saved. Orders that change without a save (comment count, a custom field such as views, random) keep the post type stamp, or are not cached at all as today.
7. The site has a persistent object cache. Without one, each stamp would be an autoloaded row in `wp_options`, and thousands of category stamps would bloat the table. Those sites keep the single post type stamp.

A grid's stamps are:

- one stamp per term it names, keyed by the term's `term_taxonomy_id`, not its term ID. The hooks below report `term_taxonomy_id`s (`taxonomy.php:3007`, fetched as `tt_ids` at `2871`). On fresh installs the two numbers usually match, which hides the bug in tests; on older or imported sites they differ.
- one stamp per taxonomy it filters on, for tree changes (see below).
- the post type stamp is not included. A term-stamped grid deliberately does not go stale on unrelated saves.

Stamps are keyed by term only, not by term and post type. A post that changes type therefore still rotates the right stamps. This can only over-invalidate.

## What changes a stamp

Changing a stamp means writing a new random token for it, as `bump()` does today (once its envelope bug is fixed in mai-cache 0.4.1).

**The invariant: every rotation happens after the database write it guards.** A rotation before the write lets a reader pick up the new stamp, compute the old result, and store it as fresh.

- **A post's terms change** (`set_object_terms`, fired after the new relationships are written; it reports old and new `tt_ids`): rotate the old terms of that taxonomy, plus every current term of the post in every taxonomy, plus the ancestors of all of them. Do not check the post's status here. Core and the block editor disagree on what status a cached `get_post()` returns at this point (`post.php:4644` caches the old post, the row updates at `4995`, terms are set at `5055`, the cache clears at `5156`; the REST controller sets terms after `wp_update_post()` returns, `class-wp-rest-posts-controller.php:980` then `1018`). Skip only auto-drafts and revisions. Rotating every current term also covers the block editor, which sets terms after `transition_post_status` has already fired.
- **Terms are removed without `set_object_terms`** (`wp_remove_object_terms`, `wp_delete_object_term_relationships`, deleting a tag, `wp post term remove`): hook `deleted_term_relationships` (`taxonomy.php:3103`), which fires after the rows are gone, and rotate the removed terms and their ancestors.
- **A post is published, unpublished, trashed, or a published or private post is edited** (`transition_post_status` with `publish` or `private` on either side): rotate the post's current terms and their ancestors, and the post type stamp. Today's guard ignores `private`, but editor grids include private posts, so `private` counts like `publish`.
- **A published or private post is deleted**: rotate on `deleted_post` (`post.php:3996`), after the post is gone. Its term rows are removed earlier in the same call, which `deleted_term_relationships` already covers. Do not use `before_delete_post`: it fires before the delete (`post.php:3887`).
- **The term tree changes**: rotate the taxonomy stamp on `edited_term` for hierarchical taxonomies (a parent may have moved), `delete_term` and `split_shared_term`. Do not hook `edited_term_taxonomy`: core fires it on every term count update (`taxonomy.php:4256`, `4286`), which happens on every save, and would quietly turn this back into a site-wide reset.
- **Deleting a term** calls `wp_set_object_terms()` once per post for categories (to reassign the default). Suppress per-post rotations inside `wp_delete_term` and rely on the single taxonomy rotation.
- **Every save that rotates term stamps also rotates the post type stamp.** Grids without term filters still need it.
- **The kill switch never stops rotations.** Term stamps rotate whenever a persistent object cache is present, even with the feature switched off. Otherwise switching it off and back on would revive entries whose stamps never rotated.

Also close two gaps that exist today:

- `hydrate()` drops cached posts whose status no longer matches. It should also drop posts whose post type is no longer in the query, because `set_post_type()` fires no hook (`post.php:2422`).
- The stamp-capturing rule that already makes stale-while-revalidate safe must survive the "write after the page is sent" work: the version is captured in `pre_query` before the SQL runs, never recomputed at write time.

## Football and Basketball

A grid that names Football and Basketball is stamped with both. Its stored version is both tokens joined, for example `football-v7.basketball-v3`, the way `Mai\Cache\Cache::version()` already joins several scopes. Every read rebuilds the pair from the current tokens. If either changed, the pair no longer matches and the entry is stale.

- A new Football post changes the Football token, so the grid goes stale. That is right for "Football or Basketball". For "Football and Basketball" it is an unneeded rebuild when the post is not also in Basketball, which is harmless.
- A new Hockey post changes neither, so the grid stays fresh.
- A post moved from Football and Basketball to Football only changes both, because its old terms included Basketball. It may need to leave an "and" grid, so that is required.

## What does not need a stamp change

The cache stores only which posts and in what order. Every view loads the posts themselves fresh. Editing a title, excerpt, image or body therefore shows immediately without changing any stamp. Only membership and order matter, and those only change on the events above.

## Safety nets that stay

- The entry's expiry time.
- `wp cache flush` and `wp mai flush`, which reset the whole grid group.
- The reset on every Mai update.

These cover anything that changes posts without going through WordPress hooks, such as a bulk importer writing straight to the database. That risk exists today and does not get worse.

## Cost

- **A save** writes 1 post type token plus one token per affected term and ancestor. A typical news post in two categories with one parent each is about 5 writes to the object cache. Today it is 1.
- **A read** gets one token per term the grid names, plus one per taxonomy, instead of one per post type. mai-cache reads tokens one at a time today (`Cache.php:271-301`). Add a batched read to mai-cache using `wp_cache_get_multiple()`. Above about 20 terms in one grid, fall back to the post type stamp.

## Kill switch

A filter, `mai_query_cache_term_scopes`, defaulting to true when a persistent object cache is present. Returning false puts every grid back on the post type stamp for reads. Rotations keep running either way (see above). A site that sees anything odd can switch it off without a release.

## Proving it is not fragile

One test that checks every combination, so a missed case fails a test instead of showing a wrong grid.

Setup that keeps the test honest:

- Force `wp_using_ext_object_cache( true )`. Rule 7 otherwise switches the feature off in the default test run, and every case passes without testing anything.
- Push the `wp_term_taxonomy` auto increment away from `wp_terms` before creating terms, so `term_id` and `term_taxonomy_id` differ.

Grid shapes: one category, a parent category with children, two categories with `OR`, two with `AND`, category plus tag, current terms on a single post, current term on an archive, a `pre_get_posts` that adds `category__not_in` (must fall back), a Polylang-style taxonomy query var (must be stamped with it or fall back), a `NOT IN` grid (must fall back), and an editor viewing private posts.

Changes, each through every path that can make it:

- publish into the grid's term, publish elsewhere, unpublish, trash, delete
- save through `wp_update_post()`, through the REST controller, and through `wp_publish_post()` (scheduled posts)
- move a post between terms with `wp_update_post()`, with `wp_set_object_terms()`, with append on, and remove a term with `wp_remove_object_terms()`
- delete a tag, delete a category (posts reassigned to the default), move a child term under a different parent
- `set_post_type()`
- change a post's date, edit only its title
- switch the kill switch off, make changes, switch it back on

For every pair, warm the grid, apply the change, then compare the cached answer with a fresh uncached query. If they differ, the entry must read as stale. If they match, staying fresh is allowed but not required.

Single-process tests cannot see races. Add a test that pins the invariant directly: each rotation hook runs after the write it guards (for example, at `deleted_post` the post row is already gone; at `set_object_terms` the new relationships already exist).

## Remaining risks

Ranked, after the rules above. None is new except the last.

1. A `posts_where` or `posts_join` filter that adds constraints Mai cannot see. Taxonomy-shaped ones are covered by deciding from the parsed tax query; raw SQL ones are not. Same risk as today's cache.
2. Database read replicas (HyperDB, LudicrousDB): a reader can stamp old data with a new stamp. Exists today.
3. Multisite saves after `switch_to_blog()`: mai-cache remembers group tokens per scope, not per site (`Cache.php:470`). Exists today, unverified; test it.
4. A term moved during an in-flight request can stamp old results as fresh for that one request.
5. Redis full with `noeviction`: a failed rotation goes unnoticed because `bump()`'s result is ignored. More stamps per save makes this more likely. Log failed rotations.

## Measured benefit (2026-10-01)

Production data, 17 to 30 September, read-only.

- **larrybrownsports:** about 27 rotations a day (publishes plus live edits), so a busy grid rebuilds about 28 times a day today. The related-posts grid on articles, which carries most grid renders, would rebuild about 13 times a day instead. That saves roughly 350 grid queries a day, about one every 4 minutes.
- **eurweb:** 99.5% of new posts are in "News", and related grids use every category on the article (6.2 on average, News included), so they would rotate exactly as often as today. Only 10 home page grids gain, about 130 queries a day.
- **Floor:** the 4-hour expiry already caps any grid at about 6 rebuilds a day, so the best possible gain is from 28 to 6.
- **Readers never wait on these rebuilds today:** the stale copy is served while one request rebuilds.

### Fleet survey (2026-10-01)

Read-only across the hosted fleet.

- 150 production sites run Mai Engine; 83 have a persistent object cache; 53 have both an object cache and term-filtered grids, but only 8 rotate 10 or more times a day (36 of the 53 rotate less than once a day).
- Five gain: tvnewscheck, larrybrownsports, allhiphop, lamag, ontapsportsnet. Related-grid rebuilds drop 30 to 45 percent and home page grid rebuilds roughly halve. Slow page loads avoided: about 85 to 920 a day per site, worth about 5 to 370 seconds of total visitor waiting per site per day.
- totalprosports does not qualify: 432 of its 468 term-filtered grids sort by views, and its home grids use a "Display NOT IN" filter. eurweb and ampers gain little (one dominant category).
- Sites without an object cache see no change. Sites with one pay 4 to 10 object cache writes per save.
- Home pages trade one big wait for several small ones: today the first visitor after a save rebuilds every home grid at once (2.1 s on tvnewscheck, 2.6 s on allhiphop); with per-term stamps the worst load drops to about 0.45 s but slow home loads happen 2 to 3 times as often.
- Much of what remains comes from many small cache entries expiring at 4 hours (lamag's related grid has 2,991 category combinations). A 24-hour lifetime for term-stamped grids roughly doubles the gain.
- Rebuilding after the page is sent would remove the visitor wait from every rebuild on every site, including the views-ordered grids this design cannot help, which makes this design a database-load saving only.

## Decision

Not building it now. The idea is sound once the review fixes are applied, but it needs about ten hooks, a strict ordering rule and a large test matrix to stay correct, and on the two busiest sites it saves a few hundred background queries a day that no reader waits on. Revisit if a site appears where term-filtered grids dominate renders and publishing is spread across many categories.

## Out of scope

- Sites without a persistent object cache.
- Author, `post__in` and custom field grids. They could get their own stamps later on the same pattern if measurement shows they matter.
- The other grid cache work under way (loading only shown posts, caching only slow grids, writing after the page is sent). This design is independent of all of it.
