# Entry Image Shape Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entry images in grids, archives and singles show in their chosen cropped shape even when WordPress serves an uncropped file (issue #677).

**Architecture:** One helper decides the forced ratio from the entry args. `Mai_Entry::get_image()` gives the `<img>` itself the class `has-aspect-ratio` and an inline `--aspect-ratio`, for grids, archives and singles alike. One CSS rule shapes only marked images, and the old left-full and right-full image rule is deleted. `mai_do_entries_open()` is not touched.

**Tech Stack:** PHP 8.1 floor, WordPress 6.4 floor, procedural `mai_` functions plus the `Mai_Entry` class, SCSS built with gulp, PHPUnit 10 with Brain Monkey (unit) and the WordPress test suite (integration).

**Spec:** `docs/specs/2026-11-19-entry-image-shape.md`

## Global Constraints

- Repo `~/Plugins/mai-engine`, branch `develop`. WordPress coding standards, procedural and unnamespaced in `lib/functions/`, `@since 2.42.0` on everything new.
- No em-dashes in code comments, changelog lines, commit messages or UI text. Commit message paragraphs on one line. End commit messages with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Stage files by explicit path only. Never `git add -A` or `git add .`.
- Never run `composer install`, `update` or `dump-autoload` in the repo, and never `npm run dev` (it runs `composer install`).
- Unit tests: `composer test-unit`. Integration: `composer test-integration -- --order-by=random`. Never run two integration runs at once (they share the test database). Integration stubs for global functions go in `tests/phpunit/integration/plugin-loader.php`, never in test files.
- Build CSS with `npx gulp build:main-css build:editor-css` only. On a clean tree it reproduces the committed files byte for byte (checked 2026-11-20), so only `assets/css/main.min.css` and `assets/css/editor.min.css` may change.
- Browser checks run headless only. Never launch a visible browser. Scratch scripts live in `/tmp`, never in the repo.
- Never write a real client site or local `.test` name into the repo. Use `docs-local/site-key.md` stand-ins.
- Deployable check after the last commit: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard`.
- No push. Commit each task locally.

## Review Focus

- **A theme's `--entry-image-object-fit`** (for example `contain` on `.entries-archive`): it still wins over `cover`. Browser check in Task 3.
- **A lazy `sizes="auto"` image that is not marked,** including one in an unshaped grid nested inside a shaped grid's entry: keeps the ratio from its width and height attributes (300×450 for a 400×600 image in a 300px column). Browser check in Task 3.
- **Background grids:** the card keeps today's shape from the grid tag, for orientations and for uncropped custom sizes. `mai_do_entries_open()` is unchanged; browser check in Task 3.
- **A `mai_entry_image_size` filter that returns `large` for a landscape grid:** the image is still marked, because the shape follows the setting. Integration test in Task 2.
- **A marked image printed outside any grid** (code calling `mai_do_entry()` on its own): it carries its own ratio, so it is shaped like any other. Browser check in Task 3.

---

### Task 0: Commit the spec and plan

- [ ] **Step 1: Commit** `docs/specs/2026-11-19-entry-image-shape.md` and `docs/plans/2026-11-20-entry-image-shape.md` if either is untracked or changed: "Spec and plan: entry images keep their chosen shape (#677)". Later steps check changes with `git diff --name-only`, so untracked files don't get in the way.

### Task 1: The ratio helper

**Files:**
- Modify: `lib/functions/images.php` (add after `mai_get_image_aspect_ratio()`)
- Test: `tests/phpunit/unit/EntryImageShapeTest.php` (create)

**Interfaces:**
- Produces: `mai_get_entry_aspect_ratio( $args ): string`. `$args` holds `image_orientation` and `image_size`. Returns a ratio like `4/3`, or `''` when no shape is forced.

- [ ] **Step 1: Write the failing tests.** New unit test class `EntryImageShapeTest` extending `BizBudding\MaiEngine\Tests\TestCase`, `#[RunTestsInSeparateProcesses]` (the helpers it calls cache statically). `setUp()` requires `lib/functions/helpers.php`, `utilities.php` and `images.php`, stubs `mai_get_config` to return `[ 'add' => [ 'landscape' => '4:3', 'portrait' => '3:4', 'square' => '1:1' ] ]` (as `MasonryTest` does), sets `$GLOBALS['_wp_additional_image_sizes']` to `[ 'hard' => [ 'width' => 600, 'height' => 400, 'crop' => true ], 'soft' => [ 'width' => 600, 'height' => 400, 'crop' => false ], 'flat' => [ 'width' => 600, 'height' => 0, 'crop' => true ], 'pos' => [ 'width' => 300, 'height' => 300, 'crop' => [ 'center', 'top' ] ] ]`, and stubs `get_option` so `thumbnail_size_w`/`h` are `150`, `thumbnail_crop` is `'1'`, `large_size_w`/`h` are `1024`, `large_crop` is `false`, `medium_*` are `300` and `medium_crop` is `''`. Tests, each `assertSame`:
  - `test_an_orientation_gives_its_ratio`: `landscape` gives `'4/3'`, `portrait` gives `'3/4'`.
  - `test_masonry_gives_nothing`: `masonry` gives `''`.
  - `test_custom_with_a_cropped_size_gives_its_ratio`: `custom` + `hard` gives `'600/400'`; `custom` + `pos` gives `'300/300'`; `custom` + `thumbnail` gives `'150/150'`.
  - `test_custom_with_an_uncropped_size_gives_nothing`: `custom` + `soft`, `large`, `medium` each give `''`.
  - `test_a_size_with_no_height_gives_nothing`: `custom` + `flat` gives `''`.
  - `test_an_unknown_size_gives_nothing`: `custom` + `nope` gives `''` (never `4/3`).
  - `test_an_unknown_orientation_gives_nothing`: `wide` + `hard` gives `''`, and missing keys (`[]`) give `''`.

