# TODO

- After 2.41.0-beta.5: test refreshing every grid after the page, not only Exclude grids, for speed and safety across ~5,000 sites. See `docs/ideas/2026-10-02-grid-cache-refresh-every-grid-after-page.md`.
- Before 2.41.0 final: check MySQL's `innodb_buffer_pool_size` (and MySQL or MariaDB) on the main hosts against their biggest sites' `wp_posts` and `wp_term_relationships`. On a small buffer pool, beta.5's ID-only lookup for big categories can pick a slower plan (spec Risks, decided 2026-10-04).
