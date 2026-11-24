# State
Updated: 2026-11-24 by Claude (stale v1 header widget areas)

## Now
**2.42.0-beta.1 is out:** `develop` and `beta` on GitHub are both at `db7b85214` (pushed 2026-11-20 with Mike's yes). It holds legacy preset CSS, logo width, issue #677 (entry images keep their chosen shape), the custom image width, the CSS browser target, ACF Pro 6.8.12 and plugin update checker 6.0 (namespace `v6`, checked on a local site against GitHub for master and beta).
- **#677:** `mai_get_entry_aspect_ratio()` decides the forced ratio, `Mai_Entry::get_image()` marks the image with `has-aspect-ratio` and its own `--aspect-ratio`, and `.entry-image:where(.has-aspect-ratio)` shapes only those images. Plan and three-engine results: `docs/plans/2026-11-20-entry-image-shape.md`.
- **Walked with Mike on 2026-11-20 against a local test page:** uncropped sizes in Left Full and Right Full keep the photo's own shape. Center crops to the chosen orientation. A custom size on the left or right now uses Image Width (¼, ⅓, ½) but never shows wider than the size: `min(<size>px,<share>)` in `mai_do_entries_open()`. Plan: `docs/plans/2026-11-20-custom-image-width.md`.
- **CSS browser target:** `package.json` targets `baseline widely available`. Every bundle was rebuilt and checked: only dropped prefixes and equal values written shorter.
- The test page lives on the local demo-import site, page "Entry Image Shape Test", built by `/tmp/entry-shape-page/setup.php` (scratch, gone after reboot).

- **On `develop`, not pushed (2026-11-24):** `mai_has_widget_area()` = registered + `is_active_sidebar()`. Header Left, Header Right and the `has-before-header` class use it, so widgets saved to v1 areas Mai no longer registers stop opening an empty header section that pushes the logo off center. Found on a v1 upgrade where the Widgets screen showed nothing to clear. Test: `StaleWidgetAreaTest`.
- **Also on `develop`:** `mai_upgrade_2_42_0_widget_areas()` moves widgets from the nine v1 area IDs to Inactive Widgets when the area isn't registered (writes only when it changes something; skips itself if `mai_get_deprecated_widget_areas()` is missing, like the font flush). The v1 fallback check now names `before-header` outright: the old loop broke after its first item, and fixing it would show years-hidden v1 widgets on ~5k sites. Tests: `DeprecatedWidgetAreasUpgradeTest`, `UpgradeWaitsForCodeTest`. Checked on the local demo-import site, then its options restored byte for byte.

## Next
1. On Mike's yes: bump to 2.42.0-beta.2 and run `npm run beta` (pushes `develop` and `beta`).
2. Beta soak on beta-tester sites. Then promote 2.42.0 to `master` on Mike's yes; Mike publishes the GitHub Release himself.
2. Then walk `TODO.md` with Mike, one item at a time.

## GitHub batch, on hold until Mike names the time
GitHub actions notify the team, so nothing here fires during the workday or without Mike's say-so.
- Issues #677, #678 and #679 were closed and the merged branches `demo-import-images` and `dom-to-tag-processor` deleted on 2026-11-20, on Mike's say-so. Nothing is queued.

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (307, 11 skipped), `composer test-integration -- --order-by=random` (831).
- `--filter EntryImageShapeTest` (unit and integration) and `--filter EntryImageWidthTest` cover today's PHP.
- A clean tree must rebuild byte for byte: `npx gulp build:css` and `npx gulp build:js`, then `git status` shows nothing.
- Deployable check on the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.

## Gotchas
- Never give `.entry-image` a global `aspect-ratio`, not even `auto`. It replaces the ratio a lazy `sizes="auto"` image takes from its attributes and the image collapses.
- The browser target also drives cssnano and Parcel, not only autoprefixer, so a target change touches `blocks.js` too. `package-lock.json` is ignored, so pin tool versions in `package.json`.
- In `CHANGES.md`, tagged lines (`[Developers]`, `[Security]`) sit together inside each Added, Changed or Fixed group.
- Never run two integration runs at once. A targeted run reinstalls the shared test DB.
- `phpcs.xml.dist` errors out (`minimum_supported_version`). `php vendor/bin/phpcs --standard=WordPress <file>` works but flags short arrays everywhere.
- The main tree's own `composer.lock` (ignored by git) is stale: it still pins PUC 5.7 and the importer 2.1.2, so the last step of `npm run beta` (`composer install`) stops with a lock warning. That left the tree clean on 2026-11-20. Refresh it in a worktree, never with `composer update` in the main tree.
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize`, then run deployable-guard. Never commit a with-dev autoloader: it takes every site down on deploy.
- The integration harness never loads `lib/functions/deprecated.php`, and `SetupWizardSlashDataTest` asserts its functions are absent. Tests that need it require it in a separate process.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`. Tests that need `lib/functions/images.php` require it in a separate process.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`.
- Run `wp` against local sites from `/tmp`. Never write a client site, server or local `.test` name into this repo (stand-ins in `docs-local/site-key.md`).
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
