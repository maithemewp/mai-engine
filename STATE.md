# State
Updated: 2026-11-19 by Claude (after the develop push)

## Now
`develop` is pushed (`ef272b2c0`, 2026-11-19). It holds the Tag Processor migration and the demo import work from `docs/plans/2026-11-19-demo-import-images.md`: content area images point at local attachments (nested blocks too), content areas keep escaped block attributes, and the vendored `proteusthemes/wp-content-importer-v2` is 2.1.4, which slashes post data itself. Mai's old `mai_setup_wizard_slash_data` filter is a deprecated no-op in `deprecated.php`. `CHANGES.md` has a `2.42.0 (unreleased)` section; Mike sets the version and runs the beta.

## Next
1. Strip core's legacy preset classes from the inlined `wp-block-library` CSS (`TODO.md`, design in `~/Themes/mai-theme/docs/specs/2026-10-20-mai-theme-v3.md`, "theme.json").
2. The uncommitted `lib/functions/images.php` change: `mai_add_logo_attributes()` falls back to the config logo width, so a site that never saved the setting stops getting `sizes="1px"`. Ask Mike whether to finish it (test, changelog, commit) before touching it.
3. Mike: close issues #678 and #679 (fixed on `develop`), delete the merged `demo-import-images` and `dom-to-tag-processor` branches on GitHub, run the 2.42.0 beta.
4. Issue #677 (entry image aspect ratio when the crop is missing) is open. Don't start it unless Mike asks.
5. Help Scout ticket #786985: reply once Mike approves the wording.
6. Then `TODO.md`. Its deployable-guard v1.1.0 item is done (v1.2.0 is installed; 52 of 54 `mai-*` plugins have it, all but mai-content-areas and mai-user-post).

## Blocked / waiting on
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (280, 11 skipped), `composer test-integration -- --order-by=random` (815). `GridCacheAfterPageTest` fails 2 tests on some MySQL/MariaDB versions regardless; it passed here.
- `composer test-integration -- --filter LocalizeBlocksTest` covers the path match, nested blocks and re-import.
- `composer test-integration -- --filter TemplatePartImportSlashTest` covers escaped block attributes surviving the single import. `SetupWizardSlashDataTest` and `SetupWizardWxrImporterTest` cover the vendored importer keeping escapes on insert and on the URL rewrite, and nothing in Mai slashing a second time.
- `deployable-guard` fails in this working tree because of the uncommitted with-dev `vendor/`. Check the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- Wizard scripts: `/tmp/tp-demo/pw/import.js` (full run) and `fail.js` (first content request only), plus `/tmp/tp-demo/check.php` for content area images (gone after reboot).

## Gotchas
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize` (the release script's shape), then run deployable-guard on it. Never in the main tree.
- The working tree carries Mike's uncommitted `TODO.md` line (the legacy preset item) and the `lib/functions/images.php` logo fix. Stage by explicit path only. The old with-dev `vendor/composer` files were discarded on 2026-11-19 (saved at `/tmp/mai-engine-local-vendor-composer.patch`). Never commit a with-dev autoloader: it takes every site down on deploy.
- The wizard caches demo files for an hour in `wp-content/mai-setup-wizard/<theme>/`. Delete that folder to force a fresh download.
- The local demo site blocks outgoing HTTP. Only Mike opens or closes it with `mai-sites isolate`.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in the namespaced test files.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`. Never mix `set_attribute( 'class', ... )` with `add_class()` on one tag.
- Run `wp` against local sites from `/tmp`, not from this repo.
- Never write a client site, server or local `.test` name into this repo. Use the stand-ins in `docs-local/site-key.md`. The commit hooks refuse registry names.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
