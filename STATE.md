# State
Updated: 2026-09-30 by Claude

## Now
2.41.0 in progress (see CHANGES.md). Latest: column layouts read `display` from `--columns-display` (default `flex`), and the editor no longer zeroes margins on plain `.is-column` items. Both exist for Mai Gallery's masonry layout (mai-galleries 1.3.0).

## Next
- Release 2.41.0.

## Blocked / waiting on
Nothing known.

## Verify
- A `.has-columns` block still lays out as flex on the front end and in the editor.
- In the editor, `.is-column` items keep the margins they have on the front end. Nested `.wp-block` column wrappers still get `margin: 0`.
- `npx gulp build:main-css` and `build:editor-css` rebuild the CSS. `gulp styles` does not exist.

## Gotchas
- `mai-sites push` reads `.distignore`, not `.gitattributes` `export-ignore`. Dev files and `docs/` must be listed in `.distignore` to stay off servers. Files already on a server are not removed by a later push.
- The editor loads its column CSS after block editor styles, so a block overriding column rules at equal specificity loses in the editor only. Prefer custom properties like `--columns-display`.
