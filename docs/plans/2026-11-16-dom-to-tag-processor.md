# Replace DOMDocument with WP_HTML_Tag_Processor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move 18 of Mai Engine's 19 DOMDocument call sites to `WP_HTML_Tag_Processor`, fix the bugs found in those lines, and prove with tests, a corpus diff and timings that real content renders the same except where intended.

**Architecture:** Each call site is rewritten inline, the way the existing Tag Processor uses in `lib/` are written. No new helper: fixed classes use one `add_class()` call each, and free-form class input goes through the existing `mai_add_classes()`, which Task 1 teaches to de-duplicate. Tests go in the WordPress-loaded integration suite and assert meaning (class tokens, style declarations, attribute values), not serialized bytes. The corpus diff and profiling are scratch scripts outside the repo.

**Tech Stack:** PHP 8.1 floor, WordPress 6.4 floor, `WP_HTML_Tag_Processor`, PHPUnit via `tests/composer.json` (wp-phpunit, WordPress 7.x), WP-CLI against local Herd sites.

**Spec:** `docs/specs/2026-11-16-dom-to-tag-processor.md`. Read it first; this plan argues from it and does not repeat its reasoning.

## Global Constraints

- WordPress 6.4 and PHP 8.1 floors (plugin header). Only Tag Processor methods listed in the spec's Constraints.
- Existing files stay procedural, unnamespaced, WordPress coding standards. No `declare(strict_types=1)` in existing files.
- `get_attribute()` returns `null` (absent) or `true` (valueless). Guard every `class` / `style` / `src` read with `is_string()`.
- Adding classes: a fixed class name gets its own `add_class()` call. Free-form input (a filter's return, a `$class` argument, a built string) goes through `mai_add_classes()`: read `class` with an `is_string()` guard, pass it as `$existing`, and `set_attribute( 'class', ... )` the result. Never pass a space-separated string to `add_class()`. `mai_add_classes()` returns `esc_attr()` output, so `set_attribute()` escapes it a second time on WordPress 6.9+; that only matters for a class containing `&` or a quote, which real class names don't. Accepted.
- Leave `mai_get_dom_document()`, `mai_get_dom_html()`, `mai_get_dom_first_child()` and `mai_custom_scroll_logo()` unchanged. Leave `mai_wprm_dom_convert_svgs()` defined but unused: it is a global function, and a production child theme or snippet calling it would fatal if it disappeared.
- New functions are marked `@access private` in their docblocks, as `lib/functions/templates.php` does, so they don't become public API.
- **Never stage `vendor/`, `TODO.md` or `lib/functions/images.php`.** The working tree on `develop` has uncommitted changes to all of them that are not part of this work, and the `vendor/composer` changes are a with-dev autoloader. Committing that autoloader takes every site down on deploy. Stage files by explicit path only, never `git add -A` or `git add .`. Never run `composer dump-autoload` in this working tree.
- Because of that dirty `vendor/`, `php vendor/bin/deployable-guard check` fails in the working tree before any change (measured 2026-11-16: exit 1, untracked symfony and react files). Check the committed tree instead: `git worktree add --detach /tmp/tp-guard HEAD && php vendor/bin/deployable-guard check --root=/tmp/tp-guard; git worktree remove --force /tmp/tp-guard` (`--detach`, because the branch is already checked out in the main tree and git refuses a second checkout).
- Test stubs for global functions go in `tests/phpunit/integration/plugin-loader.php` behind `function_exists`, never in a test file: every test file is namespaced (`BizBudding\MaiEngine\Tests\Integration`), so a function declared there is not the global one.
- `phpunit.xml.dist` sets `failOnWarning`. Fixtures must not trigger "Undefined array key" warnings.
- Never write a real client site name in the repo, commit messages included. Use the stand-ins in `docs-local/site-key.md` (news-site-c, content-site-a, and so on).
- No em-dashes in commit messages, changelog lines or code comments.
- No push, tag, beta or release. Mike does those.
- Record every result the plan asks for (baseline counts, 6.4 differences, corpus counts, timings, extra bugs fixed) in the `## Results` section at the end of this file, stand-in names only.
- Tests run with `composer test-integration -- --filter <TestClass>`. Full suites: `composer test-unit`, `composer test-integration -- --order-by=random`. `GridCacheAfterPageTest` fails 2 tests on some MySQL/MariaDB versions regardless.

## Review Focus

1. **A converted filter gets HTML with no matching tag** (empty string, plain text, only a comment). Expect the input back unchanged and no warning. Every task's tests include one such case.
2. **Class or style attribute present but valueless** (`<div style>`, `<a class>`). Expect no `1--x` values and no warnings; treated as empty. Covered in Tasks 2 and 5.
3. **`mai_archive_pagination_link_classes` returns a string, array items with spaces, or null** (a callback that forgets to return). Expect each token once, and null treated as no classes, not a fatal. Covered in Tasks 1 and 5.
4. **A self-closing `<svg/>` inside a Recipe Maker button, followed by more markup.** Expect nothing after it to get `fill="currentColor"`. Covered in Task 3.
5. **Demo import image IDs that overlap** (`wp-image-12` next to `wp-image-123`, or one image's new ID equal to another's old ID). Expect each class rewritten exactly once to its own new ID. Covered in Task 7.

---

### Task 1: Branch, baseline, test harness and `mai_add_classes()` de-duplication

**Files:**
- Modify: `lib/functions/helpers.php:1181-1190` (`mai_add_classes()`)
- Modify: `tests/phpunit/integration/plugin-loader.php` (load the files and stubs every later task needs, listed in Step 2)
- Modify: `tests/phpunit/integration/MaiIntegrationTestCase.php` (two assertion helpers)
- Test: `tests/phpunit/integration/AddClassesTest.php`

**Interfaces:**
- Changes: `mai_add_classes( $new, $existing = '' )` keeps its signature and its early return of `$existing` untouched when `$new` is empty (including `null`). Otherwise it splits `$existing . ' ' . $new` with `preg_split( '/\s+/', ..., -1, PREG_SPLIT_NO_EMPTY )`, drops repeats with `array_unique()` (first occurrence wins, order kept), and returns the tokens joined by single spaces through `esc_attr()`. Mike approved this exact shape on 2026-11-16; it was measured at 0.64 µs per call against 0.40 µs for `explode`, which splits only on spaces.
- Produces (tests): `MaiIntegrationTestCase::tagClasses( string $html, string $tag, int $nth = 1 ): array` returns the class tokens of the nth matching tag, or `[]`; `MaiIntegrationTestCase::tagStyles( string $html, string $tag, int $nth = 1 ): array` returns that tag's style declarations as an ordered list of trimmed, non-empty `prop:value` strings.
- Produces (harness): globals `$GLOBALS['mai_test_wc_loop_columns']` (read by the `wc_get_loop_prop()` stub) and the fixture icons dir `tests/phpunit/integration/fixtures/icons`.

- [ ] **Step 1: Branch and baseline**

Run: `git switch -c dom-to-tag-processor develop`, then `composer test-unit` and `composer test-integration -- --order-by=random`, then the worktree `deployable-guard` check from Global Constraints.
Expected: green apart from the known `GridCacheAfterPageTest` failures. Record the counts in Results. Confirm `git status --short` still shows the unrelated `TODO.md`, `lib/functions/images.php` and `vendor/` changes, and leave them.

- [ ] **Step 2: Extend the harness**

In `plugin-loader.php`:
- Require: `packages/kirki/lib/class-aricolor.php` (the unit suite does the same, `tests/phpunit/unit/InputBorderColorTest.php:25`), `lib/functions/colors.php`, `lib/functions/columns.php`, `lib/blocks/mai-grid/functions.php` (defines `mai_get_flex_align()`), and the files under conversion: `lib/blocks/paragraph.php`, `heading.php`, `group.php`, `button.php`, `lib/structure/single.php`, `comments.php`, `pagination.php`, `lib/functions/shortcodes.php`, `icons.php`, `templates.php`, `lib/support/woocommerce.php`, `wp-recipe-maker.php`. Load each one and run the suite after each; if one can't load bare, stop and record why in Results rather than stubbing Genesis.
- Add the real `global-styles` block from `config/_default.php:22-40` (`breakpoint`, `contrast-limit` 160, `colors`, `custom-colors`) to the default stub config in `mai_get_config()`. Without `contrast-limit`, `mai_is_light_color()` is always false. Adding only `contrast-limit` is worse: `mai_get_global_styles()` falls back to the whole array for a missing key (`utilities.php:215`), so `mai_get_custom_colors()` reads `['color']` on an int and warns, failing whichever color test runs first.
- Stubs behind `function_exists`: `mai_get_dir()` returning `trailingslashit( $plugin_root )` (the real one is in `lib/init.php:480`, which the suite doesn't load); `mai_icons_get_dir()` returning `$plugin_root . '/tests/phpunit/integration/fixtures/icons'`; `wc_get_loop_prop( $prop )` returning `$GLOBALS['mai_test_wc_loop_columns'] ?? 4` for `columns`.

- [ ] **Step 3: Write the tests**

`AddClassesTest`:
- `test_appends`: `mai_add_classes( 'b c', 'a' )` is `'a b c'` (passes today).
- `test_array_input`: `mai_add_classes( [ 'b', 'c d', '' ], 'a' )` is `'a b c d'`.
- `test_dedupes`: `mai_add_classes( 'a button', 'a b' )` is `'a b button'`.
- `test_normalizes_whitespace`: `mai_add_classes( "c\td", " a  b\n" )` is `'a b c d'`.
- `test_empty_new_returns_existing_untouched`: `''`, `[]` and `null` return `' a  b '` byte for byte.
- `test_empty_existing`: `mai_add_classes( 'a a', '' )` is `'a'`.

- [ ] **Step 4: Run to verify the de-dupe and whitespace tests fail**

Run: `composer test-integration -- --filter AddClassesTest`
Expected: `test_dedupes`, `test_normalizes_whitespace`, `test_empty_existing` and the array test fail; the rest pass.

- [ ] **Step 5: Change `mai_add_classes()` and add the two test helpers**

Add `@since 2.42.0 De-duplicate classes and normalize whitespace.` to its docblock. 2.42.0 is provisional; Mike may change the version at release.

- [ ] **Step 6: Run the new test and the full integration suite**

Run: `composer test-integration -- --filter AddClassesTest`, then `composer test-integration -- --order-by=random`, then `composer test-unit`.
Expected: PASS, and no new failures from the files now loaded or from the changed helper (`mai_add_classes()` has 52 callers in `lib/`; five other plugins call it too, all with plain class strings or arrays).

- [ ] **Step 7: Commit**

```bash
git add lib/functions/helpers.php tests/phpunit/integration/plugin-loader.php tests/phpunit/integration/MaiIntegrationTestCase.php tests/phpunit/integration/AddClassesTest.php
git commit -m "mai_add_classes: drop repeated classes and extra spaces"
```

### Task 2: Block filters (paragraph, heading, group, button)

**Files:**
- Modify: `lib/blocks/paragraph.php:116-131`, `lib/blocks/heading.php:98-113`, `lib/blocks/group.php:51-77`, `lib/blocks/button.php:106-182`
- Test: `tests/phpunit/integration/BlockFiltersTagProcessorTest.php`

**Interfaces:**
- Consumes: `tagClasses()`, `tagStyles()` from Task 1. Every class here is fixed, so use `add_class()` / `remove_class()` only. Never call `set_attribute( 'class', ... )` on a tag that also gets `add_class()` / `remove_class()`: it discards their queued changes.

- [ ] **Step 1: Write the tests**

Call `mai_render_paragraph_block()`, `mai_render_heading_block()`, `mai_render_group_block()`, `mai_render_button_block()` directly with a `$block` array. Measure every expected value against today's code before converting; don't write them from reading.
- Paragraph with `contentAlign` `start`: first tag's classes include `has-no-margin-left` (LTR); `end` gives `has-no-margin-right`; `center` returns input byte-identical. Same three for heading. Avoid 9-character inputs such as `<p>ab</p>`: `paragraph.php:85` returns `''` for those.
- Group with `contentAlign` and `customBackgroundColor`: `tagStyles()` starts with `--group-block-justify-content:<mai_get_flex_align() value>` followed by the existing declarations; `#ffffff` gives `has-light-background`, `#111111` gives `has-dark-background`. Use hex values, not palette names: palette names don't resolve in the suite. Inner `<svg viewBox="0 0 1 1"><path d="M0"/></svg>` survives byte for byte.
- Group whose root has a valueless `style`: no declaration starting with `1`, no warning.
- Button, legacy attributes: `className` `button-small` (that is what `button.php:43` checks; `is-style-small` does not trigger it), legacy `textColor` and `backgroundColor` slugs, and a radius. Each `div`'s `tagStyles()` equals today's measured list. The `a` gains `button-small` and loses `has-text-color`, `has-background`, `has-{slug}-color`, `has-{slug}-background-color`.
- Button, `style.color` attributes: slug classes are kept (today's behavior, `button.php:86-99`).
- Button bug fix: an `a` with `has-background-blur has-background` keeps `has-background-blur` and loses `has-background`.
- Button with two `div` and two `a`: all four updated.
- No-tag input (`''`, `'text'`) returns unchanged for all four.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter BlockFiltersTagProcessorTest`
Expected to fail today, measured: the group `viewBox` byte test (DOM writes `viewbox` and `<path ...></path>`), the button `'text'` no-tag test (DOM returns `"text\n"`), and the `has-background-blur` test. Expected to pass today: everything else, including the valueless `style` test (DOM's `getAttribute()` returns `""`). Any other result means the test is wrong about today: fix the test, not the code.

- [ ] **Step 3: Convert the four filters**

First-tag sites use `next_tag()` once. Button walks every tag once and branches on `get_tag()` for `DIV` and `A`. The style rebuild logic stays as it is; color classes go through `remove_class()`.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter BlockFiltersTagProcessorTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add lib/blocks/paragraph.php lib/blocks/heading.php lib/blocks/group.php lib/blocks/button.php tests/phpunit/integration/BlockFiltersTagProcessorTest.php
git commit -m "Blocks: set paragraph, heading, group and button attributes with the Tag Processor"
```

### Task 3: WP Recipe Maker SVG fill

**Files:**
- Modify: `lib/support/wp-recipe-maker.php:104-134` (replace the DOM block; leave `mai_wprm_dom_convert_svgs()` defined but uncalled, with a docblock line saying it is kept only so external callers don't fatal)
- Test: `tests/phpunit/integration/RecipeMakerButtonsTest.php`

**Interfaces:**
- Consumes: `tagClasses()` from Task 1.

- [ ] **Step 1: Write the tests**

Through `mai_wprm_convert_buttons()`:
- A `wprm-recipe-*-button` link containing `<svg fill="#333"><g fill="none"><path fill="#333"/></g><path/></svg>`: the `svg` and the filled `path` get `fill="currentColor"`, the `g` keeps `none`, the unfilled `path` gets no `fill`.
- An `svg` without its own `fill` keeps none, while its filled children change.
- An `svg` in a non-button `a`, and one outside any `a`: unchanged.
- `<a class="wprm-recipe-print-button"><svg fill="#000"/></a><p><svg><path fill="#111"/></svg></p>`: the self-closing `svg` changes, the later `path` does not.
- Two button links where the first has an unclosed `<svg>`: the second link's fills are still handled per its own markup (depth resets per link).
- Input whose only SVG is self-closing and has no `</svg>`: unchanged after the first pass (the `</svg>` bail is kept).
- The card's text survives byte for byte, including `&amp;` and a raw `’`.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter RecipeMakerButtonsTest`
Expected to fail today, measured: the `&amp;` byte test (`mai_get_dom_html()` decodes it to a bare `&`). Expected to pass today: the self-closing `svg` test (libxml honours `/>`). Fix tests, not code, for anything else.

- [ ] **Step 3: Convert**

One walk with `tag_closers => 'visit'`. Set the in-button flag on an `A` opener whose class contains `button`, clear it on the `A` closer, and reset `svg` depth to 0 at both points. Increment depth on an `SVG` opener without `has_self_closing_flag()`, decrement on an `SVG` closer. On every opener while in a button and (the tag is `SVG` or depth > 0), change `fill` only when it is a string other than `none`. If you merge this with the existing pass above it, keep the `</svg>` bail.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter RecipeMakerButtonsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add lib/support/wp-recipe-maker.php tests/phpunit/integration/RecipeMakerButtonsTest.php
git commit -m "WP Recipe Maker: recolor button icons with the Tag Processor"
```

### Task 4: Icons and the dead deprecated code

**Files:**
- Modify: `lib/functions/icons.php:234-250` (`mai_get_svg()`), `:284-303` (`mai_get_svg_icon()`)
- Modify: `lib/functions/deprecated.php:28-52` (delete everything after `return $svg;` inside `mai_convert_svg_xmlns()`)
- Create: `tests/phpunit/integration/fixtures/icons/svgs/light/<name>.svg` (one per icon test, Font Awesome shaped: comment inside the `<svg>`, `viewBox`, an existing `class`, self-closing `<path/>`)
- Test: `tests/phpunit/integration/SvgIconsTest.php`

**Interfaces:**
- Consumes: `mai_add_classes()` (for `mai_get_svg()`'s `$class`), `tagClasses()`, the `mai_get_dir()` and `mai_icons_get_dir()` stubs from Task 1.

- [ ] **Step 1: Write the tests**

`mai_get_svg_file()` and `mai_get_svg_icon_file()` cache in `static`s, so use a distinct file name per test.
- `mai_get_svg( <real assets/svg name>, 'a  b' )`: first `svg` classes gain `a` and `b`; `viewBox` and any `/>` in the file survive byte for byte.
- `mai_get_svg_icon( $name, 'light' )`: `width="24"` and `height="24"`; `[ 'width' => '1em' ]` wins; `[ 'class' => 'x' ]` replaces the fixture's existing class (today's `setAttribute()` replaces, it does not add).
- Missing file returns `''`. A fixture file containing no `<svg>` returns its contents unchanged.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter SvgIconsTest`
Expected to fail today: the byte-preservation assertions (DOM writes `viewbox` and expands `/>`). Fix tests, not code, for anything else.

- [ ] **Step 3: Convert both functions and delete the dead code**

`mai_get_svg_icon()`: `next_tag( 'svg' )` once, then `set_attribute()` per att, so `class` keeps replacing.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter SvgIconsTest`

- [ ] **Step 5: Commit**

```bash
git add lib/functions/icons.php lib/functions/deprecated.php tests/phpunit/integration/SvgIconsTest.php tests/phpunit/integration/fixtures/icons
git commit -m "Icons: set SVG attributes with the Tag Processor, keeping viewBox intact"
```

### Task 5: Structure (entry pagination, comments, archive pagination, adjacent entry)

**Files:**
- Modify: `lib/structure/single.php:29-53`, `lib/structure/comments.php:44-88`, `lib/structure/pagination.php:87-163` and `:180-207`
- Test: `tests/phpunit/integration/StructureLinksTest.php`

**Interfaces:**
- Consumes: `mai_add_classes()` (for the pagination filter's return and `single.php`'s built string), `tagClasses()`.
- Produces: `mai_get_posts_nav_with_classes( string $pagination, string $type ): string` in `lib/structure/pagination.php`, `@access private`. `mai_posts_nav()` keeps buffering `genesis_posts_nav()`, the `alignleft` / `alignright` `str_replace`s and `mai_get_template_args()`, then echoes this function's result. It is extracted so the markup logic can be tested without Genesis.

- [ ] **Step 1: Write the tests**

Use markup copied from real output: Genesis numeric and prev-next nav (`genesis/lib/structure/post.php`, from any local site's Genesis), `wp_link_pages_link` output, `get_comment_reply_link()` and `get_cancel_comment_reply_link()` output, and `get_previous_post_link()` shape for adjacent entry.
- `mai_entry_pagination_button_class()`: the current link gets `button button-small`, not `button-secondary`; other links get all three.
- `mai_comment_reply_button_class()`: every `a` gets the three classes; an `a` with a valueless `class` gets them with no warning. `mai_comment_reply_link()`: first tag gets `cancel-comment-reply-link`; empty input returns empty.
- `mai_get_posts_nav_with_classes()`, numeric: the `a` in `li.active` gets `pagination-link button button-small` without `button-secondary`; the others also get `button-secondary`; an `a` outside any `li` is untouched. Prev-next: every `a` gets all four.
- Filter: returning `'one two'` gives tokens `one`, `two`; returning `[ 'one two', 'three' ]` gives all three; returning `null` adds nothing and doesn't fatal. The filter runs once per link with `( $classes, $active, $type )` (count calls).
- `mai_adjacent_entry()`: `go_to()` a post first so `is_singular()` is true. `adjacent-post-link` becomes `adjacent-entry-link-inner` and the first tag gets `adjacent-entry-link`. A title containing U+0092 comes back with U+0092, the intended C1 difference, pinned on purpose with a comment pointing at the spec.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter StructureLinksTest`
Expected to fail today: the C1 test (DOM repairs it to U+2019) and every `mai_get_posts_nav_with_classes()` test ("undefined function" until Step 3). To check the nav tests against today's behavior, run the same inputs once through 2.41.2's DOM logic in a scratch script and confirm the class sets match; the `'one two'`, array and `null` filter cases all work today (`mai_add_classes()` implodes, concatenates, and returns the existing classes for null). Fix tests, not code, for anything else.

- [ ] **Step 3: Convert, extracting `mai_get_posts_nav_with_classes()`**

The numeric walk uses `tag_closers => 'visit'` and tracks an open `li` and its `active` state. Drop the wrapper's no-op get-then-set of `class`.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter StructureLinksTest`

- [ ] **Step 5: Commit**

```bash
git add lib/structure/single.php lib/structure/comments.php lib/structure/pagination.php tests/phpunit/integration/StructureLinksTest.php
git commit -m "Structure: add pagination and comment link classes with the Tag Processor"
```

### Task 6: Shortcodes and WooCommerce loop start

**Files:**
- Modify: `lib/functions/shortcodes.php:487-569`, `lib/support/woocommerce.php:84-115`
- Test: `tests/phpunit/integration/ShortcodesTagProcessorTest.php`

**Interfaces:**
- Consumes: `tagClasses()`, `tagStyles()`, `$GLOBALS['mai_test_wc_loop_columns']` (reset it in `tear_down`).

- [ ] **Step 1: Write the tests**

- `mai_post_terms_shortcode_classes()`: `<span class="entry-terms">…</span>` with `taxonomy => 'category'` gains `entry-terms-category`; missing or empty `taxonomy` returns input unchanged with no warning; plain text returns unchanged with no error.
- `mai_gallery_shortcode_tag()`: with `columns => 4`, first tag's `tagStyles()` ends with `--gallery-columns-xs:1`, `-sm:2`, `-md:4`, `-lg:4` in that order (measured against `mai_get_breakpoint_columns()`); an existing `style="color:red"` (no `;`) stays its own declaration; a tag other than `gallery` and a non-default `type` return unchanged.
- `mai_product_loop_start_columns()`: `<ul class="products columns-4">` gets `--columns-xs` through `--columns-lg`, the output has no `</ul>`, and the `ul` is still open; `$GLOBALS['mai_test_wc_loop_columns'] = ''` returns unchanged.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter ShortcodesTagProcessorTest`
Expected to fail today: missing taxonomy (warning), empty taxonomy (today's guard passes `''` and adds `entry-terms-`), plain text (fatal), and the missing-`;` test. Fix tests, not code, for anything else.

- [ ] **Step 3: Convert all three**

Fix the taxonomy guard to `empty( $atts['taxonomy'] )`, add the `;` when missing, drop the `</ul>` `str_replace`.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter ShortcodesTagProcessorTest`

- [ ] **Step 5: Commit**

```bash
git add lib/functions/shortcodes.php lib/support/woocommerce.php tests/phpunit/integration/ShortcodesTagProcessorTest.php
git commit -m "Shortcodes and WooCommerce: set classes and styles with the Tag Processor"
```

### Task 7: Demo template image import

**Files:**
- Modify: `lib/functions/templates.php:542-722` (`mai_localize_blocks()`)
- Test: `tests/phpunit/integration/LocalizeBlocksTest.php`

**Interfaces:**
- None new. `mai_localize_blocks( $content )` keeps its signature and return.

- [ ] **Step 1: Write the tests**

Use the existing-attachment path only, never the download path. Create attachments with the factory including `'file' => '<name>.jpg'`, `'post_mime_type' => 'image/jpeg'` and a `post_name` matching the remote file's base name with sizes stripped. Without `file`, `wp_get_attachment_image_src()` returns false and `$new_img[0]` warns. WP_Query trims a trailing `-` left by the size-stripping regex, so `photo-300x200.jpg` matches `post_name` `photo`. Every fixture block carries `id` / `sizeSlug` (image) or `mediaId` / `mediaType` (media-text); also add one image block without `sizeSlug` and guard those reads with `?? ''` in the code.
- Image block with a remote `src` and `wp-image-5`: output has the local URL and `wp-image-<new id>`, and the block's `id` attr is the new ID.
- Media-text block first in the content (no image block before it), remote `src`, `mediaId` 5: output has the local URL, `wp-image-<new id>`, and `mediaId` is the new ID.
- Image block (remote, id 7) followed by media-text (remote, mediaId 5): each gets its own new URL, and the media-text's `mediaId` is its own new ID (this assertion is what fails today).
- Image block (remote) followed by a media-text block with no `<img>`: the media-text is left alone, and the image's URL replacement is not overwritten (today it takes the image's new ID and replaces its URL with the `large` size).
- Old IDs 12 and 123: each class rewritten exactly, `wp-image-123` never becomes `wp-image-<new>3`.
- Chained IDs: create attachments A and B first, then build two image blocks whose old IDs are A's real ID and an unused ID that maps to A. Write the content so block 1's old ID is the one that maps to A, and block 2's old ID equals A's ID and maps to B. Each image ends with its own new ID, and block 1 is not rewritten twice. (The factory can't choose IDs; `import_id` is an alternative.)
- Already-local URLs are left alone.

- [ ] **Step 2: Run against today's code**

Run: `composer test-integration -- --filter LocalizeBlocksTest`
Expected to fail today: the media-text tests, the `wp-image` tests and the missing-`sizeSlug` test.

- [ ] **Step 3: Convert and fix**

Read `src` with `next_tag( 'img' )` / `get_attribute()`. Media-text: reset `$image_id` / `$image_url` per block, read `mediaId`, write `attrs['mediaId']`, use `$new_id` for the new image, drop the `innerHTML` write-back. Collect old-to-new image IDs into one map and rewrite classes in a single pass next to the `$replace_urls` loop: `preg_replace_callback( '/\bwp-image-(\d+)\b/', fn( $m ) => isset( $map[ $m[1] ] ) ? 'wp-image-' . $map[ $m[1] ] : $m[0], $html )`. A loop of `preg_replace` calls would chain. Leave the `core/cover` branch as it is apart from what the shared variables need.

- [ ] **Step 4: Run to verify all pass**

Run: `composer test-integration -- --filter LocalizeBlocksTest`

- [ ] **Step 5: Commit**

```bash
git add lib/functions/templates.php tests/phpunit/integration/LocalizeBlocksTest.php
git commit -m "Demo import: localize media-text images and update image ID classes"
```

### Task 8: Full suites and the 6.4 floor

**Files:**
- Create (scratch, outside the repo): `/tmp/tp-floor/run.php`

- [ ] **Step 1: Confirm no DOMDocument callers remain except the header**

Run: `rg -n "mai_get_dom_document|mai_get_dom_first_child" lib`
Expected: only `lib/structure/header.php` and the definitions in `lib/functions/utilities.php`.

- [ ] **Step 2: Full suites**

Run: `composer test-unit`, `composer test-integration -- --order-by=random`, and the worktree `deployable-guard` check from Global Constraints.
Expected: Task 1's baseline counts plus the new tests, no new failures, guard passes on the committed tree.

- [ ] **Step 3: Run the converted functions against WordPress 6.4's Tag Processor**

`run.php` loads, in this order, `class-wp-html-span.php`, `class-wp-html-text-replacement.php`, `class-wp-html-attribute-token.php` and `class-wp-html-tag-processor.php` from `~/Local Sites/corporatepro/app/public/wp-includes/html-api/` (WordPress 6.4). Don't glob the directory: `class-wp-html-processor.php` sorts first and extends the Tag Processor. Add minimal stubs for whatever it asks for (`esc_url`, `wp_kses_uri_attributes`; stub `esc_attr` as `htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8', false )`, because core's doesn't double-encode and a plain `htmlspecialchars()` would report the opposite result), load renamed copies of the converted functions, and run the inputs from Tasks 2 to 6 that need no database. Compare with the suite's 7.x results by meaning.
Expected: identical meaning. Record in Results any escaping difference (the spec predicts `&amp;lt;` collapsing to `&lt;` on 6.4 for values that already hold entities) and whether real input could hit it. Nothing to commit.

### Task 9: Corpus diff (the gate)

**Files:**
- Create (scratch, outside the repo): `/tmp/tp-corpus/differ.php`, `/tmp/tp-corpus/old.php` and `/tmp/tp-corpus/new.php` (renamed copies of every converted function **and** of `mai_get_dom_document()`, `mai_get_dom_html()`, `mai_get_dom_first_child()` and `mai_add_classes()`, so neither side calls the site's own copies, which range from 2.38 to 2.41 on local sites and decode entities differently; `tp_old_*` from `git show 2.41.2:<file>` and `tp_new_*` from this branch, of every converted function), captured inputs under `/tmp/tp-corpus/inputs/<stand-in>/`

- [ ] **Step 1: Write the differ**

Per the spec's Verification section:
- **Safety first.** Before rendering anything, add a `query` filter that blanks `INSERT`, `UPDATE`, `DELETE` and `REPLACE` statements and counts them, and a `pre_http_request` filter returning a `WP_Error`. `the_content` runs oEmbed and can write post meta and transients. Never `wp cache flush`.
- **Capture.** Hook each converted filter at a priority just before Mai's, record its real input during `apply_filters( 'the_content', ... )` for every published post (WP Recipe Maker shortcodes included), and save the inputs to disk for Task 10.
- **Compare.** Run `tp_old_*` and `tp_new_*` on each captured input. Both are renamed copies on every site, so it works whether the site's mai-engine is a symlink to this repo or an older copy. Canonicalize both outputs with `Dom\HTMLDocument::createFromString()` and compare trees with class tokens as sets and style declarations as lists.
- **Non-block call sites.** Run directly: every Mai Icons SVG file and every `assets/svg` file, every term name through post terms, published post titles through adjacent-entry markup, and Genesis numeric and prev-next pagination, `wp_link_pages_link`, comment reply, cancel-reply and Woo loop-start shapes.
- **Unbalanced input.** Flag every input whose HTML5 parse and DOM output tree differ in element structure.
- **Classify** into intended (the spec's list; C1 and double-encoding counted separately), unbalanced, and other. Print per-site counts, the blocked-write count, and up to five post IDs per shape.

- [ ] **Step 2: Dry run on one small site, then all**

Run per site: `wp --path=<site> eval-file /tmp/tp-corpus/differ.php 2>/dev/null`, for every `~/Herd/*` site with mai-engine.
Expected: completes everywhere. Record in Results any site it can't run on and why.

- [ ] **Step 3: Gate**

If anything lands in "unbalanced" or "other": **stop**. Write the findings (shape, counts per site by stand-in name, example post IDs) to `docs-local/` and to Results, and bring them to Mike. Do not continue to Task 10 or the changelog until he decides. If everything is intended, record the counts and continue.

### Task 10: Profiling

**Files:**
- Create (scratch, outside the repo): `/tmp/tp-profile/bench.php`

- [ ] **Step 1: Per-call timings**

On content-site-a's local copy, using Task 9's saved inputs: run `tp_old_*` and `tp_new_*` for the group, button and paragraph filters and `mai_get_svg_icon()` 10,000 times each, three runs, and report median microseconds per call.

- [ ] **Step 2: Page timing**

Pick content-site-a's longest published article that triggers the converted filters (Task 9 knows which). Keep Task 9's write and HTTP blocks on. Time `apply_filters( 'the_content', ... )` 20 times on this branch. Then `git worktree add /tmp/tp-241 2.41.2`, repoint the site's `wp-content/plugins/mai-engine` symlink to `/tmp/tp-241`, time 20 more, restore the symlink to `~/Plugins/mai-engine`, confirm with `readlink`, and remove the worktree. Report median and spread for both.

- [ ] **Step 3: Record the numbers in Results.** Describe a gain only where the numbers show one.

### Task 11: Changelog, results and STATE

**Files:**
- Modify: `CHANGES.md`, `STATE.md`, this plan's Results section

- [ ] **Step 1: Changelog lines**

Add a section at the top headed `## 2.42.0 (unreleased)`, matching the `## X.Y.Z (MM/DD/YY)` shape; Mike sets the date, and may change the version, at release. Follow the existing shape: one short line per change, grouped Added, Changed, Fixed, tags in brackets. Plain, user-facing, no em-dashes. Cover: icons and block markup kept as written (SVG `viewBox` intact); the button class fix; the demo import media-text fix; `[Developers]` `mai_add_classes()` now drops repeated classes; `[Performance]` only if Task 10 measured a gain, with the number.

- [ ] **Step 2: Rewrite STATE.md** per its existing sections: this branch, what's done, the Task 9 and 10 results, next step (Mike reviews, then beta).

- [ ] **Step 3: Commit**

```bash
git add CHANGES.md STATE.md docs/plans/2026-11-16-dom-to-tag-processor.md docs/specs/2026-11-16-dom-to-tag-processor.md
git commit -m "Changelog, results and state for the Tag Processor migration"
```

## Results

Filled in during the build. Stand-in site names only.

- Baseline test counts (Task 1):
- Files that couldn't load bare (Task 1):
- 6.4 escaping differences (Task 8):
- Corpus diff per site (Task 9):
- Timings (Task 10):
- Extra bugs fixed along the way:
