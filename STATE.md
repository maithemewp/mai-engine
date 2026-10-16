# State
Updated: 2026-10-16 by Claude

## Now
Everything below went out in 2.41.0-beta.9. It was 14 commits after 2.41.0-beta.8 (`1fac2d345`), not pushed. Mike tested each piece in his browser on local sites; it is ready for beta.9 on his go.

- **Masonry image orientation** for Mai Post Grid, Mai Term Grid and content archives (not single content, via `mai_get_single_image_orientation_choices()`). Large size, Image Size hidden, no `--aspect-ratio`, and a note under the setting saying entries fill each column top to bottom. `mai_get_columns_atts()` adds `is-masonry` next to `has-columns` whenever `mai_is_masonry( $args )` is true, so grids, archives and Mai Gallery all get it from one place. Shared CSS: `assets/scss/layout/_masonry.scss`, in `main.min.css` and `editor.min.css`.
- **Gravity Forms defaults** (`lib/support/gravity-forms.php`, loaded only when `GFForms` exists): Orbital, colors from `mai_get_colors()`, radius and font sizes from config `extra`, then the active Mai theme's `assets/css/themes/<theme>.min.css` `:root`, then 4px / 16px / scale 1.25. `mai_gform_default_styles` filter. A form block's own settings win key by key. `gform_form_block_attributes` sets the same values as the block's editor defaults.
- **Text input border at 3:1**: `mai_get_input_border_color()` blends body into background until 3:1, output as `--input-border-color`, used only inside the `--input-border` fallback (`base/_form.scss`, search block). Themes with their own `--input-border` keep it. Boxed entries and other borders unchanged. Gravity Forms uses the same color.
- **Editor styles**: Mai's WYSIWYG stylesheet (admin-ajax `mai_classic_editor_styles`) now goes in only through `mce_css`, so the block editor no longer requests it from the site itself on every load. Buttons are styled inside `.mce-content-body`. Mai's own stylesheets in the WYSIWYG list carry the Mai version plus file date, because TinyMCE's own cache suffix only changes with WordPress.
- 2.41.0-beta.8 (2026-10-09) is the last release. beta.6 to beta.8 are on 18 sites via `mai-sites push`.

## Next
1. Release beta.9 when Mike says go (`npm run beta`, then `composer dump-autoload --no-dev`).
2. Mai Galleries `develop` (`c119ee3`, unreleased 1.3.0) takes Masonry, the class and the CSS from here, and forces Large only when `mai_is_masonry()` exists. Don't rename or remove it. Release Mai Engine first.
3. A child theme with its own `gform_default_styles` filter wins over Mai's. Such a theme can drop it once this ships; for its own corner radius it sets `'border-radius'` in config `extra`.
4. Fixed in `bin/styles.js`: built CSS gets the build time, so its `?ver=` changes on every build. Before, it kept the SCSS entry file's date and a rebuild could keep serving the old file.
5. Keep watching the 18 pushed sites' error logs (read-only via `mai-sites run`). MariaDB stays off for the grid optimizer (`TODO.md`). Before 2.41.0 final: buffer pool sizes, then `TODO.md`.

## Blocked / waiting on
- Mike's go for beta.9.
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (280 tests, 11 skipped libxml goldens), `composer test-integration -- --order-by=random` (694), `php vendor/bin/deployable-guard check`. All pass as of 2026-10-15.
- CSS: `npx gulp build:main-css build:editor-css build:blocks-css`. There is no `styles` task.
- Admin pages headless: `mai-sites login <site>.test --redirect=<path|post:ID/edit|term:ID/edit>` prints a one-time link with nobody at a keyboard and opens nothing; open it in headless Chrome. Script: `/tmp/ocr-scripts/admin-shot.mjs` (gone after reboot).
- `GridCacheAfterPageTest` fails 2 tests on MySQL 8.0.28, 8.0.16, MariaDB 11.4 and 11.8, also before this work.

## Gotchas
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
