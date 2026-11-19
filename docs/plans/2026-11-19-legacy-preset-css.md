# Strip core's legacy preset CSS

Small change, no spec. The design was decided for Mai Theme v3 in `~/Themes/mai-theme/docs/specs/2026-10-20-mai-theme-v3.md`, section "theme.json", paragraph "Core's legacy preset classes are stripped too". The reviewed code already lives in `~/LocalPackages/mai-theme-core/src/legacy-css.php` (namespaced, PHP 8.4). This ports it to Mai Engine: procedural, `mai_` prefixed, WordPress coding standards, PHP 8.1 and WP 6.4 floors, `@since 2.42.0`.

## What core does (read on disk, test WordPress 7.0.2)

- `wp-includes/default-filters.php:648` hooks `wp_maybe_inline_styles` on `wp_head` at 1, and `:355` hooks `wp_print_styles` at 8.
- `wp-includes/script-loader.php:3089` `wp_maybe_inline_styles()` reads each queued style that has a `path`, smallest first, up to the `styles_inline_size_limit` (40,000 bytes), sets its `src` to `false` and `array_unshift`s the file's text onto `extra['after']`.
- `script-loader.php:1820` gives `wp-block-library` the path `block-library/common$suffix.css` when `wp_should_load_separate_core_block_assets()` is true. Since 6.9 core turns that on for classic themes too (`script-loader.php:3710`, from `wp_load_classic_theme_block_styles_on_demand` on `wp_default_styles` at 0). Before 6.9 a classic site loads the full `style.css`, which is too big to inline, so nothing changes there.
- `block-library/common.css` lines 116 to 171 hold the 15 rules: four `:root .has-very-{light,dark}-gray(-background)-color`, seven `:root .has-<name>-gradient-background`, and four `.has-{regular,larger,normal,huge}-font-size`. `common.min.css` holds the same 15.
- Mai's own presets do not use these names: font sizes are `xs` to `xxxxl` (`lib/functions/fonts.php`), and theme gradients have their own slugs (`config/prosper.php`, `config/studio.php`).

## Build

- `lib/functions/enqueue.php`, after `mai_reorder_core_block_styles()` (the existing place Mai adjusts core block CSS on `wp_head`):
  - `mai_strip_legacy_block_css( $css )`: the mai-theme-core function as is. Exact selectors, a rule must start the string or follow a `}`, `[^{}]*` stops at a nested rule, and the original text comes back when `preg_replace()` fails or the `{` and `}` counts differ.
  - `mai_remove_legacy_block_css()` on `wp_head` at 2: strips only core's inlined copy, which is `extra['after'][0]` when `src` is false, `inlined_src` is set and `path` ends in `block-library/common(.min).css`. CSS a plugin or child theme adds to the handle is left alone (review finding, see below).
- No filter. A site that wants the classes back can `remove_action( 'wp_head', 'mai_remove_legacy_block_css', 2 )`.

## Tests (TDD)

- Unit `tests/phpunit/unit/LegacyBlockCssTest.php`, against copies of the test WordPress 7.0.2 `common.css` and `common.min.css` in `tests/phpunit/unit/fixtures/block-library/`: all 15 rules gone, `.has-border-color`, `.screen-reader-text` and `.wp-element-button` kept, braces balanced, shorter. Edge cases from mai-theme-core: a nested rule left untouched, a plugin gradient kept, a selector list kept, a nested rule after a sibling removed with braces balanced, empty string, neighbours kept.
- Integration `tests/phpunit/integration/LegacyBlockCssTest.php`, against the real core file: enqueue `wp-block-library`, run `wp_maybe_inline_styles()` and `do_action( 'wp_head' )` up to priority 2 in effect, check the inline copy has none of the 15 and still has `.has-border-color`. This one fails when a new WordPress changes the rules, which is the signal to look again.

## Decisions

None needed walking. The design, the code and the tests were settled in the Mai Theme v3 spec and review. Placement follows `mai_reorder_core_block_styles()`.

## Results

Inline `wp-block-library` CSS on the `nonprofit-site-a` homepage (WP 7.0, separate block assets on), measured with curl on 2026-11-19:

- **Before:** 7,411 bytes, 15 legacy classes, 52 `{` and 52 `}`.
- **After:** 5,944 bytes (1,467 fewer), 0 legacy classes, 37 `{` and 37 `}`, `.has-border-color` still there.
- **What went:** a line diff of the two shows only removals, exactly the 15 rules. The 2026-10-20 throwaway plugin measured 5,964. Why it kept 20 more bytes is not checked; that plugin is gone, and a likely cause is whitespace between rules.

Production sites load `common.min.css`, so they save less. Run through the function: `common.css` 7,344 to 5,877 bytes (1,467 fewer), `common.min.css` 3,577 to 2,450 (1,127 fewer). `nonprofit-site-a` has `SCRIPT_DEBUG` on, which is why it inlines the unminified file.

Tests: unit `LegacyBlockCssTest` 9 tests, integration `LegacyBlockCssTest` 4 tests, both red before the code and green after.

Review (subagent, 2026-11-19):
- The first version stripped every string in `extra['after']`, so a plugin's own `.has-huge-font-size` added inline to the handle was removed too, even when core linked the file. Fixed: only core's copy at index 0 is touched. Core's comment at `script-loader.php:3748` confirms the 6.9 hoist placeholder sits after it. Both third-party tests now carry legacy classes, so they fail on the old code.
- The changelog said 1.5 KB, the unminified number. Now about 1 KB, and it says old posts using these classes lose those sizes and colors.
