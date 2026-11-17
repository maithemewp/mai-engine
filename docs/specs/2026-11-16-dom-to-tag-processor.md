# Replace DOMDocument with WP_HTML_Tag_Processor

Status: agreed design, 2026-11-16, revised after an adversarial review the same day. Next step is a plan in `docs/plans/`.

## Intent

Mai Engine sends HTML through `mai_get_dom_document()` / `mai_get_dom_html()` (`lib/functions/utilities.php`) at 19 call sites in `lib/`, almost all of which only read or change an attribute on one or a few elements. DOMDocument parses with libxml's HTML4 parser, which re-serializes the whole fragment: it lowercases SVG attributes (`viewBox` becomes `viewbox`, measured on a Mai Icons SVG), expands self-closing tags (`<path/>` becomes `<path></path>`), rewrites quoting and entities, balances unbalanced markup, and needs the selective entity decode documented above `mai_get_dom_html()`. `WP_HTML_Tag_Processor` follows HTML5 tokenizing rules and changes only the bytes it is told to change.

The goal is correct output first and less work in the render filters second. The speed gain is unmeasured and must be measured, not claimed.

This came out of a performance session on content-site-a (stand-ins are mapped in `docs-local/site-key.md`).

## Goals

- Every call site that only reads or writes attributes uses `WP_HTML_Tag_Processor` instead of DOMDocument. That is 18 of the 19.
- Output for real content renders the same as today, proven by a corpus diff, except for the intended differences listed below.
- Before and after timings for the converted render paths, reported as measured numbers.

## Non-goals

- Changing `mai_get_dom_document()`, `mai_get_dom_html()` or `mai_get_dom_first_child()`. They stay as they are. `mai-custom-content-areas`, `mai-table-of-contents` and `mai-url-parameter-content` call `mai_get_dom_html()` when Mai Engine is active, and `DomEncodingTest` pins their behavior.
- Rewriting call sites beyond the conversion. Each one keeps today's behavior except for the bugs listed under "Bugs fixed in this work".
- `~/LocalPackages/mai-dom` or PHP 8.4's `Dom\HTMLDocument` in shipped code. (The corpus differ may use `Dom\HTMLDocument` as a scratch tool; the local CLI is PHP 8.4.)

## Anti-goals

- Any post whose rendered markup structure changes, or whose text changes in a way not listed under "Intended output differences", without Mike seeing it first.

### Legacy C1 and double-encoded text

The July encoding work found that the DOM round trip accidentally repairs broken legacy data: stray CP1252 bytes stored as U+0092 become proper apostrophes through libxml's C1 remapping, and double-encoded text (`&amp;period;`) decodes one level (`docs/specs/2026-07-30-wordpress-phpunit-suite-design.md`, "What the corpus disqualified"). The Tag Processor never re-serializes, so converted call sites stop doing this.

That is accepted (Mike, 2026-11-16). The data is broken in the database, and every path that doesn't go through DOMDocument already shows it as stored. Core doesn't repair it either: `convert_chars()` on `the_title` only touches `&`. On news-site-c, 697 published post titles contain a C1 character. Those titles already show the raw character in the post's own heading, archives and grids; only the prev/next links on neighbouring posts get repaired today. After this change those links match the rest of the page. The July corpus figure (12,257 posts) came from running the helpers over whole `post_content`, which overstates what a real render sends through these filters.

### Unbalanced markup (gate item)

libxml also balances every fragment it parses, and the converted filters see whole fragments: `group.php` parses the entire group including inner blocks, and `wp-recipe-maker.php` parses a whole recipe card. Measured with today's helpers:

- `<div class="g"><div class="i"><p>x</p></div></div></div><p>after</p>` comes back as `<div class="g"><div class="i"><p>x</p></div></div>`. The stray `</div>` closes `mai_get_dom_document()`'s wrapper, and **everything after it is dropped from the page**.
- `<div class="g"><div class="ad"><p>x</p></div>` comes back with the missing `</div>` added.

