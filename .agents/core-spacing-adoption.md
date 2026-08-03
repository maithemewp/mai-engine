# mai-engine — Adopt core's spacing scale (TODO / feasibility)

**Repo:** maithemewp/mai-engine · **Branch:** develop
**Created:** 2026-08-02 · **Status:** not started, exploratory

## Goal

Get Mai more in line with WordPress core by using core's spacing presets (`--wp--preset--spacing--*`, the Dimensions panel's padding/margin/blockGap UI) instead of, or alongside, Mai's own `--spacing-*` scale.

## Hard constraint

**Existing sites must render identically after upgrade.** Every theme currently ships specific spacing values and customers have those values serialized into block attributes and options. Any adoption path that changes computed pixel values on existing content is a non-starter. This is the whole difficulty of the task; the theme.json edit itself is trivial.

## Current state

Mai's scale lives in `assets/scss/base/_globals.scss:93-104`:

```scss
--spacing-base:  16px;
--spacing-xxxxs: 2px;
--spacing-xxxs:  4px;
--spacing-xxs:   6px;
--spacing-xs:    8px;
--spacing-sm:    12px;
--spacing-md:    var(--spacing-base);
--spacing-lg:    calc(var(--spacing-md)  * var(--spacing-scale));
--spacing-xl:    calc(var(--spacing-lg)  * var(--spacing-scale));
--spacing-xxl:   calc(var(--spacing-xl)  * var(--spacing-scale));
--spacing-xxxl:  calc(var(--spacing-xxl) * var(--spacing-scale));
--spacing-xxxxl: calc(var(--spacing-xxxl)* var(--spacing-scale));
```

Two things matter here:

1. **12 steps, not 7.** Core ships 7 (`spacingScale` slugs 20/30/40/50/60/70/80). Mai has `xxxxs` through `xxxxl`. There is no lossless slug mapping.
2. **The top half is multiplicative and per-theme.** `lg` and up are derived from `--spacing-scale`, which each theme sets. Core's presets are fixed values. So "the same slug" means different pixels on different Mai themes, which core's model cannot express.

Core's scale is currently suppressed by `mai_remove_default_theme_json_presets()` in `lib/functions/theme-json.php`, which zeroes `spacingScale.default.steps` (emptying `spacingSizes` alone is not enough, since the presets are generated from the scale).

## Where Mai's spacing is written into content

This is the surface area that would need migrating if we went the naive route:

- `lib/blocks/settings.php:245-246` — `spacingTop` / `spacingBottom` block attributes
- `lib/functions/columns.php:137-138` and `lib/classes/class-mai-columns.php:270-271` — column/row gap, `sprintf( 'var(--spacing-%s)', $args['column_gap'] )`
- `lib/functions/helpers.php:221` — `mai_is_valid_size()`, the canonical list of size keys
- `assets/scss/utilities/_misc.scss:194+` — `.has--{size}-margin-top` / `-bottom` utility classes
- `config/*.php` — per-theme spacing defaults (achieve, lookbook, homestead, pure, prosper, studio, and others)
- ACF field values for grid/columns/page-header settings

## Promising approach: alias, don't migrate

The realization that makes this tractable: **we probably don't need to touch content at all.**

Instead of migrating Mai's values to core's slugs, define core's `spacingSizes` presets so they *resolve to* Mai's variables:

```php
'--wp--preset--spacing--50' => 'var(--spacing-lg)'
```

Mai's `--spacing-*` stays the rendering source of truth, so existing content and per-theme `--spacing-scale` keep working untouched. Core's UI becomes usable and writes core slugs, which resolve back through the same scale. New content uses core's model, old content keeps rendering byte-identically.

Open questions on this path:

- Which 7 of Mai's 12 steps do core's slugs alias to? The tails (`xxxxs`, `xxxxl`) have no core equivalent and would stay Mai-only.
- Does the Dimensions UI preview correctly when a preset value is a `var()` rather than a literal? Needs testing in the editor; the slider/visualizer may not resolve it.
- `blockGap` interaction with `lib/functions/columns.php`, which sets gaps directly rather than through core's mechanism.
- Whether enabling core's spacing UI on blocks Mai already provides custom controls for creates a confusing double-control situation.

## Next step (cheap, do this first before scoping the rest)

Audit how many sites/posts actually have Mai spacing values serialized, and in which of the surfaces listed above. That number decides whether this is an alias-and-ship job or a real migration with a deprecation window. Don't scope the full project before that count exists.

## Related

- The aspect ratio regression (2.40.0, fixed in 2.41.0) came from the same `mai_remove_default_theme_json_presets()` function. Lesson worth carrying: theme.json settings are not only CSS var sources, they are also the data the editor builds its controls from. Check the editor UI before removing any preset group.
