# Demo import images Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Images in imported demo content areas end up on the customer's site, pointing at the attachments the content import already brought in, instead of loading from `demo-site-a.example`.

**Architecture:** `mai_localize_blocks()` (`lib/functions/templates.php`) learns to walk nested blocks and to match a demo image to a local attachment by its file path. The setup wizard's content area import runs every content area through it, and the wizard's file download reports why a fetch failed. All of this runs only during an import, never on a page view.

**Tech Stack:** PHP 8.1 floor, WordPress 6.4 floor, `WP_HTML_Tag_Processor`, `attachment_url_to_postid()`, PHPUnit via `tests/composer.json` (WordPress 7.x), a headless Playwright run of the wizard on a local Herd site.

**Spec:** none. This came out of an end-to-end test of the demo importer on 2026-11-19, and the findings are below. Mike asked for a short plan with no spec.

## What the test found

On a blank local site running the `dom-to-tag-processor` branch with the Side Hustle "Influencer" demo, the wizard imported content, content areas and Customizer settings, but both content area images still load from `demo-site-a.example`:

1. **The wizard never localizes content areas.** `Mai_Setup_Wizard_Importer::import_template_parts()` (`lib/classes/class-mai-setup-wizard-importer.php:168`) inserts each content area as the demo has it. `mai_localize_blocks()` only runs from the single "Import" link on the Content Areas screen (`mai_import_template_part()`, `templates.php:465`).
2. **`mai_localize_blocks()` only sees top-level blocks.** Side Hustle's footer image is three levels deep (group, columns, column), so even the single import skips it.
3. **The local match is by attachment slug and misses real files.** `mai_get_existing_attachment_from_filename()` compares the URL's file name to `post_name`. The content import brought in `2024/02/milad-fakurian-GAnCCU7mqIU-unsplash-scaled-1.jpg` (slug `milad-fakurian-ganccu7mqiu-unsplash-scaled`) and `2024/03/Asset-2@4x-e1711650607873.png` (slug `asset-24x`). Neither matches, so the importer would download duplicates. The stored file path, `_wp_attached_file`, matches both exactly.
4. **A failed download hides the reason.** `Mai_Setup_Wizard_Importer::download_file()` never checks `is_wp_error()` on `wp_remote_get()`, so a blocked request shows "response body could not be retrieved".

Checked and not a bug: the single import refuses `post-header` because it is not one of Mai's template part locations (`mai_get_config( 'template-parts' )`), and the Content Areas screen only offers those.

## Global Constraints

