# Custom image width and CSS browser target

Follow-up to issue #677 (`docs/plans/2026-11-20-entry-image-shape.md`). Both decisions were walked with Mike on 2026-11-20 against the local test page (a demo site, page "Entry Image Shape Test", built by a scratch script in `/tmp/entry-shape-page`).

## Decisions

- **Left Full and Right Full with an uncropped size:** no forced shape. Each photo keeps its own shape, as #677 committed.
- **Center with an orientation:** keeps #677's crop to the chosen shape.
- **Custom image width:** a custom image size on the left or right uses the Image Width setting (¼, ⅓, ½) like every orientation, but never shows wider than the size's own width. Before, the grid block hid Image Width for Custom and the image was as wide as the size (Large up to 1024px), which squeezed the text into a strip. The Customizer archive settings already showed Image Width for Custom, but it did nothing.
- **CSS browser target:** Baseline widely available, matching the modern CSS rule, in place of browserslist's defaults, which still include Opera Mini and add prefixes such as `-o-object-fit`.

## Task 1: Custom image width

- `lib/functions/entries.php`, `mai_do_entries_open()`: on the left or right, a custom size's `--entry-image-link-max-width` becomes `min(<size width>px,<share>)`. Above the text (full, center) it stays the size width. Orientations are unchanged.
- `lib/fields/grid-display.php`: Image Width no longer hides when the orientation is Custom.
- Test: `tests/phpunit/unit/EntryImageWidthTest.php`.
- `CHANGES.md`: one Changed line, and the #677 `[Developers]` line. Tagged lines sit together inside each group.
- Existing Custom grids on the left or right never saved Image Width, so they pick up the default (⅓ in the default config). That only changes grids whose size is wider than that share.

## Task 2: CSS browser target

- `package.json`: a `browserslist` of `baseline widely available`, and browserslist updated so it knows that query, with fresh browser data.
- Rebuild every CSS bundle with the gulp tasks (never `npm run dev`). Check that every change in the built files is a vendor prefix that went away, nothing else.

## Results

### Task 1

- Unit: 303 tests, 11 skipped, all passing (`EntryImageWidthTest`: 4 tests). Integration in random order: 823 tests, seed 1795207382, all passing.
- Test page, headless Chromium, 1140px wide content, after the change:
  - **Custom Large, Left Full, ⅓, 1 column:** every image 380px wide (was 1024px with a squeezed text column). Each photo keeps its own shape.
  - **Custom Thumbnail, Left Top, ⅓, 2 columns:** every image 150×150, the size width, since a third of the card is wider.
  - **Custom Tiny, Left Top, ⅓, 2 columns:** every image 80×80, including the six with no Tiny crop. A 60×40 original shows at 60×60: the right shape, never enlarged.
  - **Custom Large Full, Custom Medium Center, Custom Large Background:** unchanged.
