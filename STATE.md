# State
Updated: 2026-11-20 by Claude (custom image width and CSS browser target)

## Now
`develop` on GitHub is at `ef272b2c0`. Everything since is local and not pushed: legacy preset CSS, logo width, issue #677 (entry images keep their chosen shape), the custom image width, and the CSS browser target. `CHANGES.md` has a `2.42.0 (unreleased)` section; Mike sets the version and runs the beta.
- **#677:** `mai_get_entry_aspect_ratio()` decides the forced ratio, `Mai_Entry::get_image()` marks the image with `has-aspect-ratio` and its own `--aspect-ratio`, and `.entry-image:where(.has-aspect-ratio)` shapes only those images. Plan and three-engine results: `docs/plans/2026-11-20-entry-image-shape.md`.
- **Walked with Mike on 2026-11-20 against a local test page:** uncropped sizes in Left Full and Right Full keep the photo's own shape. Center crops to the chosen orientation. A custom size on the left or right now uses Image Width (¼, ⅓, ½) but never shows wider than the size: `min(<size>px,<share>)` in `mai_do_entries_open()`. Plan: `docs/plans/2026-11-20-custom-image-width.md`.
- **CSS browser target:** `package.json` targets `baseline widely available`. Every bundle was rebuilt and checked: only dropped prefixes and equal values written shorter.
- The test page lives on the local demo-import site, page "Entry Image Shape Test", built by `/tmp/entry-shape-page/setup.php` (scratch, gone after reboot).

## Next
1. Mike reviews the branch locally.
2. Mike pushes `develop` and runs the 2.42.0 beta, both only on his yes.
3. Then walk `TODO.md` with Mike, one item at a time.
4. Help Scout ticket #786985: reply once Mike approves the wording.

## GitHub batch, on hold until Mike names the time
GitHub actions notify the team, so nothing here fires during the workday or without Mike's say-so.
- Close #678 (prev-next pagination wrapper) as completed, comment: "Fixed in 51d3a501f on develop. Ships in 2.42.0."
- Close #679 (cover block image ID class) as completed, comment: "Fixed in 11dee0a5b on develop. Ships in 2.42.0."
- Delete remote branches `demo-import-images` and `dom-to-tag-processor` (both fully merged into `origin/develop`), then `git branch -d` the local copies.
- Close #677 (entry images keeping their chosen shape) as completed, comment: "Fixed in bb9659638 on develop. Ships in 2.42.0."

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (303, 11 skipped), `composer test-integration -- --order-by=random` (823).
- `--filter EntryImageShapeTest` (unit and integration) and `--filter EntryImageWidthTest` cover today's PHP.
- A clean tree must rebuild byte for byte: `npx gulp build:css` and `npx gulp build:js`, then `git status` shows nothing.
- Deployable check on the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.

## Gotchas
- Never give `.entry-image` a global `aspect-ratio`, not even `auto`. It replaces the ratio a lazy `sizes="auto"` image takes from its attributes and the image collapses.
- The browser target also drives cssnano and Parcel, not only autoprefixer, so a target change touches `blocks.js` too. `package-lock.json` is ignored, so pin tool versions in `package.json`.
- In `CHANGES.md`, tagged lines (`[Developers]`, `[Security]`) sit together inside each Added, Changed or Fixed group.
- Never run two integration runs at once. A targeted run reinstalls the shared test DB.
- `phpcs.xml.dist` errors out (`minimum_supported_version`). `php vendor/bin/phpcs --standard=WordPress <file>` works but flags short arrays everywhere.
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize`, then run deployable-guard. Never commit a with-dev autoloader: it takes every site down on deploy.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`. Tests that need `lib/functions/images.php` require it in a separate process.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`.
- Run `wp` against local sites from `/tmp`. Never write a client site, server or local `.test` name into this repo (stand-ins in `docs-local/site-key.md`).
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