So today, broken Custom HTML or ad code inside a group with a background silently loses content or gets closed. After the change, the raw markup reaches the browser: hidden content reappears, and a stray closer can end the group wrapper early. This is not accepted in advance. The corpus differ must count, per site, every converted-filter input that is unbalanced (or whose DOM output tree differs from an HTML5 parse of the raw input), and those go to Mike as a gate item.

## Constraints

- Floors from the plugin header: WordPress 6.4, PHP 8.1. Every Tag Processor method used exists in 6.4 (reviewed against the 6.4 source): `next_tag()` with `tag_closers`, `is_tag_closer()`, `get_tag()`, `add_class()`, `remove_class()`, `get_attribute()`, `set_attribute()`, `remove_attribute()`, `get_updated_html()` (6.2), `has_self_closing_flag()` (6.3), `class_list()` and `has_class()` (6.4).
- `set_attribute()` escapes differently by version:
  - **6.4.x:** `esc_attr()` on every value (`class-wp-html-tag-processor.php:2074` in a 6.4 tree). `esc_attr()` doesn't double-encode, so a get-then-set of `style="a:&amp;lt;b"` collapses to `a:&lt;b`.
  - **6.6 to 6.8:** `esc_url()` on URI attributes such as `src`, `esc_attr()` otherwise. 6.5 not checked.
  - **6.9 and later:** `esc_url()` on URI attributes, otherwise a `strtr` that escapes every `&`, so values round-trip.
  - The integration suite is pinned to WordPress 7.x (`tests/composer.json`), so it sees only the last regime. 6.4 and 6.4.1 trees exist on disk under `~/Local Sites/` (corporatepro, businesspro) for checking the converted cases against 6.4.
- `get_attribute()` returns `null` when the attribute is absent and `true` when it is present without a value (`<div style>`). DOM returned `''`. Every read of `class`, `style` or `src` needs an `is_string()` guard before string work.
- `add_class()` takes one class name. Today's inputs are often space-separated strings or arrays (`mai_add_classes()` accepts both, `helpers.php:1181`), including the public `mai_archive_pagination_link_classes` filter and `mai_get_svg()`'s free-form `$class`. Decided with Mike (2026-11-16): no new helper. Fixed class names get one `add_class()` call each; free-form input goes through `mai_add_classes()`, read-modify-write on the `class` attribute. `mai_add_classes()` itself changes to split on whitespace with `preg_split( '/\s+/' )` and drop repeats with `array_unique()`, so every caller de-duplicates (measured 0.64 µs per call; removing a repeated class never changes rendering). It has 52 callers in `lib/`, and five other mai plugins call it too.
- When the Tag Processor adds an attribute that wasn't there, it inserts it right after the tag name (`<ul style="..." class="...">`), where DOM appended it. Harmless, but tests and the differ must not compare attribute order.
- Existing files are procedural, unnamespaced, WordPress coding standards. Match them. Don't add `declare(strict_types=1)` to existing files.
- The existing Tag Processor uses (19 instantiations, e.g. `lib/blocks/general.php`, `settings.php`, `cover.php`, `search.php`, `site-logo.php`, `lib/support/learndash.php`, `woocommerce.php:280`, `wp-recipe-maker.php:78`) are all written inline with no helper. Follow that.
- Branches deploy as-is from committed `vendor/`. Never commit a with-dev autoloader. Leave `vendor/composer/installed.php` and the other autoload files out of feature commits.
- The working tree on `develop` has uncommitted changes to `TODO.md` and `lib/functions/images.php` that are not part of this work. Leave them alone.
- Never write a real client site name into the repo. Use the stand-ins.
- No push, tag or release without Mike's explicit yes.

## Approaches considered

- **A. Inline Tag Processor at each call site.** Chosen by Mike. Matches every existing use. Each site is a few lines, and the sites differ in small ways (prepend vs append to `style`, class removal in buttons), so a shared helper would grow options for every case.
- **B. A small shared helper** such as `mai_set_first_tag_attributes( $html, $classes, $style )`. Rejected: it adds a second pattern next to the existing inline ones, and the odd cases would still be inline.
- **C. Swap the internals of `mai_get_dom_document()`.** Rejected: callers receive a `DOMDocument` and use its API, so the return type can't change.