- [ ] **Step 2: Run** `composer test-unit -- --filter EntryImageShapeTest`. Expected: errors, `mai_get_entry_aspect_ratio` undefined.

- [ ] **Step 3: Implement `mai_get_entry_aspect_ratio( $args )`** in `lib/functions/images.php`. Docblock with the why from the spec ("When a shape is forced"). Orientation: `mai_has_image_orientiation()` then `mai_get_aspect_ratio_from_orientation()`. Custom: read `mai_get_available_image_sizes()[ $image_size ]`, require a truthy `crop` and `absint` width and height above 0, return `sprintf( '%s/%s', $width, $height )` so the string matches what `mai_get_image_aspect_ratio()` returns today. Cast `$args` values with `is_string()` checks. Read the size data only on the `custom` path: every entry calls this helper after Task 2, so the common orientation path should stay a cached lookup, and unit tests that stub no `get_option()` would fatal on an up-front read.

- [ ] **Step 4: Run** `composer test-unit -- --filter EntryImageShapeTest`. Expected: PASS.

- [ ] **Step 5: Commit** `lib/functions/images.php` and `tests/phpunit/unit/EntryImageShapeTest.php`: "Entry images: add the helper that decides a forced ratio".

### Task 2: Marking the image

**Files:**
- Modify: `lib/classes/class-mai-entry.php` (new property; constructor, before the `array_merge` that writes the filtered `image_size` into `$this->args`; `get_image()`, the attributes passed to `wp_get_attachment_image()`)
- Do not modify `tests/phpunit/integration/plugin-loader.php`. `images.php` adds image filters the rest of the suite does not expect (`LogoAttributesTest` explains), so the integration test requires it in its own process.
- Test: `tests/phpunit/unit/EntryImageShapeTest.php` and `tests/phpunit/integration/EntryImageShapeTest.php` (create)

**Interfaces:**
- Consumes: `mai_get_entry_aspect_ratio( $args ): string` from Task 1.
- Produces: `protected $aspect_ratio` on `Mai_Entry`, a string, `''` when no shape is forced. On a shaped image, `class="entry-image size-<size> has-aspect-ratio"` and `style="--aspect-ratio:<ratio>;"`, which Task 3's CSS targets.

- [ ] **Step 1: Write the failing unit tests** in `EntryImageShapeTest`, calling `get_image()` on a `Mai_Entry` built with `ReflectionClass::newInstanceWithoutConstructor()` (as `MasonryTest::image_size()` does). Set by reflection: `image_id` `5`, `image_size` `landscape-sm`, `context`, and `aspect_ratio`. Stub `wp_get_attachment_image` to save the `$attr` array it receives into a variable the test holds by reference, and return `'<img>'`; stub `wp_get_attachment_caption` to return `''`. Assert on the saved array, so a missing `style` key can be told apart from an empty one. Brain Monkey provides `add_filter` and `remove_filter`.
  - `test_a_shaped_image_carries_its_mark_and_ratio`: context `archive`, ratio `4/3`: `$attr['class']` is `entry-image size-landscape-sm has-aspect-ratio`, `$attr['style']` is `--aspect-ratio:4/3;`.
  - `test_a_shaped_single_image_carries_its_mark_and_ratio`: context `single`, ratio `3/4`: same class, `$attr['style']` is `--aspect-ratio:3/4;`.
  - `test_an_unshaped_image_is_unchanged`: ratio `''`, contexts `archive` and `single`: `$attr['class']` is `entry-image size-landscape-sm`, and `assertArrayNotHasKey( 'style', $attr )`.