- WordPress 6.4 and PHP 8.1 floors (plugin header). Existing files stay procedural, unnamespaced, WordPress coding standards. No `declare(strict_types=1)` in existing files.
- New functions are marked `@access private` and `@since 2.42.0` (2.42.0 is provisional; Mike sets the version at release).
- `get_attribute()` returns `null` or `true` as well as strings. Guard every read with `is_string()`.
- Keep the single pass over URLs and `wp-image-` classes at the end of `mai_localize_blocks()` (`preg_replace_callback`), so an ID rewritten to a new ID that is also another image's old ID is not rewritten again.
- **Never stage `vendor/`, `TODO.md` or `lib/functions/images.php`.** The working tree carries unrelated uncommitted changes to them, and the `vendor/composer` one is a with-dev autoloader that takes every site down on deploy. Stage by explicit path only. Never `composer dump-autoload` in this tree.
- `deployable-guard` fails in the working tree because of that `vendor/`. Check the committed tree: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php` behind `function_exists`, never in the namespaced test files. `phpunit.xml.dist` sets `failOnWarning`.
- Tests never reach the network: every test that could hit `media_sideload_image()` adds a `pre_http_request` filter that fails the test (see `LocalizeBlocksTest`).
- Never write a real client site name in the repo or a commit message. The commit hooks refuse them.
- No em-dashes in commit messages, changelog lines or code comments. No shell variables in Bash commands.
- No push, tag, beta, release or GitHub activity. Commit locally; Mike reviews the branch before anything is pushed.
- Run `wp` against local sites from `/tmp`, not from the repo directory.
- Tests: `composer test-integration -- --filter LocalizeBlocksTest`; full suites `composer test-unit` and `composer test-integration -- --order-by=random`.

## Review Focus

1. **A sized URL whose original upload was scaled** (`photo-300x200.jpg` on the demo, `photo-scaled.jpg` stored locally). Expect the scaled attachment to match. Covered in Task 1.
2. **The same demo image used twice in one content area.** Expect one attachment, both blocks rewritten, no second download. Covered in Task 2.
3. **A content area imported twice** (wizard, then the single import). Expect the second run to leave the already-local URLs alone and download nothing. Covered in Task 2.
4. **An image URL not under `/uploads/`** (a CDN or another host). Expect no path match, the slug and download fallbacks as today, and no warning. Covered in Task 1.
5. **A demo fetch that fails** (blocked host, 404). Expect the wizard to show the URL and the real reason, and to stop. Covered in Task 3 and the end-to-end check.

---

### Task 1: Branch, and match demo images to local attachments by file path

**Files:**
- Modify: `lib/functions/templates.php` (new function next to `mai_get_existing_attachment_from_filename()`)
- Test: `tests/phpunit/integration/LocalizeBlocksTest.php`

**Interfaces:**
- Produces: `mai_get_attachment_id_from_demo_url( string $url ): int` in `lib/functions/templates.php`, `@access private`. Returns the local attachment ID whose `_wp_attached_file` matches the demo URL's upload path, or `0`.

- [x] **Step 1: Branch**

Run: `git switch -c demo-import-images dom-to-tag-processor`. Confirm `git status --short` shows only the unrelated `TODO.md`, `lib/functions/images.php` and `vendor/composer/*` changes.

- [x] **Step 2: Write the failing tests**

Create attachments with the factory and set `_wp_attached_file` to the paths below. Assert:
- `test_matches_exact_upload_path`: `https://demo.example/site/wp-content/uploads/sites/83/2024/02/photo-scaled-1.jpg` returns the attachment stored at `2024/02/photo-scaled-1.jpg`.
- `test_matches_without_multisite_prefix`: `.../wp-content/uploads/2024/02/photo.jpg` returns the one stored at `2024/02/photo.jpg`.
- `test_matches_sized_url_to_original`: `.../uploads/sites/83/2024/03/Asset-2@4x-e1711650607873-300x238.png` returns the one stored at `2024/03/Asset-2@4x-e1711650607873.png`.
- `test_matches_sized_url_to_scaled_original`: `.../uploads/2024/05/big-300x200.jpg` returns the one stored at `2024/05/big-scaled.jpg`.
- `test_no_match_returns_zero`: an uploads URL with no stored file, a URL with no `/uploads/` segment, and `''` all return `0` with no warning.

- [x] **Step 3: Run to verify they fail**

Run: `composer test-integration -- --filter LocalizeBlocksTest`
Expected: the five new tests fail with "Call to undefined function".

- [x] **Step 4: Implement `mai_get_attachment_id_from_demo_url()`**

Take the path after the last `/uploads/`, drop a leading `sites/<number>/`, and try up to three candidates in order: the path as is; the path with a trailing `-<w>x<h>` removed from the file name; that sized-stripped path with `-scaled` added before the extension. For each, build the local URL from `wp_get_upload_dir()['baseurl']` and call core's `attachment_url_to_postid()`, which does the `_wp_attached_file` lookup and prefers a case-sensitive match. Return the first non-zero ID.

- [x] **Step 5: Run to verify they pass**

Run: `composer test-integration -- --filter LocalizeBlocksTest`
Expected: PASS, with the existing tests unchanged.

- [x] **Step 6: Commit**

```bash
git add lib/functions/templates.php tests/phpunit/integration/LocalizeBlocksTest.php
git commit -m "Demo import: match demo images to local attachments by file path"
```

### Task 2: Localize nested blocks, matching by file path first

**Files:**
- Modify: `lib/functions/templates.php` (`mai_localize_blocks()`, `:542` onward)
- Test: `tests/phpunit/integration/LocalizeBlocksTest.php`

**Interfaces:**
- Consumes: `mai_get_attachment_id_from_demo_url()` from Task 1.
- Produces: `mai_localize_block( array $block, array &$replace_urls, array &$replace_ids ): array` in `lib/functions/templates.php`, `@access private`. Localizes one `core/cover`, `core/image` or `core/media-text` block, recurses into `innerBlocks`, and returns the block. `mai_localize_blocks( $content )` keeps its signature and return.

- [x] **Step 1: Write the failing tests**

Use existing-attachment fixtures only. Assert:
- `test_nested_image_is_localized`: an image block inside `core/column` inside `core/columns` inside `core/group` gets the local URL, `wp-image-<new id>` and the new `id` attr.
- `test_nested_cover_and_media_text_are_localized`: same for a cover inside a group and a media-text inside a column.
- `test_file_path_match_wins_over_slug`: an attachment whose slug differs from the file name (stored `2024/03/Asset-2@4x-e1711650607873.png`, slug `asset-24x`) is matched, and no download is attempted.
- `test_same_image_twice_uses_one_attachment`: two image blocks with the same demo URL both end on the same new ID, and the attachment count does not change.
- `test_second_import_changes_nothing`: running `mai_localize_blocks()` on its own output returns it unchanged and downloads nothing.

- [x] **Step 2: Run to verify they fail**

Run: `composer test-integration -- --filter LocalizeBlocksTest`
Expected: the nested tests and the file-path test fail; the second-import test may pass today.

- [x] **Step 3: Move the per-block work into `mai_localize_block()` and recurse**

`mai_localize_blocks()` parses, calls `mai_localize_block()` on each top-level block, then runs the existing `serialize_blocks()`, `$replace_urls` and single `wp-image-` pass. Inside `mai_localize_block()`, recurse into `innerBlocks` first, so a cover that is skipped still has its children visited. Then the three branches keep today's behavior, with each `continue` in today's loop becoming `return $block`. Look up `mai_get_attachment_id_from_demo_url( $image_url )` first, then `mai_get_existing_attachment_from_filename()`, then `mai_get_new_image_from_url()`.

- [x] **Step 4: Run to verify they pass**

Run: `composer test-integration -- --filter LocalizeBlocksTest`, then `composer test-integration -- --order-by=random`.
Expected: PASS, and every existing `LocalizeBlocksTest` case still passes.

- [x] **Step 5: Commit**

```bash
git add lib/functions/templates.php tests/phpunit/integration/LocalizeBlocksTest.php
git commit -m "Demo import: localize images in nested blocks"
```

### Task 3: Setup wizard localizes content areas and explains failed downloads

**Files:**
- Modify: `lib/classes/class-mai-setup-wizard-importer.php` (`import_template_parts()` `:168`, `download_file()` `:78`)

**Interfaces:**
- Consumes: `mai_localize_blocks( $content )` from Task 2.

- [x] **Step 1: Localize each content area before it is inserted**

In `import_template_parts()`, set `$post['post_content'] = mai_localize_blocks( (string) ( $post['post_content'] ?? '' ) );` before `wp_insert_post( $post )`. The wizard imports content before content areas, so the content's attachments are in the library by then. When Content is unchecked, the images are downloaded instead, as the single import does today.

- [x] **Step 2: Report the real reason a download failed**

In `download_file()`, when `wp_remote_get()` returns a `WP_Error`, send `wp_send_json_error()` with the URL and `$response->get_error_message()`. When the status code is not 200, send the URL and the code. Keep the existing empty-body message for a 200 with no body.

- [x] **Step 3: Full suites**

Run: `composer test-unit`, `composer test-integration -- --order-by=random`.
Expected: no new failures. The importer class has no integration tests; Task 4 checks it end to end.

- [x] **Step 4: Commit**

```bash
git add lib/classes/class-mai-setup-wizard-importer.php
git commit -m "Setup wizard: localize content area images and explain failed downloads"
```

### Task 4: End-to-end check, changelog and state

**Files:**
- Modify: `CHANGES.md`, `STATE.md`, this plan's Results section
- Scratch, outside the repo: `/tmp/tp-demo/` (Playwright run of the wizard)

- [x] **Step 1: Prepare the local site**

`local-site-a.test` (`~/Herd/local-site-a`) is a blank site with Genesis, Mai Chic, `mai-engine` symlinked to `~/Plugins/mai-engine`, and `wp-content/mu-plugins/demo-test-theme.php`, which makes Mai load the Side Hustle config (its demo has a top-level cover in `post-header` and an image three levels deep in `footer`). The content import from 2026-11-19 is already there: attachments 111 (`2024/02/milad-fakurian-GAnCCU7mqIU-unsplash-scaled-1.jpg`) and 339 (`2024/03/Asset-2@4x-e1711650607873.png`). Attachment 376 is a stray copy of the Asset image from that day's investigation; leave it, and expect the run to match 339, not 376. Record the attachment count before the run.

The site blocks outgoing HTTP. Ask Mike to run `mai-sites isolate allow local-site-a.test demo-site-a.example --for=1h` (a write the agent never runs itself), and wait for his "done".

- [x] **Step 2: Run the wizard headless**

Install Playwright under `/tmp/tp-demo/pw` if it is gone, get a login link with `mai-sites login local-site-a.test --redirect='/wp-admin/admin.php?page=mai-setup-wizard'` (it works once, for a minute), and drive the wizard headless: "No thanks", "Skip this step" (plugins), "Import Content" with all three boxes checked. Wait until the page shows "Step 4 of 4" or an error, not just for network idle: the content import keeps running server-side after the browser closes, and the content area and Customizer steps only start from the page. Never click "Yes please, sign me up!".

- [x] **Step 3: Check the content areas**

With `wp eval-file` from `/tmp`, walk every `mai_template_part`'s blocks recursively and print, for each cover, image and media-text: the `id` / `mediaId` attr, its `wp-image-` class, and whether `url` and the `<img>` `src` are local. Expected: `post-header`'s cover and `footer`'s image point at local URLs, their `id` and class name an existing local attachment (111 and 339), and the attachment count did not grow.

- [x] **Step 4: Check the failure message**

Ask Mike to run `mai-sites isolate revoke local-site-a.test demo-site-a.example`. Delete the wizard's cached download folder `~/Herd/local-site-a/wp-content/mai-setup-wizard/side-hustle/` (it caches files for an hour), run the wizard again, and confirm the error names the URL and the blocked request instead of "response body could not be retrieved".

- [x] **Step 5: Changelog, Results and STATE**

Add to the `## 2.42.0 (unreleased)` section of `CHANGES.md`, in its Fixed group: images in imported demo content areas now load from the site's own media library, including images inside groups and columns. If the demo import's Fixed line from the Tag Processor work is next to it, merge the two into one line. Fill in Results below. Rewrite `STATE.md` per its sections: this branch, what's done, the end-to-end result, next step (Mike reviews, then it joins the Tag Processor work for the beta).

- [x] **Step 6: Final checks and commit**

Run the full suites and the worktree `deployable-guard` check from Global Constraints.

```bash
git add CHANGES.md STATE.md docs/plans/2026-11-19-demo-import-images.md
git commit -m "Changelog, results and state for demo import images"
```

## Results

Filled in during the build, on 2026-11-19.

- Attachment count before and after the wizard run: 31 before, 31 after (highest attachment ID 376 both times). The run reused the wizard's cached demo files, which were 57 minutes old and under its one-hour limit, so it did not fetch them again. The content areas still came from the demo's own `template-parts.json`. After the final review fixes, a second run with the cache cleared fetched fresh files from the demo host and gave the same result: 31 attachments before and after, and the same IDs below.
- Content area images after the run (block, id, class, local or not):
  - `post-header` cover: `id` 111, class `wp-image-111`, `url` and `<img>` `src` local (`uploads/2024/02/milad-fakurian-GAnCCU7mqIU-unsplash-scaled-1.jpg`).
  - `footer` image, three levels deep: `id` 339, class `wp-image-339`, `src` local (`uploads/2024/03/Asset-2@4x-e1711650607873.png`). The stray copy, attachment 376, was not used.
- Failure message shown with the host blocked: Mike kept the host allowed, so a temporary mu-plugin made requests to the demo host fail, and was deleted after the check. With a request error the wizard showed "https://demo-site-a.example/side-hustle-influencer/wp-content/uploads/sites/83/mai-engine/content.xml could not be downloaded: Outgoing request blocked by test scaffold." With a 404 it showed "... could not be downloaded. The server returned status 404." Both times the wizard stopped after the first content request.
- Suites and guard: `composer test-unit` 280 tests OK (11 skipped). `composer test-integration -- --order-by=random` 813 tests OK. `deployable-guard` on the committed tree: "OK: committed autoloader is deployable as-is."
- Final review fixes: the wizard now localizes a content area before trashing the old one, so a timeout while downloading images no longer leaves the site without it. A demo image used twice in one content area now reuses the first match, so it downloads once and both blocks get the same ID.
- Added after the plan, at Mike's request: both content area imports now `wp_slash()` the content, because `wp_insert_post()` unslashes and a `button--primary` class came back as `buttonu002du002dprimary`. A wizard run with that class injected into the cached footer kept the escape. Across all 40 demo `template-parts.json` files, the only backslashes are `\/` in ACF block JSON, which mean the same as `/`, so the exporter needs no change and no demo needs re-exporting.
- Also added at Mike's request: the wizard's WXR importer rewrote image URLs in imported posts with an unslashed `wp_update_post()`, which stripped every escape. The Side Hustle homepage lost all 10, and its Mai Grid custom content read `u003cp ...` instead of HTML. 14 pages across 13 demos have escapes and image URLs. A Mai subclass fixed it first; then upstream turned out to have fixed it four days earlier, so the vendored `proteusthemes/wp-content-importer-v2` was updated from 2.1.2 to 2.1.4 instead, the subclass was removed, and Mai's own `mai_setup_wizard_slash_data` filter was removed so wizard imports are not slashed twice. Covered by `SetupWizardSlashDataTest` and `SetupWizardWxrImporterTest`. Not re-run end to end: that needs fresh attachment downloads on a blank site.
