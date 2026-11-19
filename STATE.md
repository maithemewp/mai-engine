# State
Updated: 2026-11-19 by Claude (after the legacy preset CSS and logo width commits)

## Now
`develop` on GitHub is at `ef272b2c0`. Three local commits sit on top, not pushed: `17a3f6cce` (state), `0ce48ba62` (legacy preset CSS) and `c99085f7b` (logo width). `CHANGES.md` has a `2.42.0 (unreleased)` section; Mike sets the version and runs the beta.
- **Legacy preset CSS:** `mai_remove_legacy_block_css()` on `wp_head` at 2 (`lib/functions/enqueue.php`) strips core's 15 old gradient, gray and font size rules from the inlined `block-library/common.css`. Only core's copy at `extra['after'][0]` is touched. Plan and measurements are in `docs/plans/2026-11-19-legacy-preset-css.md` (7,411 to 5,944 inline bytes unminified, about 1.1 KB with the min file).
- **Logo width:** `mai_add_logo_attributes()` falls back to `mai_get_config( 'settings' )['logo']['width']`, so a site that never saved the setting stops getting `sizes` of 1px.
- The working tree is clean. Mike's `TODO.md` line about the legacy CSS is gone because the work is done, which leaves `TODO.md` matching the commit.

## Next
1. Mike: push `develop` (needs his yes).
2. Mike: close issues #678 and #679 (fixed on `develop`), delete the merged `demo-import-images` and `dom-to-tag-processor` branches on GitHub, run the 2.42.0 beta.
3. Issue #677 (entry image aspect ratio when the crop is missing) is open. Don't start it unless Mike asks.
4. Help Scout ticket #786985: reply once Mike approves the wording.
5. Then `TODO.md`.

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (289, 11 skipped), `composer test-integration -- --order-by=random` (821).
- `--filter LegacyBlockCssTest` (unit and integration) and `--filter LogoAttributesTest` cover today's work. The unit fixtures are copies of WP 7.0.2's `common.css` and `common.min.css`; copy them in again when the test WordPress updates.
- Deployable check on the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- Inline CSS bytes: `/tmp/legacy-css/measure.php <saved homepage html>` (gone after reboot).

## Gotchas
- Never run two integration runs at once. A targeted run reinstalls the shared test DB, and the other run then fails with errors that have nothing to do with the code (seen 2026-11-19 in `GridKeptOnlyTest`; the same seed replayed green). One earlier random-order run had a single failure that was not captured; 20+ runs since were green.
- `phpcs.xml.dist` errors out (`minimum_supported_version` does not exist on `WordPress.WP.DeprecatedFunctions`). `php vendor/bin/phpcs --standard=WordPress <file>` works but flags the repo's short arrays everywhere.
- The `nonprofit-site-a` local site has `SCRIPT_DEBUG` on, so it inlines unminified core CSS.
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize`, then run deployable-guard on it. Never in the main tree. Never commit a with-dev autoloader: it takes every site down on deploy.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in the namespaced test files. Tests that need `lib/functions/images.php` require it in a separate process, since it adds global image filters.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`. Never mix `set_attribute( 'class', ... )` with `add_class()` on one tag.
- Run `wp` against local sites from `/tmp`, not from this repo.
- Never write a client site, server or local `.test` name into this repo. Use the stand-ins in `docs-local/site-key.md`. The commit hooks refuse registry names.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