- [ ] **Step 2: Write the failing integration test** `tests/phpunit/integration/EntryImageShapeTest.php`, extending `MaiIntegrationTestCase`, with `#[RunTestsInSeparateProcesses]` (the helpers cache statically). `set_up()` requires `lib/functions/images.php` as `LogoAttributesTest` does, and sets `$GLOBALS['mai_test_config']` to `[ 'image-sizes' => [ 'add' => [ 'landscape' => '4:3' ] ], 'global-styles' => [ 'breakpoint' => 1200 ], 'settings' => [] ]` (a test config replaces the whole default, and `mai_get_breakpoints()` divides the breakpoint); `tear_down()` unsets it. Create a post and an attachment, and link them with `update_post_meta( $post_id, '_thumbnail_id', $attachment_id )`: `set_post_thumbnail()` refuses an attachment with no image file, and `Mai_Entry` only sets `image_size` when there is an image ID. Args: `context` `archive`, `disable_entry_link` false, `show` `[ 'image', 'title' ]`, `image_position` `full`, plus the orientation and size below.
  - `test_the_ratio_follows_the_setting_not_the_size_filter`: a `mai_entry_image_size` filter returns `large`; `image_orientation` `landscape`: by reflection `aspect_ratio` is `'4/3'` and `image_size` is `'large'`.
  - `test_custom_with_an_uncropped_size_has_no_ratio`: `image_orientation` `custom`, `image_size` `large`: `aspect_ratio` is `''`.

- [ ] **Step 3: Run** `composer test-unit -- --filter EntryImageShapeTest` and `composer test-integration -- --filter EntryImageShapeTest`. Expected: FAIL (no property, no class, no style).

- [ ] **Step 4: Implement.** Add `protected $aspect_ratio = '';` with a docblock. In the constructor, set `$this->aspect_ratio = mai_get_entry_aspect_ratio( $this->args );` before the line that merges the filtered `image_size` into `$this->args`, with a comment that the shape follows what the editor chose, not the filter. In `get_image()`, build the attributes array first; when `$this->aspect_ratio` is set, append ` has-aspect-ratio` to its class and set its `style` to `--aspect-ratio:<ratio>;`, with a comment that the image carries its own ratio so it never depends on a wrapper. Add `@since 2.42.0` lines to the changed method docblocks.

- [ ] **Step 5: Run** `composer test-unit` and `composer test-integration -- --order-by=random`. Expected: PASS, including `MasonryTest`.

- [ ] **Step 6: Commit** `lib/classes/class-mai-entry.php` and both test files: "Entry images: give shaped images their own mark and ratio".

### Task 3: The CSS rule, browser checks, changelog

**Files:**
- Modify: `assets/scss/components/entry/_entry-image.scss` (after the `.entry-image` rule)
- Modify: `assets/scss/components/entry/_entry.scss` (delete the `.has-image-left-full, .has-image-right-full { .entry-image { aspect-ratio: ... } }` block)
- Modify: `assets/css/main.min.css`, `assets/css/editor.min.css` (rebuilt, never hand-edited)
- Modify: `CHANGES.md` (2.42.0 section), `docs/plans/2026-11-20-entry-image-shape.md` (Results)

**Interfaces:**
- Consumes: the image's `has-aspect-ratio` class and inline `--aspect-ratio` from Task 2.