## Decided

**Scope.** Convert every call site below. That includes the cold ones (demo template import, WooCommerce loop start), so nothing is left half-migrated.

**One exception: the header scroll logo stays on DOMDocument.** `mai_custom_scroll_logo()` (`lib/structure/header.php:404`) appends the scroll logo `<img>` inside the first element of the `get_custom_logo` output. That first element is not always an `<a>`: with "unlink homepage logo" on, core wraps the front page logo in `<span class="custom-logo-link">` (`wp-includes/general-template.php:1326`), and other plugins can filter `get_custom_logo` earlier. The Tag Processor cannot insert markup or expose the position of a closing tag without subclassing into private API, and a string splice would misplace the logo. It runs once per page on a tiny fragment, so there is nothing to gain. Leave it unchanged.

**The corpus diff is a gate.** If it shows any change outside "Intended output differences", or any unbalanced input (see "Unbalanced markup"), stop and bring Mike the posts and the change shape before anything ships. He decides then whether to accept it, keep DOMDocument for that call site, or add a repair step. C1 and double-encoding differences are intended; report their counts per site, but they don't stop the work.

### Call sites

Line numbers are from 2.41.2. "First tag" means the first `next_tag()` match, which skips text and comments the way `mai_get_dom_first_child()` does (a leading `<style>` is returned by both, so they match).

- **`lib/blocks/paragraph.php:116` and `lib/blocks/heading.php:98`.** Add `has-no-margin-{side}` to the first tag. Only runs when Mai's `contentAlign` is start or end.
- **`lib/blocks/group.php:52`.** On the first tag, prepend `--group-block-justify-content:{value};` to `style` when aligned, and add `has-{light|dark}-background` when there is a background color.
- **`lib/blocks/button.php:107`.** On every `div`, rebuild `style` as today (split on `;`, trim, append the custom properties, drop empties, join with `;` plus a trailing `;`, remove the attribute when empty). On every `a`, remove the same color classes with `remove_class()` and add `button-small` / `button-large` as today. See "Bugs fixed in this work".
- **`lib/structure/single.php:30`.** Entry pagination link: add `button`, `button-secondary` (unless the class contains `current`) and `button-small` to the first tag.
- **`lib/structure/comments.php:45` and `:76`.** Reply link: add `button`, `button-secondary` and `button-small` to every `a`. Cancel link: add `cancel-comment-reply-link` to the first tag.
- **`lib/structure/pagination.php:101`.** Archive pagination. Numeric: walk with `tag_closers => 'visit'`, track `li` open and close, note whether the open `li`'s class contains `active`, and add the link classes to each `a` inside an `li`. Prev-next: add them to every `a`. Call `mai_archive_pagination_link_classes` once per link with the same arguments as today, and pass what it returns through `mai_add_classes()` (see Constraints). The current get-then-set of the wrapper's class is a no-op and can go. Genesis's numeric markup keeps every `a` inside an `li`, so this matches today.
- **`lib/structure/pagination.php:189`.** Adjacent entry: keep the `str_replace`, add `adjacent-entry-link` to the first tag.
- **`lib/functions/shortcodes.php:496`.** Post terms: add `entry-terms-{taxonomy}` to the first tag. Return the output unchanged when there is no tag. Fix the taxonomy check (see "Bugs fixed in this work").
- **`lib/functions/shortcodes.php:540`.** Gallery shortcode: append the `--gallery-columns-{break}` properties to the first tag's `style`, adding a `;` first when the existing value doesn't end in one.
- **`lib/support/woocommerce.php:91`.** Product loop start: append the `--columns-*` properties to the first tag's `style`. Drop the trailing `str_replace( '</ul>', '', ... )`, which only removed the `</ul>` DOMDocument added when it auto-closed the open `<ul>`. The Tag Processor adds nothing.
- **`lib/support/wp-recipe-maker.php:111`.** Inside every `a` whose class contains `button`, set `fill="currentColor"` on each `svg` and each element inside it, but only where that element's own `fill` is set and not `none` (as `mai_wprm_dom_convert_svgs()` does today). One pass with `tag_closers => 'visit'`: set the "in a button link" flag on the `a` opener and clear it on the `</a>` closer; count `svg` depth on `svg` openers and closers only, and don't increment for an `<svg .../>` with `has_self_closing_flag()`, or the fill leaks onto everything after it. Merging this into the existing Tag Processor pass above it is the builder's call.
- **`lib/functions/icons.php:235` (`mai_get_svg()`) and `:285` (`mai_get_svg_icon()`).** Add the class through `mai_add_classes()` / set each attribute on the first `svg` tag. `mai_get_svg_icon()` runs for every icon on a page, so it is a hot path. Mai Icons files carry the Font Awesome comment inside the `<svg>`, after the opening tag.
- **`lib/functions/templates.php:597` and `:651`.** Demo template image import. Read `src` from the first `img` with the Tag Processor. Drop the media-text branch's `innerHTML` write-back, which has no effect (see "Bugs fixed in this work").
- **`lib/functions/deprecated.php:37`.** Delete the unreachable code after `return $svg;` in `mai_convert_svg_xmlns()`. Keep the function.

