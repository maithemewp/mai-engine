# State
Updated: 2026-10-23 by Claude

## Now
2.41.0 is released (2026-10-23): GitHub release `2.41.0` on `master` at `e804a3b9b`, the same commit as `develop`. Issue #673 (WooCommerce 11+ shop showing products twice) closed with it. The changelog was trimmed to one short line per change, grouped Added, Changed, Fixed, and by tag inside each (untagged, Performance, WooCommerce, Security, Developers). Keep that shape.

- **How Mai Engine releases:** dated `Release X.Y.Z` commit (version header, changelog date, `npm run release`), fast-forward `master`, `gh release create X.Y.Z --target master --title "Mai Engine vX.Y.Z" --notes-file CHANGES.md`. Sites on `master` update only from a GitHub release, then a tag (`Vcs/GitHubApi.php:354`). The mai-bulk-update fleet flow (merge commits, annotated tags) is not used here, so `release-qa.sh postrelease` warns about the develop tip. Ignore it.
- **2.40.1 never shipped.** It had a release commit on `master` but no release or tag, so non-beta sites stayed on 2.40.0 until 2.41.0. Its two changes are in 2.41.0.
- **The auto-mode permission check refuses `npm run beta`, `npm run release`, tags and releases.** Hand Mike the commands.
- Checked before release: every BizBudding server's buffer pool (4 to 16 GB) holds all its sites' `wp_posts` and `wp_term_relationships`. The 128 MB servers host only small sites. No MariaDB. No Mai Engine errors in the beta sites' `debug.log` since beta.9, but 15 of 27 have no `debug.log`.
- The `beta` branch was fast-forwarded to `master` (2.41.0) the same day, so beta-channel sites get 2.41.0 too.

## Next
1. Mai Galleries: guard `mai_get_image_orientation_choices()` in `blocks/mai-gallery/block.php` with `function_exists()`, then release 1.3.0 (Masonry, needs Mai Engine 2.41.0) on Mike's go.
2. Roll deployable-guard v1.2.0 to the other `mai-*` plugins (bulk-update skill). Only Mai Engine and Mai Galleries have it.
3. A child theme with its own `gform_default_styles` filter wins over Mai's. Such a theme can drop it now. For its own corner radius it sets `'border-radius'` in config `extra`.
4. Then `TODO.md`. MariaDB stays off for the grid optimizer.

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (280 tests, 11 skipped libxml goldens), `composer test-integration -- --order-by=random` (699), `php vendor/bin/deployable-guard check`. All pass as of 2026-10-23.
- CSS: `npx gulp build:main-css build:editor-css build:blocks-css`. There is no `styles` task.
- Admin pages headless: `mai-sites login <site>.test --redirect=<path|post:ID/edit|term:ID/edit>` prints a one-time link with nobody at a keyboard and opens nothing; open it in headless Chrome. Script: `/tmp/ocr-scripts/admin-shot.mjs` (gone after reboot).
- `GridCacheAfterPageTest` fails 2 tests on MySQL 8.0.28, 8.0.16, MariaDB 11.4 and 11.8, also before this work.

## Gotchas
- `wp ... --skip-themes` fatals on sites running Mai Galleries: it calls `mai_get_image_orientation_choices()` unguarded, and Mai Engine loads no files without Genesis. Front end is fine. Leave `--skip-themes` off for reads on those sites.
- Never write a client site, server or local `.test` name into this repo (it is public on GitHub), not even in STATE.md or a commit message. Use stand-ins; put real detail in `docs-local/`, which every repo ignores (`~/.config/git/ignore`). The commit hooks refuse names from the mai-sites registry and `~/Herd` through `git config deployable-guard.extraHooks`. BizBudding's own demo domain is on that list too, so a commit adding it is refused.
- Gravity Forms reads `gform_default_styles` for the block's editor defaults once, early, before Mai's support file loads, and keeps it. Only `gform_form_block_attributes` (applied again when the editor config is built) reaches the editor.
- Gravity Forms scopes its values to `#gform_wrapper_<id>[data-form-index]` and adds `px`, so radius and font sizes are plain numbers in PHP. A child theme's `style.css` is not read.
- `--input-border-color` prints in `mai-inline-styles` after the child theme's `style.css`, so a child theme setting it on `:root` there loses. Use config `extra` or `body`.
- ACF fields in `mai_get_grid_display_fields()` need a `name`, even message fields, or `mai_get_editor_localized_data()` logs "Undefined array key".
- Masonry: never set `aspect-ratio` on the images, not even `auto` (lazy `sizes="auto"` images get `contain: size`; they rendered 1500px tall). Background, Left Full and Right Full positions give 4:3 tiles, cropped not stretched. Items flow down each column, then across.
- Mai Performance Enhancer double-encodes UTF-8 (`loadHTML()` without a charset). Mike is archiving it; it must not run anywhere.
- A local copy whose asset URLs still point at the live domain can't show local CSS changes. Check the stylesheet URLs before testing CSS on it.
- `npm run beta` leaves a dev autoloader. Run `composer dump-autoload --no-dev`, or every site crashes.
- `vendor/composer/installed.php` shows a modified commit reference after `composer` runs. Leave it out of feature commits.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- Grid optimizer and database-testing gotchas are in `git show 145a4939b:STATE.md`.