- [ ] **Step 1: Write the browser check** as a scratch Node script in `/tmp/entry-shape/check.js` using Playwright (installed at `/tmp/tp-demo/pw/node_modules/playwright` on this machine; if it is gone, install Playwright in `/tmp/entry-shape`). It loads the built `assets/css/main.min.css` into a page per case, runs in Chromium, Firefox and WebKit headless, and prints each image's box and computed `aspect-ratio` and `object-fit`. Every image except the lazy ones is a real SVG or PNG data URI of the stated size, and the script waits for it to load, because `.entry-image { width: unset }` makes the file's own width decide the center and left-top widths. Markup copies what Mai prints after Task 2: `.entries` with the class list and style `mai_do_entries_open()` prints today (including `--aspect-ratio` for background, left-full and right-full), `.entries-wrap`, `article.entry`, `a.entry-image-link`, and a shaped `img` with `class="entry-image size-landscape-sm has-aspect-ratio" style="--aspect-ratio:4/3;"` plus width and height attributes. One class attribute per element: a second `class` attribute is ignored by the browser. Cases and expected boxes, 400px column unless stated:
  - Landscape grid, `full`, 300×190 file, shaped: 400×300, `cover`.
  - Same with a 200×300 portrait file: 400×300.
  - `center`, 300×190 file: 300×225.
  - `left-top` at a third, 300×190 file: 133.33×100 (height is width × 3/4).
  - Stacked `left-top` (`has-image-stack` on `.entries-wrap`) at a 500px viewport, 300×190 file: 300×225.
  - Boxed (`has-boxed`) `center`, 300×190 file: 300×225.
  - Single: `figure.entry-image-link.entry-image-single` with a shaped image and no `.entries` around it, 300×190 file, 400px wide content: 400×300.
  - A shaped image in a plain `div`, no Mai wrapper at all, lazy `sizes="auto, 300px"` 400×600 file below the fold, 300px column: 300×225.
  - `left-full` at a third, 600px card, short excerpt, 300×190 file, shaped: 200×150 (the same as before the change).
  - `left-full`, 600px card, custom `large`, not shaped, with the tag Mai really prints for it (`--aspect-ratio:1024/1024;--entry-image-link-max-width:1024px;`), short excerpt, 300×190 file: 475×475 before the change, 475×301 after. This is the agreed change.
  - `left-full` at a third, shaped, long excerpt: the image fills the card's height before and after (about 200×609 in a 600px card).
  - `background`, 400px card: landscape gives a 400×300 card, custom `large` (tag `--aspect-ratio:1024/1024;`) a 400×400 card, before and after.
  - Masonry, not shaped: own shape.
  - `custom` + `large` at `full`, not shaped: own shape.
  - Unshaped grid nested in the entry of a shaped `has-image-full` grid, lazy `sizes="auto, 300px"` 400×600 image below the fold, 300px column: 300×450 before and after.
  - `.entries-archive` with `--entry-image-object-fit: contain` in a theme rule, shaped image: `contain`.

- [ ] **Step 2: Run** it against the current CSS. Expected: the shaped cases keep the file's own shape (the bug), the rest match.

- [ ] **Step 3: Add the rule and delete the old one.** In `_entry-image.scss`, right after `.entry-image`, add the rule below, with a comment on why it only applies to marked images (a global `aspect-ratio`, even `auto`, drops the ratio lazy images take from their attributes) and why `object-fit` reads the variable. In `_entry.scss`, delete the left-full and right-full `.entry-image` aspect-ratio block; leave `.has-image-background .entry` alone.

  ```scss
  .entry-image:where(.has-aspect-ratio) {
  	aspect-ratio: var(--aspect-ratio);
  	object-fit: var(--entry-image-object-fit, cover);
  }
  ```

- [ ] **Step 4: Build** with `npx gulp build:main-css build:editor-css`. Expected: `git diff --name-only` shows only `_entry-image.scss`, `_entry.scss`, `main.min.css` and `editor.min.css`; both built files contain `.entry-image:where(.has-aspect-ratio)` and no longer contain `.has-image-left-full .entry-image,.has-image-right-full .entry-image{aspect-ratio`.

- [ ] **Step 5: Run the browser check again.** Expected: every case matches its expected box in all three engines. Record the table in this plan's Results.

- [ ] **Step 6: Add two changelog lines** under `## 2.42.0 (unreleased)`, each at the end of its own group (all Changed lines sit above all Fixed lines): "Fixed: Grid, archive and single post images keep their chosen shape when an image has no crop in that size, such as images uploaded before the site used Mai." and "Changed: Grids with the image position set to Left Full or Right Full and an uncropped image size now show the photo's own shape instead of the size's width and height."

- [ ] **Step 7: Run** `composer test-unit` and `composer test-integration -- --order-by=random`. Expected: PASS.

- [ ] **Step 8: Rewrite `STATE.md`** (about 40 lines: Now, Next, Blocked, Verify, Gotchas) for where this work leaves `develop`. Keep its queued GitHub batch (closing issues and deleting branches) on hold exactly as it is; it fires only when Mike says so. Add closing #677 to that batch, not to anything that runs now.

- [ ] **Step 9: Commit** `assets/scss/components/entry/_entry-image.scss`, `assets/scss/components/entry/_entry.scss`, `assets/css/main.min.css`, `assets/css/editor.min.css`, `CHANGES.md`, `STATE.md` and this plan: "Entry images: keep the chosen shape when the crop is missing". Then run the deployable check from Global Constraints.

## Results

To be filled in Task 3.
