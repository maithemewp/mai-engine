# State
Updated: 2026-10-12 by Claude

## Now
- **Masonry image orientation is committed on local `develop`, not pushed or released.** Mai Post Grid, Mai Term Grid and content archives offer Masonry (single content does not, via `mai_get_single_image_orientation_choices()`). It uses the Large size, hides Image Size (that field only shows for Custom), puts `is-masonry` on `.entries-wrap`, and sets no `--aspect-ratio`. Shared CSS is `assets/scss/layout/_masonry.scss`, in both `main.min.css` and `editor.min.css`.
- Tested headless on morgastudio.test at 1440px and 390px: every masonry image box matched its own width/height ratio in the post grid, term grid, `/products/` archive and the Mai Gallery on `/art/commissioned-illustrations/`, also with Mai Galleries' own masonry rules deleted. Landscape grids unchanged. The editor preview was not checked.
- 2.41.0-beta.8 (2026-10-09) is the last release: the `mai_metabox_post_types` filter. beta.7 (upgrade guard) and beta.6 (grid taxonomy optimizer, spec `docs/specs/2026-10-04-grid-taxonomy-query-rewrite.md`) are on 18 sites via `mai-sites push`.

## Next
1. Mai Galleries `develop` (`c119ee3`, unreleased 1.3.0) takes the Masonry choice, the `is-masonry` class (from `mai_get_columns_atts()`) and the CSS from here. It forces the Large size only when `mai_is_masonry()` exists. Don't rename or remove that function. Release Mai Engine with masonry before or with Mai Galleries 1.3.0.
2. The morgastudio theme (`ec0cf40`, local) now uses Masonry from config and dropped its own workaround, so its product archives need a Mai Engine with masonry on that site before the theme goes live. A note under Image Orientation says entries fill each column top to bottom (`8d9e31f13`).
3. Keep watching the 18 pushed sites' error logs for `Mai Engine:` lines and fatals (read-only via `mai-sites run`, logs in `../logs/error.log`).
4. MariaDB stays off for the grid optimizer until the hardened replay runs on MariaDB 10.6, 10.11, 11.4 and 11.8 (`TODO.md`).
5. Before 2.41.0 final: check the buffer pool size on the main hosts, then the rest of `TODO.md`.

## Blocked / waiting on
- Mike's go to push or release the masonry work.
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`). Turn it back on only when Mike says.

## Verify
- `composer test-unit` (265 tests, 11 skipped libxml goldens; `MasonryTest` covers masonry), `composer test-integration -- --order-by=random` (694 tests), `php vendor/bin/deployable-guard check`. All pass as of 2026-10-12.
- CSS: `npx gulp build:main-css build:editor-css`. There is no `styles` task.
- `GridCacheAfterPageTest` fails 2 tests on MySQL 8.0.28, 8.0.16, MariaDB 11.4 and 11.8, also before this work. Not a regression.

## Gotchas
- Masonry: never set `aspect-ratio` on the images, not even `auto`. Lazy images with `sizes="auto"` get `contain: size` from Chrome, so their shape comes only from the width/height attributes; `aspect-ratio: auto` made them 1500px tall.
- Masonry with Background, Left Full or Right Full positions gives 4:3 tiles (the entry rules fall back to `4/3`), with images cropped by `object-fit: cover`, not stretched.
- Masonry items flow down each column, then across, so newest-first reads top to bottom per column.
- `:where(.has-columns.is-masonry) > * { display: block }` has no specificity on purpose, so entries keep `display: flex` (left/right image positions need it).
- `WP_Query` has a magic `__get()`: `$query->prop['k'] = $v` on an unset property is silently dropped.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'grid' )->flush()` through `wp eval`.
- `npm run beta` leaves a dev autoloader. Run `composer dump-autoload --no-dev`, or every site crashes.
- `vendor/composer/installed.php` shows a modified commit reference after `composer` runs. Leave it out of feature commits.
- Grid optimizer and database-testing gotchas (Docker, MySQL plan flips, replay bar) are in the previous state: `git show 145a4939b:STATE.md`.
