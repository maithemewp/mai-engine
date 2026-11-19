# State
Updated: 2026-11-18 by Claude

## Now
Branch `dom-to-tag-processor` (from `develop` at `5d6534d68`) moves 18 of the 19 DOMDocument call sites to `WP_HTML_Tag_Processor`, per `docs/specs/2026-11-16-dom-to-tag-processor.md` and `docs/plans/2026-11-16-dom-to-tag-processor.md`. All 11 plan tasks are done and committed. Not pushed. The header scroll logo stays on DOMDocument on purpose, and `mai_get_dom_document()`, `mai_get_dom_html()` and `mai_get_dom_first_child()` stay for other plugins.

- **The corpus diff gate** (Task 9) rendered every post that can reach the converted code on all 35 local sites. It stopped on four shapes, and Mike decided them on 2026-11-18. The prev-next archive pagination never closed its wrap `div`, an old bug DOMDocument hid. It is fixed here (`51d3a501f`). Two malformed posts are accepted as the sites' to fix. URL percent-encoding and Gravity Forms boolean attributes are accepted, since they render the same. Counts are in the plan's Results, and post IDs are in `docs-local/2026-11-18-tag-processor-corpus.md`.
- **Profiling** (Task 10): the group block is 14 times faster per call and buttons are about 7 µs slower on DOM-path inputs. The page render shows no change (6.34 ms both), so the changelog makes no performance claim.
- **Changelog:** `CHANGES.md` has a `2.42.0 (unreleased)` section. Mike sets the version and date at release.

## Next
1. Mike reviews the branch, then it goes to `develop` and a beta. Mike does the push, merge and beta.
2. Issues #678 (prev-next pagination closer) and #679 (demo import cover block `wp-image-` class) are open; both are fixed on this branch.
3. Mike fixes the gallery caption typo on nonprofit-site-a post 9620 himself.
4. Issue #677 (entry image aspect ratio when the crop is missing) is open. Don't start it unless Mike asks.
5. Earlier queue: reply on Help Scout ticket #786985 once Mike approves the wording; roll deployable-guard v1.2.0 to the other `mai-*` plugins; then `TODO.md`.

## Blocked / waiting on
- Mike's review of this branch. The #679 cover fix is uncommitted until he has seen it in Tower.
- Hindsight is off on purpose (`"disabled": true` in `~/.agents/hindsight/coding-agent.json`).

## Verify
- `composer test-unit` (280, 11 skipped), `composer test-integration -- --order-by=random` (802 with the uncommitted #679 tests). `GridCacheAfterPageTest` fails 2 tests on some MySQL/MariaDB versions regardless; it passed here.
- `deployable-guard` fails in this working tree because of the uncommitted with-dev `vendor/`. Check the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- `rg -n "mai_get_dom_document|mai_get_dom_first_child" lib` shows only `lib/structure/header.php` and the definitions in `utilities.php`.
- Corpus and profiling scripts: `/tmp/tp-corpus/`, `/tmp/tp-profile/`, `/tmp/tp-floor/` (gone after reboot).

## Gotchas
- The working tree carries uncommitted `TODO.md`, `lib/functions/images.php` and `vendor/composer/*` changes that are not part of this work. The `vendor/` one is a with-dev autoloader, and committing it takes every site down on deploy. Stage by explicit path only.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in the namespaced test files.
- `get_attribute()` returns `null` or `true` as well as strings; guard every read with `is_string()`. Never mix `set_attribute( 'class', ... )` with `add_class()` on one tag.
- Run `wp` against local sites from `/tmp`, not from this repo. One site's plugin includes a relative `vendor/autoload.php` and loads this repo's autoloader otherwise.
- Never write a client site, server or local `.test` name into this repo. Use the stand-ins in `docs-local/site-key.md`. The commit hooks refuse registry names.
- Never `wp cache flush` on Redis sites. Use `mai_cache( 'css' )->flush()` (or `'grid'`) through `wp eval`.
- How Mai Engine releases, and the gotchas older than this branch, are in `git show 0f34a094d:STATE.md`.
