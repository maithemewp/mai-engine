# Entry images keep their chosen shape

Issue #677. Short design, agreed with Mike on 2026-11-19 and 2026-11-20, reviewed three times by subagents against the code.

## Why

Entries take their shape from the cropped image file. When that crop doesn't exist, WordPress serves the full image scaled down to fit, so the card takes the photo's own shape and comes out taller or shorter than the cards next to it. Crops are missing for every image uploaded before Mai Engine registered its sizes, so every site that moves to Mai Engine starts this way. On one migrated site 28,607 of 29,066 featured images had no landscape crop.

## Goal

Wherever a cropped shape is chosen, the image shows in that shape whatever file WordPress picked.

## What visibly changes

- **Missing crops** take the chosen shape, trimmed by `object-fit: cover` the way a real crop would be.
- **Single posts change the most.** Their orientation defaults to landscape (`config/_default.php`, `single-content`), so every single featured image without a matching crop changes shape.
- **Some real crops change too.** When an original is smaller than the crop in one dimension, WordPress still crops it but keeps the smaller side (`wp-includes/media.php`, `image_resize_dimensions()`), so a 400×1000 upload against 768×432 becomes about 400×432 and is now trimmed to the full shape. Originals smaller than the crop in both dimensions get no crop at all and are now trimmed.
- **Themes that remove an orientation's sizes but still offer it** (Twenty-seven removes the `landscape-*` sizes) now get that orientation's shape everywhere, since no crop ever exists there.

## Non-goals

- Generating the missing crops. Sites can still run `wp media regenerate --only-missing`, which also gives smaller downloads.
- The image's width in positions with an automatic width (center, and left or right top and bottom). There the image keeps the width of the file WordPress served, which with a missing crop can be narrower than the crop (about 455px instead of 512px for `landscape-sm`). The shape is right; the width gap stays.

## Anti-goals

- A global `.entry-image` aspect ratio rule. Any `aspect-ratio` value from CSS, `auto`, `unset`, `initial` and `revert` included, replaces the ratio the browser takes from the width and height attributes, and a lazy image with `sizes="auto"` then collapses (measured in Chromium, Firefox and WebKit on 2026-11-20: 300×450 became 300×150). Only `revert-layer` inside a cascade layer avoids that; Mike chose not to add Mai's first cascade layer for this.
- Changing any setting that should not force a shape (see "Must keep working as today").

## When a shape is forced

One helper answers "the forced ratio for these args, or none". `Mai_Entry` calls it:

- The chosen orientation has a ratio in the `image-sizes` config (`mai_has_image_orientiation()` plus `mai_get_aspect_ratio_from_orientation()`). Orientation sizes are always registered with crop on (`lib/functions/setup.php`).
- Or the orientation is `custom` and the chosen image size is registered with crop on. Read the size data from `mai_get_available_image_sizes()`: core sizes carry `get_option( "{$size}_crop" )` (the string `'1'` or false), added sizes carry `$crop` as registered (a bool or a position array). Force a shape only when crop is truthy and width and height are both above 0. Never use the `'4/3'` that `mai_get_image_aspect_ratio()` returns for an unknown size.
- Never for `masonry`, and never for an uncropped size.
- The `image_size` it checks is the setting, not what the `mai_entry_image_size` filter returned. `Mai_Entry` overwrites `$this->args['image_size']` with the filtered size (`class-mai-entry.php`, constructor), so capture the setting before that. Agent's call: the shape follows the setting, because that is what the editor chose. Code that wants a different shape can change the image's attributes with the `wp_get_attachment_image_attributes` filter, or override it in CSS on the image.

## How

- **The mark and the ratio live on the image.** `Mai_Entry::get_image()` builds the `<img>` for grids, archives and singles alike. When the helper returns a ratio, it adds `has-aspect-ratio` to the image's class and `--aspect-ratio:<ratio>;` as its style, through the attributes it already passes to `wp_get_attachment_image()`:

  ```html
  <img class="entry-image size-landscape-sm has-aspect-ratio" style="--aspect-ratio:4/3;" width="455" height="288" ...>
  ```

  Mike chose this on 2026-11-20 over a ratio on the `.entries` tag plus a class on the image link. A mark and its ratio can never be separated, so it works with or without a link, in singles, in nested grids, and for any code that prints entries with `mai_do_entry()`. A ratio on the grid tag alone left a marked image with no value whenever entries were printed outside `mai_do_entries_open()`, and a lazy `sizes="auto"` image there rendered as a 300×150 strip that stayed after loading (measured in all three engines). A sweep of all 245 live sites and every local copy on 2026-11-20 found no such caller, but Mai is a public product.
