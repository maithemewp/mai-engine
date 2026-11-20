# State
Updated: 2026-11-20 by Claude (entry image shape #677 built on develop, final review pending)

## Now
`develop` on GitHub is at `ef272b2c0`. Local commits on top, not pushed: legacy preset CSS (`0ce48ba62`), logo width (`c99085f7b`), the #677 spec and plan, and the three #677 build commits ending with "Entry images: keep the chosen shape when the crop is missing". `CHANGES.md` has a `2.42.0 (unreleased)` section; Mike sets the version and runs the beta.
- **Issue #677, entry images keeping their chosen shape:** built on local `develop`, not pushed. `mai_get_entry_aspect_ratio()` (`lib/functions/images.php`) decides the forced ratio; `Mai_Entry::get_image()` gives a shaped image `has-aspect-ratio` and its own inline `--aspect-ratio`; `.entry-image:where(.has-aspect-ratio)` in `_entry-image.scss` shapes only those images. The old left-full and right-full image rule in `_entry.scss` is gone. Browser results for all three engines are in the plan's Results (`docs/plans/2026-11-20-entry-image-shape.md`).
- **Legacy preset CSS** and **logo width** are done (see `docs/plans/2026-11-19-legacy-preset-css.md`).

## Next
1. Final whole-branch review of the #677 work, pending from the controller.
2. Then walk `TODO.md` with Mike, one item at a time.
3. Mike: push `develop` (needs his yes), run the 2.42.0 beta.
4. Help Scout ticket #786985: reply once Mike approves the wording.

## GitHub batch, on hold until Mike names the time
GitHub actions notify the team, so nothing here fires during the workday or without Mike's say-so.
- Close #678 (prev-next pagination wrapper) as completed, comment: "Fixed in 51d3a501f on develop. Ships in 2.42.0."
- Close #679 (cover block image ID class) as completed, comment: "Fixed in 11dee0a5b on develop. Ships in 2.42.0."
- Delete remote branches `demo-import-images` and `dom-to-tag-processor` (both fully merged into `origin/develop`), then `git branch -d` the local copies.
- Close #677 (entry images keeping their chosen shape) as completed, comment: "Fixed on develop in the 'Entry images: keep the chosen shape when the crop is missing' commit. Ships in 2.42.0."

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (299, 11 skipped), `composer test-integration -- --order-by=random` (823).
- `--filter EntryImageShapeTest` (unit and integration) covers #677's helper and markup. The CSS is checked in a headless browser: `/tmp/entry-shape/check.js` runs every case before and after in Chromium, Firefox and WebKit (scratch, gone after reboot; the case list is in the plan).
- `--filter LegacyBlockCssTest` and `--filter LogoAttributesTest` cover the other unpushed work. The legacy CSS unit fixtures are copies of WP 7.0.2's `common.css` and `common.min.css`; copy them in again when the test WordPress updates.
- Deployable check on the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.

## Gotchas
- Never give `.entry-image` a global `aspect-ratio`, not even `auto`. It replaces the ratio a lazy `sizes="auto"` image takes from its attributes and the image collapses. Only images marked `has-aspect-ratio` get one.
- Never run two integration runs at once. A targeted run reinstalls the shared test DB, and the other run then fails with errors that have nothing to do with the code.
- `phpcs.xml.dist` errors out (`minimum_supported_version` does not exist on `WordPress.WP.DeprecatedFunctions`). `php vendor/bin/phpcs --standard=WordPress <file>` works but flags the repo's short arrays everywhere.
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize`, then run deployable-guard on it. Never in the main tree. Never commit a with-dev autoloader: it takes every site down on deploy.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in the namespaced test files. Tests that need `lib/functions/images.php` require it in a separate process, since it adds global image filters.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`. Never mix `set_attribute( 'class', ... )` with `add_class()` on one tag.
- The `nonprofit-site-a` local site has `SCRIPT_DEBUG` on, so it inlines unminified core CSS.
- Run `wp` against local sites from `/tmp`, not from this repo.
- Never write a client site, server or local `.test` name into this repo. Use the stand-ins in `docs-local/site-key.md`. The commit hooks refuse registry names.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
