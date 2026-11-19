# State
Updated: 2026-11-19 by Claude

## Now
Branch `demo-import-images`, cut from `dom-to-tag-processor` (the Tag Processor migration, pushed but not merged). It builds `docs/plans/2026-11-19-demo-import-images.md`, and all four tasks are committed locally, plus the follow-up fixes below. Not pushed.

- `mai_get_attachment_id_from_demo_url()` matches a demo image to a local attachment by its stored file path (`_wp_attached_file`), including sized and `-scaled` names.
- `mai_localize_blocks()` now walks nested blocks through `mai_localize_block()`. `mai_get_local_image_id()` reuses an earlier match for the same demo image, then tries the file path, the old slug match and a download.
- The setup wizard runs every content area through `mai_localize_blocks()` before trashing the old one, and a failed demo download now shows the URL and the real reason (request error or HTTP status).
- Both content area imports `wp_slash()` the content before `wp_insert_post()`, which unslashes. Without it, a `--`, `&` or quote in a block attribute lost its escape. All 40 demos today only carry `\/` in ACF block JSON, which is harmless, so no re-export is needed.
- `proteusthemes/wp-content-importer-v2` is now 2.1.4. Upstream 2.1.3 and 2.1.4 slash post data on insert and on the image URL rewrite, which had stripped every escape (the Side Hustle homepage's Mai Grid custom content came out as `u003cp...`; 14 pages across 13 demos were exposed). Mai's own `mai_setup_wizard_slash_data` filter is gone, because upstream now slashes right after that filter and keeping both would slash twice (the Help Scout #783681 symptom). The temporary `Mai_Setup_Wizard_Wxr_Importer` subclass is gone too.
- End to end on the local demo import site: `post-header`'s cover and `footer`'s nested image point at local attachments 111 and 339, with no new attachments. The failure message was checked with a temporary mu-plugin, because Mike kept the demo host allowed. Details are in the plan's Results.
- `CHANGES.md` 2.42.0 (unreleased): the demo import Fixed line now covers content area images.

## Next
1. Mike reviews `demo-import-images`. Then it joins the Tag Processor work for the 2.42.0 beta. Mike does the push, merge and beta.
2. Open from the Tag Processor branch: issues #678 (prev-next pagination closer) and #679 (cover block `wp-image-` class) are fixed there and still open on GitHub.
3. Issue #677 (entry image aspect ratio when the crop is missing) is open. Don't start it unless Mike asks.
4. Earlier queue: Help Scout ticket #786985 reply once Mike approves the wording; roll deployable-guard v1.2.0 to the other `mai-*` plugins; then `TODO.md`.

## Blocked / waiting on
- Mike's review of this branch and of `dom-to-tag-processor`.
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (280, 11 skipped), `composer test-integration -- --order-by=random` (815). `GridCacheAfterPageTest` fails 2 tests on some MySQL/MariaDB versions regardless; it passed here.
- `composer test-integration -- --filter LocalizeBlocksTest` covers the path match, nested blocks and re-import.
- `composer test-integration -- --filter TemplatePartImportSlashTest` covers escaped block attributes surviving the single import. `SetupWizardSlashDataTest` and `SetupWizardWxrImporterTest` cover the vendored importer keeping escapes on insert and on the URL rewrite, and nothing in Mai slashing a second time.
- `deployable-guard` fails in this working tree because of the uncommitted with-dev `vendor/`. Check the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- Wizard scripts: `/tmp/tp-demo/pw/import.js` (full run) and `fail.js` (first content request only), plus `/tmp/tp-demo/check.php` for content area images (gone after reboot).

## Gotchas
- Update vendored packages only in a clean worktree with `composer dump-autoload --no-dev --optimize` (the release script's shape), then run deployable-guard on it. Never in the main tree.
- The working tree carries uncommitted `TODO.md`, `lib/functions/images.php` and `vendor/composer/*` changes that are not part of this work. The `vendor/` one is a with-dev autoloader, and committing it takes every site down on deploy. Stage by explicit path only.
- The wizard caches demo files for an hour in `wp-content/mai-setup-wizard/<theme>/`. Delete that folder to force a fresh download.
- The local demo site blocks outgoing HTTP. Only Mike opens or closes it with `mai-sites isolate`.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in the namespaced test files.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`. Never mix `set_attribute( 'class', ... )` with `add_class()` on one tag.
- Run `wp` against local sites from `/tmp`, not from this repo.
- Never write a client site, server or local `.test` name into this repo. Use the stand-ins in `docs-local/site-key.md`. The commit hooks refuse registry names.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and older gotchas, are in `git show 0f34a094d:STATE.md`.