### Intended output differences

These are expected and are not gate failures, as long as the page renders the same:

- Markup outside the changed attribute is left byte for byte: attribute case (`viewBox`), quoting, self-closing tags, whitespace and entities in text stay as written, and the trailing `\n` `saveHTML()` added is gone.
- New attributes land right after the tag name instead of last.
- Classes already present are not added a second time, anywhere `mai_add_classes()` or `add_class()` is used.
- Attribute values are escaped by the Tag Processor instead of `mai_add_classes()`'s `esc_attr()` plus DOM serialization.
- Button color classes are removed as whole names (see "Bugs fixed in this work").
- Legacy C1 characters and double-encoded entities pass through as stored instead of being repaired (see "Legacy C1 and double-encoded text"). Where this shows up: prev/next links (neighbouring post titles), post terms shortcode output (term names), gallery captions, WP Recipe Maker cards that contain an SVG (the whole card is parsed today), and content inside aligned paragraphs and headings, styled buttons, and groups with an alignment or background.

## Verification

- **Tests first, asserting meaning, not bytes.** For each converted function, write integration tests (the WordPress suite has the real Tag Processor) before converting. Assert parsed semantics on the target tag: class tokens as a set, style declarations as a list, `src` and `fill` values. Add a few checks that untouched bytes survive (an SVG's `viewBox` and `<path/>`, a quoted attribute elsewhere in the fragment). Pinning today's serialized bytes would force every pin to be rewritten after the conversion. Cover at least: class and style present, absent and valueless; space-separated and array class input; multiple `div` / `a` in a button; numeric pagination with an active item; prev-next pagination; a Recipe Maker button with nested SVG, `fill="none"` children and a self-closing `<svg/>`; icon attributes on an SVG with the Font Awesome comment inside it; post terms with no tag; the media-text branch with and without an image block before it.
- **The 6.4 floor.** Run the converted functions' cases against the 6.4 Tag Processor source on disk (see Constraints), not only the suite's 7.x, and note any escaping difference.
- **Existing suites pass:** `composer test-unit`, `composer test-integration -- --order-by=random`, `php vendor/bin/deployable-guard check`. `GridCacheAfterPageTest` fails 2 tests on some MySQL/MariaDB versions regardless, so it is not new.
- **Corpus diff (the gate).** A scratch script outside the repo, read-only, run with `wp eval-file` per local site that has mai-engine, `2>/dev/null`. Shape it on `docs/plans/2026-07-30-wordpress-phpunit-suite.md` Task 10, with these differences:
  - **Real filter input.** Capture what each converted filter actually receives during a render (hook the filters while rendering posts through `the_content`, including recipe shortcodes), not raw `innerHTML`. A group's `innerHTML` lacks its rendered inner blocks.
  - **Old and new side by side.** Run a renamed scratch copy of the old function and the new one on the same captured input.
  - **Canonicalize both outputs** with the same HTML5 parser (`Dom\HTMLDocument` on the PHP 8.4 CLI), then compare trees, with class tokens as sets and style declarations as lists. Raw byte comparison flags every fragment.
  - **Non-block call sites.** Exercise them directly: every Mai Icons SVG file through `mai_get_svg_icon()` and every `assets/svg` file through `mai_get_svg()` (exhaustive, and the hot path), every term name through post terms, published post titles through the adjacent-entry markup, and representative Genesis pagination, comment reply and Woo loop-start markup.
  - **Report** per site: change shapes with counts and example post IDs, C1/double-encoding counts, and unbalanced-input counts. Anything outside the intended differences, and any unbalanced input, stops the work and goes to Mike.
- **Profiling.** On content-site-a's local copy (its mai-engine is a symlink to this repo), time a long article with many blocks that actually trigger the converted filters, before and after. Also time old against new per call on the same inputs, since most paragraphs never reach the parse, and time `mai_get_svg_icon()` per call. Report the numbers. Don't describe a gain that wasn't measured.

## Bugs fixed in this work

Mike's call (2026-11-16): bugs found in the lines this work touches get fixed here, each with a test.

- **Button color classes (`lib/blocks/button.php`).** Today `str_replace` removes `has-text-color`, `has-background` and the `has-{slug}-color` / `has-{slug}-background(-color)` classes as substrings, so a longer class containing them is mangled (`has-background-blur` becomes `-blur`) and stray spaces are left behind. Remove them as whole class names with `remove_class()`.
- **Demo import media-text (`lib/functions/templates.php`, `mai_localize_blocks()`).**
  - The branch gates on `$image_id` (`:651`), which it never sets. It carries over from an earlier `core/cover` or `core/image` iteration of the same `foreach`, so the branch is skipped unless such a block came first. When the media-text has no `<img>`, the carried-over `$image_url` is used too. Fix: read `mediaId` and the block's own `img` `src`, and reset the variables per block.
  - It writes `attrs['id']` (`:670`, `:683`), but the media-text attribute is `mediaId`, so the block keeps the demo site's ID. Write `mediaId`.
  - When it creates a new image it passes `$existing` (false at that point) instead of `$new_id`, so the new URL is empty. Use `$new_id`.
  - Its `innerHTML` write-back has no effect, because `serialize_blocks()` reads `innerContent`. The `$replace_urls` string replace is what changes the output, so drop the write-back and test through `$replace_urls`.
  - In both the image and media-text branches, the `img`'s `wp-image-{old id}` class is never updated. WordPress uses that class to find the attachment for `srcset`. Replace it with `wp-image-{new id}` the same way `$replace_urls` replaces URLs.
- **Gallery style (`lib/functions/shortcodes.php`, `mai_gallery_shortcode_tag()`).** Appends the column properties without checking for a trailing `;`, so an existing `style` without one is glued to the first property. Add the `;` when missing.
- **Post terms shortcode (`lib/functions/shortcodes.php`, `mai_post_terms_shortcode_classes()`).** The guard `! ( isset( $atts['taxonomy'] ) || $atts['taxonomy'] )` reads the key when it is not set. Use `empty( $atts['taxonomy'] )`. The missing-element fatal goes away with the conversion. Neither is reachable with Genesis's own output, which always sets `taxonomy` and always wraps in a `span`.

Any other bug found during the build: fix it if it is in a line being converted and small, with a test, and list it in the plan's results. Anything larger goes to Mike first.

## Left to the builder

- Exact test fixtures and how many per function.
- Whether the Recipe Maker SVG pass merges with the existing button pass.
- Corpus differ internals, within the requirements above.
- Changelog wording. This rides whatever the next release is. Mike decides the version and timing.