- **The rule,** in `assets/scss/components/entry/_entry-image.scss` after the `.entry-image` rule (same weight, so order matters). `editor.scss` uses the same entry partials, so the editor gets it too.

  ```css
  .entry-image:where(.has-aspect-ratio) {
  	aspect-ratio: var(--aspect-ratio);
  	object-fit: var(--entry-image-object-fit, cover);
  }
  ```

  The class name matches core's: WordPress's style engine adds `has-aspect-ratio` next to an inline `aspect-ratio` on blocks that use its aspect ratio setting (`class-wp-style-engine.php`, `dimensions.aspectRatio`; only the Cover block in core), and no core stylesheet targets it. Mike kept the name on 2026-11-20 and kept the ratio in `--aspect-ratio` rather than core's inline `aspect-ratio`, so a theme can still change it with a plain stylesheet rule on `.entry-image.has-aspect-ratio`.

  `:where()` keeps it at the weight of `.entry-image`, so a child theme's plain `.entry-image` rule, which loads later, still wins. To change the ratio itself a theme targets the image, since the inline value beats a stylesheet value. `object-fit` reads the existing `--entry-image-object-fit` so a theme that sets it keeps its choice (`client-site-a` sets `contain` on `.entries-archive`).
- **This is the only image `aspect-ratio` rule.** The left-full and right-full image rule in `assets/scss/components/entry/_entry.scss` (`.has-image-left-full, .has-image-right-full { .entry-image { aspect-ratio: var(--aspect-ratio, 4/3); } }`) is deleted. In left-full and right-full the ratio only sets the image's minimum height: with a long excerpt the image already fills the card's height (`--entry-image-height: 100%`), so a change only shows when the text is shorter than the image. Measured in Chromium, Firefox and WebKit on 2026-11-20: a cropped landscape choice in a 600px card with a one-third image gives 200×150 with a short excerpt and fills a 609px-tall card with a long one, the same before and after. Deleting the rule also stops it shaping images in a grid nested inside a left-full entry, which its descendant selector did.
- **The grid tag is unchanged.** `mai_do_entries_open()` keeps printing `--aspect-ratio` for background, left-full and right-full exactly as today (agent's call: no change there means nothing for child themes that read it). After this change Mai's CSS reads it only in `.has-image-background .entry { aspect-ratio: var(--aspect-ratio, 4/3) }`, which shapes the background card: a background image fills the card and gives it no height, and a card cannot read a value set on an image inside it.
- **No width or height changes.** Measured in headless Chrome with `main.min.css` on 2026-11-19: width already comes from `.entry-image { min-width: 100% }` and each position's `--entry-image-width`, and height must stay auto for the ratio to apply. `cover` crops and never stretches, including a file smaller than the card, a portrait file in a landscape card, a landscape file in portrait and square cards, and an image with no width or height attributes.
- **Compiled CSS.** `assets/css/main.min.css` and `editor.min.css` are rebuilt and committed, since branches deploy as-is.

## Must keep working as today

Each gets a check in the build, in PHP tests where the markup decides it and in a headless browser where the CSS does:

- **Masonry:** no class, no style, every image keeps its own shape (`_masonry.scss` explains why `aspect-ratio` must never apply there).
- **Custom with an uncropped size** (`medium`, `large`, `full`, Mai's uncropped `cover`) in the full, center, left and right positions: no class, own shape, and `--entry-image-link-max-width` from the size width as today.
- **Background:** the card keeps today's ratio from the grid tag, including the bounding-box ratio for an uncropped size. A marked background image is 100% by 100% of the card, so its own ratio has no effect.
- **Left-full and right-full with a cropped choice:** the same boxes as today.
- **Image width** (half, third, fourth), **alternating**, **stacking** on small screens, **boxed** and **alignment:** these set widths and order only. The ratio sets the height from whatever width results.
- **A lazy image with `sizes="auto"`** that is not marked, including one in a grid nested in a shaped grid's entry: keeps the ratio from its width and height attributes.
- **A theme's `--entry-image-object-fit`** still wins.

## What changes on purpose besides missing crops

- **Left-full and right-full with an uncropped size** (for example custom `large`) are no longer forced to the size's bounding box when the text is shorter than the image. With Mai's real markup (`custom` sets `--entry-image-link-max-width` to the size's width, 1024px) in a 600px card and a 300×190 photo, the image goes from 475×475 to 475×301, the photo's own shape, the same as a full-width image with `large`. With a long excerpt the image fills the card's height before and after. Agreed with Mike on 2026-11-20.

## Left to the builder

- The helper is `mai_get_entry_aspect_ratio( $args ): string` in `lib/functions/images.php` (named in the plan). Every lookup it needs is already cached in a static (`mai_get_available_image_sizes()`, the orientation lookups), and it reads the size list only for `custom`.
- How to run the browser checks (a scratch script is fine; the repo has no browser test suite). Playwright with Chromium, Firefox and WebKit is installed under `/tmp/tp-demo/pw` on this machine but may be gone after a reboot.
- `CHANGES.md` lines under 2.42.0: a Fixed line for the missing crops and a Changed line for left-full and right-full with an uncropped size. The plan has the wording.
