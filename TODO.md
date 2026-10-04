# TODO

- After 2.41.0-beta.5: test refreshing every grid after the page, not only Exclude grids, for speed and safety across ~5,000 sites. See `docs/ideas/2026-10-02-grid-cache-refresh-every-grid-after-page.md`.
- Before 2.41.0 final: check MySQL's `innodb_buffer_pool_size` (and MySQL or MariaDB) on the main hosts against their biggest sites' `wp_posts` and `wp_term_relationships`. On a small buffer pool, beta.5's ID-only lookup for big categories can pick a slower plan (spec Risks, decided 2026-10-04).
- Update the local deployable-guard to v1.1.0 (`composer update bizbudding/deployable-guard`). The local v1.0.0 does not check that `vendor/composer/installed.php` is tracked, so only CI catches it, after `npm run beta` has pushed.
- mai-package-loader: `init.php:660-664` compares Composer's normalized `0.1.0.0` with `0.1.0`, so the loader counts its own copy as newer. Harmless until a release ships `takeover.php`. Fix in the loader repo.
- Test making "current category" grid queries cheap on big sites: `EXISTS` instead of a join with `GROUP BY` (250 ms to under 1 ms in the lab on eurweb), and one fetch for grids that differ only in post count. Only if it is not fragile with other plugins' SQL filters. See `docs/ideas/2026-10-04-grid-related-posts-query-cost.md`.
