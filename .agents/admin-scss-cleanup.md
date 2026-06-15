# `admin.scss` cleanup plan

> **STATUS — 2026-06-02: cleanup complete and shipped to `develop` (`0c85a9f44`).**
> Landed on branch `cleanup/wp-69-iframe-css`: Pass 1 (register admin/Kirki via
> `enqueue_block_assets`), Pass 2 (move iframe-content rules to editor.scss), Pass 3
> (route layout / dark-body / boxed into the iframe via PHP), Pass 4 (scope setup-wizard
> selectors under `.mai-setup-wizard`); plus the dark-text-on-forced-light-surfaces color
> work, the non-iframe full-width + admin-menu-width handling, the alignwide-in-sidebar
> cap, the cssnano/#660 build fix, and hardening `mai_do_classic_editor_styles`.
>
> **Parked (intentionally deferred, not done):**
> - Split the `mai_dynamic_css` transient into global vs context-dependent (own
>   investigation; see Follow-ups).
> - Search block overflowing narrow columns (needs a real Mai-site repro; see Backlog).
> - Button block-picker design contract is documentation-only (this doc is the record;
>   no code planned).
>
> Kept rather than deleted so those parked items and the decision records survive.

---

Working doc to support a "remove it all, add back one at a time" cleanup of `assets/scss/admin.scss`. The file currently mixes admin-chrome rules (outer admin DOM) with editor-iframe-content rules. Since the WP 6.9 iframe move, several rules are dead, broken, or unreachable across the iframe boundary.

This catalog labels every block so we can decide block-by-block what to keep, rewrite, or drop. The cleanup ships in two phases — Phase A (no-controversy deletions and the PHP iframe fix), then Phase B (test-and-restore per block).

---

## Separate workstream — light-island text on dark sites (login + `.has-light-background`) — added 2026-06-01

**Surfaced while testing the cleanup on `sportsdataio.test` (a dark-themed site).** Not part of `admin.scss`; tracked here so it's not lost.

### Symptom
wp-login.php: dark page background, but the white form card showed white text (white-on-white, invisible inputs/labels).

### Root cause (confirmed via live computed styles in cmux-browser)
`--color-body` / `--color-heading` follow the theme: dark on light sites, **white on dark sites** (so they read against `--color-background`). Any *forced-light* surface that trusts those tokens breaks on a dark site:
- Login form card: `body.login form { color: var(--color-body); background: var(--color-white); }` → white on white.
- Frontend/editor `.has-light-background` had the identical flaw (`color: var(--color-body); --heading-color: var(--color-heading)`).
- The dark-island side (`%dark-bg`) was already correct — it **hardcodes** `color: var(--color-white)`. Only the light-island side lacked a guaranteed-contrast color.
- editor.scss:82 already had a `TODO: Make this work if body color is set to white` — same issue, long known.

### Design decision (discussed + approved with user — Approach "B/(a)")
Considered: (A) hardcode a fixed dark like `%dark-bg` hardcodes white — rejected, regresses light sites + ignores brand; (C) computed token with a constant gray fallback — rejected, arbitrary + weaker contrast; (D) locally redeclare `--color-body` — rejected, muddy cascade. **Chosen: derive from the site's own palette.**

New PHP token overrides in `mai_add_light_surface_css()` (`lib/customize/output.php`, hooked into the `mai_add_kirki_css` chain). **Emitted ONLY on dark-themed sites** (where the override is actually needed); consumers carry a fallback so light sites resolve to `--color-body`/`--color-heading` directly:
```php
// PHP — only when background is dark AND the configured text color is light:
--color-body-on-light    = --color-background   // the dark surface color
--color-heading-on-light = --color-background
```
```css
/* every consumer — fallback to the themed color when the override isn't emitted (light sites) */
color: var(--color-body-on-light, var(--color-body));
--heading-color: var(--color-heading-on-light, var(--color-heading));
```
- Light sites: **nothing emitted** → consumers resolve to `--color-body`/`--color-heading` (byte-identical to before; no redundant `:root` props).
- Dark sites: emitted = the dark background; body+heading collapse to it (agreed single-ink result; "1 color is enough for dark sites").
- Value is **derived from existing tokens, not invented**. **The fallback is mandatory in every consumer** — a bare `var(--…-on-light)` would break on light sites (undefined var → inherited color).
- Islands pick their own class by background luminance (`lib/blocks/group.php:46`, `class-mai-column.php:119` → `mai_is_light_color($bg)`), so the dark-island side (`.has-dark-background` → `%dark-bg` → hardcoded `--color-white`) is independent and untouched. The four quadrants (light/dark island × light/dark site) all resolve correctly.

### Caching
No new cache. The token rides the existing **`mai_dynamic_css` transient** (1h TTL, `output.php:124`), already invalidated on `customize_save_after`, `update_option_mai-engine`, `save_post` (priority 999), and theme switch (`output.php:30–68`). (`save_post` flush exists because that same transient also holds context-dependent **page-header CSS** via `mai_get_template_arg()` — colors just ride along.)

### Files touched (all UNCOMMITTED)
- `lib/customize/output.php` — new `mai_add_light_surface_css()` + call in `mai_add_kirki_css()`.
- `lib/structure/login.php` — form card (`color` + `--heading-color`), inputs, and `.message/.success/#login_error` notice boxes → `-on-light` tokens.
- `assets/scss/utilities/_misc.scss` — `.has-light-background` → `-on-light` tokens (frontend).
- `assets/scss/editor.scss` — `.has-light-background` → `-on-light` tokens (editor canvas).
- `assets/scss/components/entry/_entry.scss` — `.has-boxed` (a forced-white entry card, `--entry-background: var(--color-white)`) `color`/`--heading-color` → `-on-light`. This is the **Mai Post Grid "all white in editor"** bug — boxed entries were white-on-white on dark sites. Pre-existing, same family.
- `assets/scss/components/entry/_entry-title.scss` — `.has-boxed` `--entry-title-link-color` → `-on-light`.
- `assets/scss/components/menu/_sub-menu.scss` — desktop sub-menu dropdown (forced-white) link color → `-on-light` fallback. (The `.has-dark-mobile-menu .mobile-menu` override in `_site-header.scss:73` correctly stays `--color-white` — that's a dark *panel*, not a white dropdown, so it's NOT a bug; left alone.) Compiles to `main.min.css`; `header.min.css` carries only the mobile override and is unchanged.
- `assets/scss/base/_form.scss` — front-end input text color (forced-white inputs) → `-on-light` fallback. Same class of bug as the login inputs, but for all front-end forms. Compiles to `main.min.css` + `editor.min.css`.
- Rebuilt CSS bundles: `utilities.min.css` + `editor.min.css` (for `.has-light-background`); then `main.min.css` + `editor.min.css` + `utilities.min.css` + `blocks.min.css` (for `.has-boxed`). Token *names* never changed across the B rework, so value-only changes needed no extra rebuild.

### Verification
- **Login: VERIFIED.** Computed input text flipped `rgb(255,255,255)` → `rgb(42,41,41)` (#2a2929 = site background) on the white card; screenshot confirms legible dark labels. Tokens emit `#2a2929` in page HTML.
- **Editor MPG `.has-boxed`: VERIFIED.** Token reaches the iframe (`#2a2929`); after rebuild + reload, boxed entry cards compute white bg + `rgb(42,41,41)` text + dark title links; screenshot of TECH/FINANCE/LIFESTYLE grids shows readable dark titles on white cards (was white-on-white). Non-boxed grids (transparent, white text on dark canvas) were always fine.
- **Frontend: VERIFIED across all four quadrants** via a built test page (`?page_id=2884` on sportsdataio, "Mai Light-Dark Nesting Test"). On the dark site: light island → `rgb(42,41,41)` dark; dark island → white; light-nested-in-dark → dark; dark-nested-in-light → white; form input (white) → dark. Loaded rule confirmed `var(--color-body-on-light,var(--color-body))`. (Test page can be deleted: `wp post delete 2884 --force`.)
- **Sub-menu fix:** same mechanism + compiled rule verified; not separately eyeballed (test page has no nav sub-menu).
- **No light-site regression:** guaranteed by construction (on light sites the token isn't emitted → fallback resolves to `--color-body`, the pre-change value); not yet exercised on a live light site.
- **Cache-busting gotcha (LOCAL TESTING ONLY):** Mai versions CSS by filemtime, but the gulp build's `gulp.dest` preserves the *source* mtime, so rebuilt `.min.css` keep a stale mtime → `?ver=` doesn't change → browsers serve cached pre-fix CSS. Fix while testing: `touch assets/css/*.min.css` then hard-reload. NON-ISSUE on release — the mai-engine version bump changes the `?ver=` prefix and busts every CSS URL.

### Edges deliberately left alone (user's call to chase)
- `.block-editor-plain-text` (editor.scss:82) — `--color-body` is *correct* on a dark canvas; only breaks inside a light block. Separate fix.
- Frontend form inputs *inside* a `.has-light-background` block still pull `--color-body` from the input SCSS — same root cause, broader change.

### Follow-ups
- ✅ **APPLIED 2026-06-01 — fixed `mai_is_light_color()`'s broken static cache** (`lib/functions/colors.php`). Was a no-op: missing `static` keyword + check/store key mismatch, so it re-parsed via ariColor every call. Now `static $cache = []` keyed by the resolved value (safe in Customizer preview — a changed color = a fresh key). NOT a transient (dynamic-CSS product is already transient-cached; DB I/O would cost more than the luminance math). Verified: token still `#2a2929`, no PHP errors. Helps the per-render `group.php`/`class-mai-column.php` calls that aren't transient-cached.
- **ariColor / color-mix / contrast-color — DECIDED 2026-06-01: no library changes.** ariColor isn't a standalone dep — it ships *inside* bundled Kirki Pro (`packages/kirki/lib/class-aricolor.php`). Mai's usage is tiny: 3 call sites (`mai_is_light_color` luminance; `mai_get_color_variant` lightness/getNew/toCSS). Decisions:
  - **Keep ariColor** — bundled with Kirki (zero cost), works, `class_exists` guards handle absence. Fork to a self-contained maiColor ONLY if decoupling from Kirki — and then copy ariColor's luminance + HSL methods VERBATIM (the `contrast-limit: 160` threshold is calibrated to ariColor's luminance scale; reimplementing from scratch would flip light/dark decisions site-wide).
  - **Skip `color-mix()`** for `mai_get_color_variant`. Lateral move: shades are already computed + transient-cached (nothing broken); `color-mix(srgb)` ≠ ariColor's HSL-lightness math so every shade/hover color would shift (regression risk); and it wouldn't drop ariColor (luminance stays in PHP; ariColor ships with Kirki anyway). Revisit only if decoupling Kirki or needing runtime-derived shades.
  - **Skip `contrast-color()`** for now. All engines support it in current versions (Chrome/Edge 147+, FF 146+, Safari 26+) but only ~67% global usage (1/3 of visitors unsupported → still needs the `-on-light` fallback = more code, not less), AND it returns black/white only (not our themed `#2a2929`). The eventual endgame once usage is high + B/W contrast is acceptable: `color: contrast-color(var(--surface-bg))` could retire the `-on-light` tokens + most PHP luminance. Not yet.
- **Split global vs context-dependent dynamic CSS into separate transients** (user suggested 2026-06-01). The single `mai_dynamic_css` blob mixes truly-global CSS (colors/breakpoints/fonts) with context-dependent page-header CSS, forcing the blunt `save_post` flush of everything. Deferred — hot-path refactor, marginal perf win, possible latent per-context caching subtlety; deserves its own investigation, not bundled with this bugfix.

### wp-cli on `sportsdataio.test` — was BROKEN, now FIXED 2026-06-01
**Symptom:** every `wp` command returned empty output / exit-0; `wp eval` didn't even run (file_put_contents never fired). **Root cause:** `mai-debugger` (`~/Plugins/mai-debugger`, symlinked) registered Whoops' `PrettyPageHandler` *unconditionally* — including under WP-CLI. Whoops' error handler converts PHP notices/deprecations into exceptions, then renders an (invisible-in-CLI) HTML page and exits. `wp-loupe`'s MCP server emits PHP 8.4 "implicitly nullable param" **deprecations** during bootstrap (`includes/class-wp-loupe-mcp-server.php:743,819`) → Whoops turned that into an exception → silent exit before the wp command ran. Either plugin alone = fine; together = dead wp-cli.
**Fix — RELEASED in mai-debugger 0.3.0** (commit `369333c`, pushed to `main`+`develop`, tag `0.3.0`): guard the Whoops registration with `$is_cli = ( defined('WP_CLI') && WP_CLI ) || 'cli' === PHP_SAPI;` and skip it in CLI. Verified: `wp eval`, `wp option get`, `wp transient delete` all work with the full plugin set. **No more MySQL workaround needed.** (That release also: bumped deps — PUC 5.6→5.7, var-dumper→6.4.36 LTS; added `Requires PHP: 8.1` header; switched PUC from branch-based to tag-based to match sibling mai-* plugins.)
**Note:** this fixes wp-cli on EVERY Herd site running mai-debugger once that plugin is committed/released. Secondary, harmless: wp-loupe's PHP 8.4 deprecation warnings still print (warnings only now) — could report upstream / bump wp-loupe.
(Old MySQL transient-bust recipe, no longer needed: `mysql -uroot -p"$PW" -h127.0.0.1 sportsdataio -e "DELETE FROM wp_options WHERE option_name LIKE '_transient_%mai_dynamic_css';"`. No persistent object cache present.)

---

## Session handoff — current state (last updated 2026-05-29)

### Where we are

- **Branch**: `cleanup/wp-69-iframe-css` (local; NEVER pushed; user wants to test all locally first).
- **Last commit**: `45b67122f` — "Block editor iframe: register admin/Kirki styles via enqueue_block_assets" (Pass 1 complete).
- **Working tree**: Pass 2 + several follow-on cleanups applied but **NOT yet committed**. Awaiting final test on `sportsdataio.test` before commit.
- **Test site**: `~/Herd/sportsdataio` (WP 7.0). Symlinks `~/Plugins/mai-engine` → live updates on rebuild. User edits posts to verify behavior.

### Constraints from the user (don't violate)

- **Never push to GitHub** without explicit "push" in the current session — even feature branches. Test locally first.
- **No mai-bulk-update skill** — Mai has a custom manual release flow.
- **Don't bump version, don't tag, don't cut a release.** The user does that manually.
- **Targeted staging only** — `git add lib/functions/enqueue.php` style. Don't `git add -A` because vendor/composer files were pre-existing modifications and `.agents/` is a working doc.
- **render_block is frontend-only** — never suggest it for editor-canvas changes.

### Uncommitted changes in working tree

Files touched since Pass 1:
- `lib/functions/enqueue.php` — Pass 1 changes (already committed) + (no new edits)
- `assets/scss/admin.scss` — Pass 2 strip (iframe-content rules moved out). Still contains cross-iframe rules flagged "Pass 3 TODO" (incl. the dead `--block-sidebar-width: 281px` setter).
- `assets/scss/editor.scss` — Pass 2 additions; iframe override `body.block-editor-iframe__body { --admin-menu-width: 0px; --block-sidebar-width: 0px; overflow-x: clip }`; alignwide fix + fallback; alignfull clean breakout (no scrollbar terms); `html { scrollbar-gutter: stable; overflow-y: scroll; scrollbar-width: thin }` mirroring the frontend (this ELIMINATED the editor full-bleed overhang entirely — block == body width).
- `assets/scss/utilities/_alignment.scss` — alignwide `*→+` fix + `min()` cap; alignfull `50vw→50dvw` and margin simplified to `calc(50% - 50dvw)` (scrollbar term removed).
- `assets/scss/abstracts/_extends.scss` — `%button-editor` slim (3 redundant/desynced lines removed — padding, border, border-color).
- `assets/scss/base/_html.scss` — replaced lone `scrollbar-gutter: stable` with `scrollbar-gutter: stable` + `overflow-y: scroll` + `scrollbar-width: thin` (persistent, thin, consistent scrollbar; no jump; no empty gap).
- `assets/scss/base/_globals.scss` — `--viewport-width: 100dvw` (scrollbar term removed); removed the `@property --scrollbar-width` registration and the commented `@supports` block; **added `--scrollbar-width: 0px` back-compat shim on :root** (Mai no longer uses it internally; kept so child-theme/custom CSS referencing it resolves cleanly).
- `assets/scss/base/_body.scss` — removed the `--scrollbar-width: calc(...)` declaration (machinery gone); keeps `overflow-x: hidden`.
- `assets/js/global.js` — JS scrollbar measurement removed; comment updated to describe the CSS-only model (no `@property`).
- `bin/config.js` — added `cssnano: { mergeRules: false, convertValues: false }` (see build-fix note below)
- `bin/styles.js` — **build-pipeline fix (GitHub issue #660)**: (a) wrap cssnano options in `{ preset: ['default', config.css.cssnano] }` in BOTH the shared `postProcessors` array and the `themes` task — cssnano 7 reads options from `preset`, so the flat `cssnano(opts)` form silently ran stock defaults (mergeRules ON mangled alignfull selectors; convertValues stripped `0px`→`0` and broke the `@property` registration); (b) rewrote the `themes` task from `Promise.all(map(..., () => stream))` (returned un-awaited streams → wrote nothing) to a single returned `gulp.src('themes/*.scss')` stream gulp awaits natively.
- 23 top-level rebuilt `.min.css` files + 7 theme `.min.css` (chic, delight, lookbook, prosper, reach, side-hustle, wellness — other 16 byte-identical) + `assets/js/min/global.min.js` + `assets/js/min/blocks.js`. NOTE: theme `.min.css` keep the SOURCE mtime (gulp.dest preserves stat), so `find -mmin` won't show them — trust `git status` (content) not timestamps.

### Build fix — GitHub issue #660 (consider a SEPARATE commit)

The cssnano config block in `bin/config.js` had been **silently ignored** because `bin/styles.js` passed it as `cssnano(opts)` instead of `cssnano({ preset: ['default', opts] })` (cssnano 7 reads from `preset`). So every `.min.css` was built with stock defaults. Two defaults broke things, plus a third stream bug:
1. `convertValues` rewrote `@property { initial-value: 0px }` → `0` → invalid `<length>` initial-value → registration dropped → `--scrollbar-width` never registered (`getComputedStyle` returned `''`).
2. `mergeRules` hoisted scoped `[data-content-align] > … > .wp-block[data-align=full]` margins into a BARE `.wp-block[data-align=full]` rule → stray `!important` `-side-spacing` margins on every top-level alignfull (this fought us all session).
3. `themes` task returned `Promise.all` of un-awaited streams → finished in ~24ms writing nothing; theme `.min.css` were stale since Dec 2025.
Fix = the `bin/styles.js` changes above. Consequence: enabling the dormant options changed ALL `.min.css` output (large but correct diff). **Recommend committing `bin/config.js` + `bin/styles.js` + all regenerated `.min.css` as its own commit referencing #660**, separate from the SCSS/iframe cleanup.

Plus pre-existing in working tree (DON'T commit, NOT this branch's work):
- `vendor/composer/*` (6 files) — user's prior composer regen
- `.agents/admin-scss-cleanup.md` (this doc) — working doc, untracked

### Status — VERIFIED on sportsdataio.test (2026-05-29)

All confirmed by the user, frontend + editor:
- **Frontend**: full-bleed edge-to-edge; persistent thin scrollbar; no jump, no gap, no horizontal scroll. `getComputedStyle(body).getPropertyValue('--scrollbar-width')` = `0px` even with a guaranteed scrollbar → the `calc(100vw-100%)`+`@property` trick is **confirmed DEAD** (it was never the fix; `overflow-x: hidden` + `scrollbar-gutter` are).
- **Editor**: full-bleed edge-to-edge (html == body == full-width block, all equal — overhang gone); selection outline no longer clipped; thin scrollbar; canvas scrolls fine; no double scrollbar.
- **Button hover**: no height jump.
- **Console**: zero "added to the iframe incorrectly" warnings.

### Final scrollbar model (the resolved approach)

- `<html>` (frontend `base/_html.scss` + editor `editor.scss`): `scrollbar-gutter: stable` + `overflow-y: scroll` + `scrollbar-width: thin`. Persistent, thin, gutter-reserved → consistent width (no jump), reserved space filled by a real scrollbar (no empty gap). Affects classic/always-on scrollbars only; overlay scrollbars (default macOS/mobile) reserve nothing and are unaffected.
- `<body>`: `overflow-x: hidden` (frontend) / `overflow-x: clip` (iframe) absorbs any full-bleed overhang.
- Full-bleed: `width: 100dvw` (frontend) / `var(--editor-viewport-width)` (editor); margins `calc(50% - 50dvw)` / `calc(50% - editor-vw/2)`. **No scrollbar measurement anywhere.**
- `--scrollbar-width`: removed as machinery; kept ONLY as a `0px` back-compat shim on `:root`.

### Open item — `--block-sidebar-width` / pre-iframe machinery (Pass 3 decision)

`--admin-menu-width` and `--block-sidebar-width` are vestigial: both resolve to `0` inside the WP 6.9+/7.0 iframe (admin menu + block-inspector sidebar live outside it). The only nonzero setter — `--block-sidebar-width: 281px` in `admin.scss` — is a cross-iframe rule that can't reach iframe content. They only did real work in the PRE-6.9 (non-iframe, classic-theme) editor. **Decision: if Mai has dropped pre-6.9 support, rip out the whole `--admin-menu-width`/`--block-sidebar-width` machinery + the dead 281px rule (Pass 3 simplification). Otherwise leave (harmless, 0 in modern WP).**

### Commits (when user says go — NO push)

Two logical commits. `.min.css` are regenerated wholesale, so they can't be split between the two — they land with whichever commit goes second.

**Commit 1 — build pipeline fix (`Fixes #660`):**
- `bin/config.js`, `bin/styles.js`

**Commit 2 — editor iframe + alignment + scrollbar cleanup:**
- `assets/scss/admin.scss`, `editor.scss`, `utilities/_alignment.scss`, `abstracts/_extends.scss`, `base/_html.scss`, `base/_globals.scss`, `base/_body.scss`
- `assets/js/global.js`
- `assets/css/*.min.css`, `assets/css/themes/*.min.css`, `assets/js/min/global.min.js`, `assets/js/min/blocks.js`
- (`lib/functions/enqueue.php` Pass 1 already committed in `45b67122f`)

Targeted `git add` only — do NOT commit `vendor/composer/*` or `.agents/`.

### Suggested commit messages

**Commit 1 — build fix:**

```
Fix cssnano config wiring + themes build (Fixes #660)

cssnano 7 reads optimization options from `preset`, but bin/styles.js passed
config.css.cssnano as the top-level argument — so the entire config block
(mergeRules/convertValues/etc.) was silently ignored and every .min.css was
built with stock defaults. Wrap the options in `{ preset: ['default', ...] }`
in both the shared postProcessors array and the themes task.

Two stock defaults were actively breaking things:
- mergeRules hoisted scoped [data-content-align] > … > .wp-block[data-align=full]
  margins into a bare .wp-block[data-align=full] rule, applying stray !important
  side-spacing margins to every top-level full-width block in the editor.
- convertValues stripped the unit off @property initial-value (0px -> 0),
  making that registration invalid.

Also rewrite the themes task: it returned Promise.all() of un-awaited gulp
streams, so it finished without writing — theme .min.css had been stale since
Dec 2025. Now returns a single gulp.src('themes/*.scss') stream gulp awaits.

Enabling the dormant options regenerates all minified CSS output (expected).

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
```

**Commit 2 — SCSS / iframe / scrollbar cleanup:**

```
Editor iframe + alignment + scrollbar cleanup (Pass 2 + follow-ons)

Pass 2: move iframe-content rules from admin.scss to editor.scss where they
belong (the old "must live in admin.scss to target body classes" reasoning
predates the WP 6.9/7.0 iframe move). admin.scss now holds only admin-chrome
rules plus cross-iframe rules still flagged TODO for Pass 3.

Editor canvas alignment:
- Add body.block-editor-iframe__body override zeroing --admin-menu-width /
  --block-sidebar-width and adding overflow-x: clip inside the iframe.
- Fix alignwide width calc (`*` -> `+`); add var(--breakpoint-lg) fallback for
  --wp-block-max-width; cap alignwide at viewport via min().

Scrollbar handling (CSS-only, no measurement):
- html: scrollbar-gutter: stable + overflow-y: scroll + scrollbar-width: thin
  (frontend base/_html.scss and editor iframe). Persistent, thin, consistent
  scrollbar — no layout jump, no empty gap; full-bleed overflow absorbed by
  overflow-x: hidden (frontend) / clip (iframe).
- Full-bleed uses 100dvw / --editor-viewport-width with calc(50% - 50dvw)
  margins — no scrollbar term.
- Remove the dead --scrollbar-width machinery (the @property + calc(100vw-100%)
  trick never worked: <length> rejects the percentage, so it always resolved
  to 0). Keep --scrollbar-width: 0px as a back-compat shim on :root for any
  child-theme/custom CSS still referencing it.
- Remove the JS scrollbar measurement in global.js.

Button hover:
- Slim %button-editor (drop duplicate padding, desynced border, redundant
  border-color) — fixes editor button height growing on hover.

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
```

### What's NEXT after commit

> **Pass 3 — IMPLEMENTED + committed locally 2026-05-30 (`93a25ac7e`); NOT pushed; dark-body + boxed-container NOT yet fully tested.**
> - `lib/functions/enqueue.php`: new `mai_get_admin_iframe_canvas_css()` + a `wp_add_inline_style` call inside `mai_enqueue_admin_iframe_styles()` (the `enqueue_block_assets` channel, handle `mai-engine-admin`). Emits, scoped to `.editor-styles-wrapper`: `--wp-block-max-width: var(--breakpoint-{sm|md|xl})` from `genesis_site_layout()` (narrow→sm; standard/content-sidebar/sidebar-content→md; wide/default→xl), plus dark-body → `--mai-block-appender-color: white`; boxed → `--body-background-color` + direct `background-color: white` + appender `#1e1e1e` (mutually exclusive, mirroring `mai_admin_body_classes`).
> - `assets/scss/admin.scss`: DELETED the three `.edit-post-layout` width media-queries + `body.has-dark-body`/`body.has-boxed-container` rules; docblock now points at the PHP. Rebuilt `admin.min.css` (`npx gulp build:admin-css`) — only that file changed.
> - Dark-body heading/body color intentionally NOT re-emitted (already reaches canvas via the Kirki color system — proven). Boxed-container is the LEAST certain path (untested pre-Pass-3); the direct `background-color` on `.editor-styles-wrapper` is the new bit that paints the canvas white inside the iframe — **verify visually on a boxed site**.
> - Deferred limitation: reflects state at editor LOAD; changing layout/colors in the sidebar needs a reload (no JS bridge yet).
> - **⚠️ TEST BEFORE PUSH / MERGE (user flagged 2026-05-30 — dark-body + boxed NOT yet verified):**
>   1. **Layout width** (main fix): a post set to narrow / standard / wide / content-sidebar each reloads to the right canvas content width in the iframe.
>   2. **Dark-body site**: empty Mai Column "type / to choose a block" appender placeholder renders WHITE on the dark canvas; post title + body text stay white.
>   3. **Boxed-container site**: canvas background goes WHITE, appender dark `#1e1e1e`. Least-certain path — the direct `background-color` on `.editor-styles-wrapper` is what makes it paint inside the iframe; if it looks wrong, revisit `mai_get_admin_iframe_canvas_css()`.
> - Committed locally as `93a25ac7e` (`lib/functions/enqueue.php`, `assets/scss/admin.scss`, `assets/css/admin.min.css`). NOT pushed.

1. **Pass 3 — route Mai's cross-iframe state INTO the iframe** (THE substantive remaining task). Mai's layout/color state is set on the OUTER admin doc (body classes via `admin_body_class`; vars on `.edit-post-layout`) and does NOT cross into the iframe (WP only mirrors `admin-color-*`, `post-type-*`, `wp-embed-responsive`). Broken in iframe mode:
   - **Layout / content width** (MOST visible — user found 2026-05-30): `admin.scss` sets `--wp-block-max-width` on `.edit-post-layout:not(.is-sidebar-opened)` keyed off the body layout class (`narrow-content`→breakpoint-sm; `standard-content`/`content-sidebar`/`sidebar-content`→breakpoint-md; wide/default→breakpoint-xl). The canvas reads `var(--wp-block-max-width, var(--breakpoint-lg))` (editor.scss ~69, ~107). In the iframe neither the class nor `.edit-post-layout` exists → canvas falls back to `breakpoint-lg` → **ignores the layout**. Affects Genesis per-post layout AND the customizer default.
   - **`has-dark-body`** (`admin.scss` `body.has-dark-body .editor-styles-wrapper`): the `--heading-color`/`--body-color: white` part is **REDUNDANT** — the color system already yields light `--color-heading`/`--color-body` for a dark bg and that reaches the iframe via Kirki (proven: iframe post title was white even though this rule does NOT apply in the iframe). Only `--mai-block-appender-color: white` is unique, and it's cross-iframe-broken anyway. → likely DELETE this rule; if the appender on a dark body needs whitening, do it via the inline-CSS channel below.
   - **`has-boxed-container`** (`admin.scss` `body.has-boxed-container .editor-styles-wrapper`): `--body-background-color: white` + appender `#1e1e1e`. Not yet tested in the iframe.

   **THE FIX:** a PHP function on `enqueue_block_assets` — the channel that DOES reach the iframe (Pass 1 already uses it: `mai_enqueue_admin_iframe_styles` in `lib/functions/enqueue.php`). Read Mai state in PHP (`genesis_site_layout()` → the right `--wp-block-max-width`; `mai_has_dark_body()`; `mai_has_boxed_container()`) and `wp_add_inline_style` it onto the iframe-loaded `mai-engine-admin` handle, targeting `.editor-styles-wrapper`. Then DELETE the cross-iframe selectors from admin.scss (the `.edit-post-layout` width rules + `body.has-dark-body` + `body.has-boxed-container`).
   - **Caveat:** inline CSS reflects state on editor LOAD. Live-update (changing layout/colors without reload) needs a small JS bridge to set the canvas var when the post setting changes — DEFERRABLE (save + reload reflects it).
   - **DONE already (this session, 2026-05-30):** the `--admin-menu-width` / `--block-sidebar-width` / `--editor-viewport-width` machinery is REMOVED — everything routes through `--viewport-width: 100vw`. Iframe unaffected (chrome was 0 there); non-iframe full-width is contained by `overflow-x: hidden !important` on `.editor-styles-wrapper.block-editor-writing-flow` in admin.scss (the inner-div scroller; `100vw` can't see its scrollbar, no viewport-unit/scrollbar-gutter trick works on an inner div, so we clip — same as the front-end body does). So Pass 3 = ONLY the inline-CSS layout/color routing above.
2. **Pass 4 — scope risky setup-wizard selectors** (`.error`, `.success`, `.label`, `.progress`, `#content`) under `.mai-setup-wizard`. The wizard is confirmed still shipping. Low-risk admin-chrome cleanup.
3. **Backlog (documented in this doc's "Backlog" section)**:
   - Harden `mai_do_classic_editor_styles` (user reports occasional errors)
   - Block color picker design contract — DO NOT add `--button-color` to the global `.has-{class}-color` rule (would cause buttons inside colored Group/Cover containers to inherit unwanted colors via CSS var inheritance). If propagation is desired, scope to `.wp-block-button__link.has-{class}-color`.

### Useful context recovered during this session

- **Iframe gating timeline (corrected via official WP docs, do NOT bump min):** iframe stays gated by block `apiVersion` through **WP 7.0**; **WP 7.1** makes it mandatory regardless. 6.9 = console warnings for `apiVersion ≤2` + block.json schema requires v3. 7.0 = the iframe decision evaluates against blocks **inserted in the post content** (a post containing a `≤v2` block → non-iframe; verified live by inserting an `apiVersion:1` block — registration alone didn't trigger it, insertion did). Mai's own blocks are ACF blocks declaring `"acf": { "blockVersion": 3 }` = WP `apiVersion 3` = iframe-ready, so they never force non-iframe. Non-iframe is reachable but RARE (needs a third-party legacy block) until 7.1. Mai min WP = 6.4 → keep it; you can't require a version that guarantees iframe (7.1 unreleased), so the non-iframe path must stay supported (writing-flow clip handles it).
- WP 7.0 iframe body class mirroring is **strictly limited** to: `admin-color-*`, `post-type-*`, `wp-embed-responsive`. Other classes (incl. all Mai's `admin_body_class` additions) do NOT cross the iframe boundary.
- `base/_html.scss` scrollbar handling was **revised in this work**: now `scrollbar-gutter: stable` + `overflow-y: scroll` + `scrollbar-width: thin` (was just `scrollbar-gutter: stable`). The editor iframe (`editor.scss` html) mirrors it — that's what eliminated the editor full-bleed overhang.
- `mai_classic_editor_styles` (`lib/customize/output.php:596`) is for the classic WYSIWYG editor, NOT the block editor. Confirmed by user. The block editor gets Kirki styles via the `mai-inline-styles` <style> element (via `mai_dynamic_css`/`mai_dynamic_fonts` transients and Kirki's normal output → copied into iframe via heuristic, then formally registered via Pass 1's `enqueue_block_assets` for `kirki-styles` handle).
- An empty/stale `mai_dynamic_css` transient was the cause of the "editor doesn't look like frontend" panic mid-session. Saving Mai customizer typography settings flushed the transient and fixed the visuals — was NOT caused by any code change.

### `:not()` vs `:has()` for button color guards

Discussed but **not changed**. The current `:not(.has-text-color)` guards in `%button-editor` are correct (`:not()` is the right operator). Frontend's `%button` does NOT have these guards — current behavior is "Mai customizer color wins everywhere," with `mai_add_colors_css()` (`lib/customize/output.php:327`) emitting `.has-{class}-color { color: !important; }` rules to handle the resting color override for WP block color picks. Hover/border/derivatives intentionally follow Mai customizer (documented as design contract in backlog).

---

## Cleanup process

1. **Phase A — no-risk deletions + PHP iframe fix**
   - Delete already-commented-out blocks (admin.scss + enqueue.php).
   - Delete provably dead `.is-sidebar-opened` rules.
   - Fix the typo at admin.scss:156 (`1px solid 1px solid …`).
   - Add `enqueue_block_assets` hook in `enqueue.php` so the `mai-engine-admin` handle registers through the iframe-correct channel (silences WP 6.9 warning).
   - Rebuild via `npm run styles`.
   - Commit, push to develop, verify warning is gone in browser on poinstitute.

2. **Phase B — strip-and-restore loop**
   - Move all remaining iframe-content rules out of admin.scss into a scratch section (commented or in a separate `admin-iframe-attic.scss` we don't load).
   - Rebuild.
   - Test editor visually. Note every regression.
   - Bring blocks back one at a time, in the correct file (`editor.scss` for iframe content, `admin.scss` for chrome). Re-test after each.
   - If a block has no visible effect when removed → it was already dead; delete permanently.

3. **Notes per block** below. Each block has:
   - Line range + key selector.
   - **Intent** — what it's trying to do.
   - **Scope** — outer admin DOM vs. inside editor iframe.
   - **Status** — `valid` / `dead` / `suspected broken since 6.9` / `commented` / `risky selector`.
   - **Test plan** — what to check when we remove it.
   - **Decision target** — `keep-as-is` / `move to editor.scss` / `delete` / `needs-verification`.

---

## File-level context

- The header comment at lines 4–12 ("Editor block content width. Needs to be here (not in editor.scss) so we can target body classes.") is **stale reasoning** from before 6.9. In 6.9 the editor canvas lives in an iframe and body classes set via `admin_body_class` (outer doc) do not automatically reach the iframe body. Whatever this file was doing for the iframe via outer-body classes is at best partially broken.
- `lib/admin/editor.php:15-47` is where `has-dark-body`, `has-boxed-container`, `content-sidebar`, `sidebar-content`, `standard-content`, `narrow-content` get added via `admin_body_class` (outer doc only).
- `editor.min.css` (built from `editor.scss`) is already loaded inside the iframe via `add_editor_style()` in `lib/functions/setup.php:40`. That is the correct channel for iframe-content rules.

---

## Block catalog

### Block 1 — `body { --admin-menu-width: 160px; }` (lines 14–17)

- **Intent**: Default admin menu width var; consumed by viewport-width calcs.
- **Scope**: Outer body. If admin.scss is mirrored into the iframe (currently via WP's heuristic), also iframe body.
- **Status**: Working in outer. Iframe reachability depends on whether WP mirrors the var into the iframe body.
- **Used by**: `--viewport-width` / `--editor-viewport-width` calcs (block 4), and indirectly `[data-align="full"|"wide"]` margins (blocks 10–11).
- **Test plan**: Remove → check editor canvas width is unchanged on layouts where alignfull/alignwide rely on this. Also check `wp-block-max-width` doesn't visibly drift.
- **Decision target**: **Move to editor.scss** (iframe body needs the var for iframe-side consumers). Consider whether outer doc still needs it — probably not, since nothing in admin chrome consumes it.

### Block 2 — `body.is-fullscreen-mode { --admin-menu-width: 0px; }` (lines 19–22)

- **Intent**: When editor is in distraction-free / fullscreen mode (admin menu hidden), set var to 0 so viewport-width calc reflects no admin menu offset.
- **Scope**: Outer body class (still applied in 6.9 — confirmed in `editor.min.js`).
- **Status**: Likely working in outer. Iframe needs the class mirrored; unverified.
- **Decision target**: **Move to editor.scss** with Block 1; rule fires inside iframe if WP mirrors `is-fullscreen-mode`.

### Block 3 — `@media (max-width: 960px) body { --admin-menu-width: 36px; }` (lines 24–28)

- **Intent**: Collapsed admin menu width on small viewports.
- **Scope**: Outer body, responsive.
- **Status**: Still valid in modern WP.
- **Decision target**: **Move to editor.scss** with Block 1.

### Block 4 — `.editor-styles-wrapper { --viewport-width: …; --editor-viewport-width: …; }` (lines 30–33)

- **Intent**: Compute viewport-width on the editor wrapper for use by alignfull/alignwide.
- **Scope**: `.editor-styles-wrapper` is inside the iframe in 6.9+.
- **Status**: **Duplicated** — `editor.scss:22-23` sets the same two vars on `body` (with a comment "These are both in admin.scss too. Not sure if we need both."). One of these is redundant.
- **Decision target**: **Delete** (the editor.scss copy is on `body` which is fine; this `.editor-styles-wrapper` version is redundant). If we keep one, the editor.scss `body` version is preferred.

### Block 5 — `body.has-dark-body .editor-styles-wrapper { --heading-color, --body-color, --mai-block-appender-color: white }` (lines 35–39)

- **Intent**: When Mai dark-body theme option is set, force heading/body/appender text white in the editor canvas (so dark backgrounds look right).
- **Scope**: `body.has-dark-body` is on **outer admin doc** (set via `admin_body_class` filter). `.editor-styles-wrapper` is **inside the iframe**. CSS descendant combinators do not cross documents.
- **Status**: **Suspected broken since 6.9.** The descendant cannot match across the iframe boundary.
- **Test plan**: With a dark-body site (poinstitute may have one), edit a post — confirm whether headings inside the editor canvas currently render dark on dark (broken) or white on dark (working). If broken: this rule was already non-functional and the fix is to set `--heading-color` / `--body-color` inside the iframe (via `editor.scss`) keyed off a body class that DOES exist in the iframe (or via a runtime body-class mirror).
- **Decision target**: **Needs verification.** If broken: delete and rewrite in `editor.scss` against an iframe-side body class.

### Block 6 — `body.has-boxed-container .editor-styles-wrapper { --body-background-color: white; --mai-block-appender-color: #1e1e1e }` (lines 41–44)

- **Intent**: When boxed-container option is set, force editor canvas background to white.
- **Scope**: Same problem as Block 5.
- **Status**: **Suspected broken since 6.9.**
- **Test plan**: Same approach — visual check on a boxed-container site.
- **Decision target**: **Needs verification.** Likely same fix as Block 5.

### Block 7 — `.interface-interface-skeleton__body:has(.interface-interface-skeleton__sidebar:not(:empty)) { --block-sidebar-width: 281px; }` (lines 46–49)

- **Intent**: Replacement for the dead `.is-sidebar-opened` class — set sidebar width var when the right-hand sidebar is open. Used in viewport-width calc.
- **Scope**: `.interface-interface-skeleton__body` is the editor shell in the **outer admin doc**, not in the iframe (confirmed: class still present in WP 6.9 JS dist).
- **Status**: Selector still matches → the var gets set on the outer doc. **But the consumers** (`.editor-styles-wrapper` viewport-width calc) **are inside the iframe**, where the var doesn't reach. **Half-broken** since 6.9.
- **Inline comment** already acknowledges the original workaround: "Can't use is-sidebar-open anymore, since https://github.com/WordPress/gutenberg/issues/62599."
- **Test plan**: Open sidebar, alignfull/wide block widths — should account for sidebar. Without this rule (or with it broken), alignfull blocks extend under the sidebar.
- **Decision target**: **Needs verification + rewrite.** Either set the var inside the iframe via a different mechanism, or accept that sidebar-open offset is broken in 6.9 and stop caring.

### Block 8 — `.edit-post-visual-editor__post-title-wrapper, .block-editor-block-list__layout.is-root-container { max-width: var(--wp-block-max-width, var(--breakpoint-lg)); margin: auto; }` (lines 51–56)

- **Intent**: Center editor canvas content (post title + root block list) at `--wp-block-max-width`.
- **Scope**: Both selectors are **inside the iframe** in 6.9+. Confirmed both class names still rendered in WP 6.9 JS dist.
- **Status**: Needs to be in the iframe. Currently in admin.scss → only reaches iframe via WP's heuristic (the thing the 6.9 warning is about).
- **Decision target**: **Move to editor.scss.**

### Block 9 — `html :where(.wp-block) { max-width: …; margin-top: 0; margin-bottom: 0; }` (lines 58–62)

- **Intent**: Default max-width and zero vertical margins for all blocks in the canvas.
- **Scope**: `.wp-block` is iframe content.
- **Status**: Needs iframe.
- **Decision target**: **Move to editor.scss.**

### Block 10 — `.wp-block[data-align="full"] { max-width, margin-right, margin-left }` (lines 64–68)

- **Intent**: Center alignfull blocks so they appear full-bleed accounting for admin menu and sidebar offsets.
- **Scope**: Iframe content.
- **Status**: Needs iframe. Math depends on `--admin-menu-width`, `--block-sidebar-width`, `--editor-viewport-width` (all currently set on outer doc — see Block 7).
- **Decision target**: **Move to editor.scss** AND ensure consumed vars are set on iframe body (Blocks 1–3, 7).

### Block 11 — `.wp-block[data-align="wide"] { alignwide spacing math }` (lines 70–77)

- **Intent**: Calculate wide-alignment side spacing dynamically based on viewport.
- **Scope**: Iframe content.
- **Decision target**: **Move to editor.scss** (same caveat as Block 10).

### Block 12 — Wide content responsive max-width (lines 79–101)

```scss
@media (min-width: 1220px) { .edit-post-layout:not(.is-sidebar-opened) { --wp-block-max-width: var(--breakpoint-xl); } }
@media (min-width: 1500px) { .edit-post-layout.is-sidebar-opened { --wp-block-max-width: var(--breakpoint-lg); } }
@media (min-width: 1740px) { .edit-post-layout.is-sidebar-opened { --wp-block-max-width: var(--breakpoint-xl); } }
```

- **Intent**: Responsive `--wp-block-max-width` based on viewport size and whether the right sidebar is open.
- **Scope**: `.edit-post-layout` is outer doc editor shell. Var set on outer doc; consumed in iframe by Block 9. Cascade broken.
- **Status**: `.is-sidebar-opened` is **completely absent from WP 6.9 JS dist** (verified). All `.is-sidebar-opened` rules are **DEAD**. The `:not(.is-sidebar-opened)` rules always match (selector becomes `.edit-post-layout` effectively).
- **Decision target**: **Delete the `.is-sidebar-opened` rules. Rewrite the responsive max-width logic in editor.scss keyed off iframe viewport/body classes, not outer doc.**

### Block 13 — Standard content (lines 103–110)

```scss
@media (min-width: 1300px) { .standard-content .edit-post-layout.is-sidebar-opened { --wp-block-max-width: var(--breakpoint-md); } }
```

- **Intent**: Tighten max-width on `standard-content` body class when sidebar open.
- **Status**: Both halves dead — `.standard-content` is on outer body (admin_body_class), `.is-sidebar-opened` no longer rendered. Even if the rule matched, the var is set on outer doc and never reaches iframe.
- **Decision target**: **Delete. Rewrite in editor.scss** keyed off an iframe-side class for the layout (or skip if not visually necessary).

### Block 14 — Narrow content (lines 112–126)

```scss
@media (min-width: 820px) { .narrow-content .edit-post-layout:not(.is-sidebar-opened) { --wp-block-max-width: var(--breakpoint-sm); } }
@media (min-width: 1080px) { .narrow-content .edit-post-layout.is-sidebar-opened { --wp-block-max-width: var(--breakpoint-sm); } }
```

- **Intent**: Narrower max-width on narrow-content body class.
- **Status**: Same problem as Block 13.
- **Decision target**: **Delete + rewrite if needed.**

### Block 15 — Sidebar layouts (lines 128–139)

```scss
@media (min-width: 1020px) {
    .content-sidebar .edit-post-layout.is-sidebar-opened,
    .content-sidebar .edit-post-layout:not(.is-sidebar-opened),
    .sidebar-content .edit-post-layout.is-sidebar-opened,
    .sidebar-content .edit-post-layout:not(.is-sidebar-opened),
    .standard-content .edit-post-layout:not(.is-sidebar-opened) {
        --wp-block-max-width: var(--breakpoint-md);
    }
}
```

- **Intent**: Force md max-width on sidebar layouts.
- **Status**: Same descendant-across-iframe / dead-class problem.
- **Decision target**: **Delete + rewrite if needed.**

### Block 16 — `[data-checked]:first-of-type { border-radius: 3px 0 0 3px !important; }` (lines 141–147)

- **Intent**: Round the first item in a radio button group (probably "Block layout settings" radios).
- **Scope**: Likely editor sidebar/inspector (outer doc chrome).
- **Status**: Generic selector — works wherever `[data-checked]` exists. Unclear where this is actually used.
- **Decision target**: **Needs verification.** Test plan: find a layout-radio in the editor sidebar, remove rule, see if border-radius regresses. If no visible effect, delete.

### Block 17 — Admin Menu (lines 149–167)

```scss
#toplevel_page_mai-theme .wp-submenu li:nth-child(4) { … border-bottom: 1px solid 1px solid rgba(255, 255, 255, 0.2); }
#toplevel_page_mai-theme .wp-submenu li:not(:nth-child(4)) + li:nth-last-child(3) { … border-top: 1px solid 1px solid rgba(255, 255, 255, 0.2); }
#toplevel_page_mai-theme .wp-submenu li:nth-child(4) + li:nth-last-child(3) { /* border: 0; */ }
```

- **Intent**: Visual separators between groups of items in the Mai admin menu (Theme > Settings group, plus a divider).
- **Scope**: Outer admin chrome.
- **Status**: Valid. **But there's a CSS bug**: `border-bottom: 1px solid 1px solid rgba(...)` is malformed (the second `1px solid` makes the value invalid → the whole declaration is dropped). Same on line 162. So **the dividers are not actually rendering today**. Was probably meant to be `border-bottom: 1px solid rgba(...)` (single `1px solid`).
- **Third rule has empty body** (just a commented-out `// border: 0;`).
- **Decision target**: **Keep + fix typo.** Delete the third (empty) rule. Phase A.

### Block 18 — ACF field groups in sidebar (lines 169–216)

```scss
#side-sortables .acf-postbox .postbox-header { … }
#side-sortables .acf-postbox:first-child .postbox-header { … }
#side-sortables .acf-postbox h2.hndle { … }
#side-sortables .acf-postbox .acf-hndle-cog, .handle-order-higher, .handle-order-lower { display: none !important; }
#side-sortables .acf-postbox .handlediv { … }
#side-sortables .acf-postbox .handlediv .toggle-indicator:before { background-image: …data URI… }
#side-sortables .acf-postbox .handlediv[aria-expanded="true"] .toggle-indicator:before { … }
```

- **Intent**: Restyle ACF postboxes in the side metabox column (classic editor + classic post screen sidebars). Hide the cog and reorder handles; custom handlediv with chevron icon.
- **Scope**: Outer admin chrome (metabox sidebar).
- **Status**: Valid. ACF still uses these class names in 6.x (verify post-Pro upgrade).
- **Decision target**: **Keep as-is.**

### Block 19 — ACF license / upgrade notice hiding (lines 219–227)

```scss
#tmpl-acf-field-group-pro-features, .acf-admin-toolbar-upgrade-btn { display: none !important; width: 0 !important; height: 0 !important; }
```

- **Intent**: Suppress ACF Pro upsell UI (assumes Pro is bundled and the upsell is irrelevant).
- **Scope**: Outer admin.
- **Status**: Valid. Worth verifying these selectors still exist in current ACF Pro release.
- **Decision target**: **Keep as-is, verify selectors against bundled ACF version.**

### Block 20 — Setup Wizard container (lines 229–342)

```scss
.mai-setup-wizard { position: fixed; top:0; left:0; z-index:99999; …flex layout… --color-primary: #2cb563; }
.mai-setup-wizard > .mai-setup-wizard-logo-wrap { … }
.mai-setup-wizard h1, > p, > .wrap, > form { … }
.mai-setup-wizard h2 { … }
.mai-setup-wizard input[type=email], label, input[type=checkbox] { … }
```

- **Intent**: Style the Mai setup wizard (full-screen overlay with green primary color, branded form).
- **Scope**: Outer admin (specific to wizard page).
- **Status**: Valid IF the wizard still exists.
- **Test plan**: Check `lib/admin/` for setup-wizard sources. If wizard still ships and is reachable, keep. If removed, delete the whole block.
- **Decision target**: **Verify wizard still exists, then keep.**

### Block 21 — Generic `.error, .success` (lines 344–348)

```scss
.error, .success { margin-bottom: 0; font-style: italic; }
```

- **Intent**: Error/success message styling. Implied scope is inside the setup wizard but written as global.
- **Scope**: Global in admin — **risky** because `.error` is also a WP admin notice class and `.success` is used by many plugins.
- **Status**: Risky selector pollution. Likely a bug that this is unscoped.
- **Decision target**: **Scope under `.mai-setup-wizard`** or delete.

### Block 22 — Generic `.label` (lines 350–353)

```scss
.label { display: inline-flex; align-items: center; }
```

- **Intent**: Probably setup wizard label flex.
- **Scope**: Global in admin — `.label` is a very common class (Bootstrap, badge libraries, etc.).
- **Status**: Risky selector pollution.
- **Decision target**: **Scope under `.mai-setup-wizard`** or delete.

### Block 23 — `[data-status]` rules (lines 355–384)

```scss
[data-status] > label { position: relative; }
[data-status] > label::after { …spinner.gif background-image, width/height 18px, visibility hidden… }
[data-status="running"] > label::after { visibility: visible; }
[data-status="complete"] > label::after { background-image: tick.png; visibility: visible; }
```

- **Intent**: Setup wizard step status indicators (spinner / checkmark).
- **Scope**: Wizard.
- **Status**: Valid for wizard. Generic `[data-status]` selector is risky elsewhere but probably scoped via the spinner asset URL not clashing.
- **Decision target**: **Scope under `.mai-setup-wizard`** if not already, then keep.

### Block 24 — `.progress { display: none; }` (lines 386–388)

- **Intent**: Hide some "progress" element by default in the wizard.
- **Scope**: Global — `.progress` is used by Bootstrap, ACF, and other plugins.
- **Status**: Selector pollution risk.
- **Decision target**: **Scope under `.mai-setup-wizard`** or delete.

### Block 25 — `#demo` rules (lines 390–469)

```scss
#demo ul { display: flex; … }
#demo li { … }
#demo label { …200px, outline, box-shadow… }
#demo label:focus-within { outline: 1px solid #007cba; }
#demo input { position: absolute; width:0; height:0; opacity:0; }
#demo img { cursor: pointer; pointer-events: none; }
#demo h4 { position: relative; }
#demo [data-status]::after { display: none; } /* unsets the [data-status] indicator inside #demo */
#demo h4::after { …tick.png… }
#demo input:checked ~ span h4::after { visibility: visible; }
#demo .label { …flex layout… }
#demo .label a { opacity: 0; transition: 0.2s; }
#demo label:hover a { opacity: 1; }
```

- **Intent**: Demo picker in the setup wizard (cards with screenshots, radio-style selection).
- **Scope**: Wizard.
- **Status**: Valid for wizard.
- **Decision target**: **Keep.** Could be scoped under `.mai-setup-wizard #demo` for clarity but `#demo` is unlikely to collide.

### Block 26 — `#plugins ul` (lines 471–474)

- **Intent**: Plugins step layout.
- **Scope**: Wizard.
- **Decision target**: **Keep.**

### Block 27 — `#content` rules (lines 476–511)

```scss
#content ul { display: block; max-width: 300px; …text-align: left… }
#content li { margin-bottom: 24px; }
#content label { display: block; }
#content .label { display: block; align-items: unset; }
#content .label strong { … }
#content .step-description { … list-style: disc }
#content .step-description li { margin-bottom: 8px; }
```

- **Intent**: Content selection step in wizard.
- **Scope**: Targeted at wizard, but **`#content` is an extremely common ID** used by many themes, admin pages, and plugins.
- **Status**: **Risky selector pollution.** These rules may bleed into unrelated admin pages.
- **Decision target**: **Scope under `.mai-setup-wizard #content`** or rename the ID in the wizard markup.

### Block 28 — Commented-out content area styles (lines 513–527)

```scss
// Can't target body classes in editor styles.
// body.post-type-wp_block .editor-styles-wrapper, body.post-type-mai_template_part .editor-styles-wrapper { … gradient bg }
// body.post-type-wp_block .editor-post-title__block:not(.is-focus-mode).is-selected .editor-post-title__input, body.post-type-mai_template_part … { background: white }
```

- **Status**: **Already commented out.** The comment "Can't target body classes in editor styles" is interesting — it's an admission that this approach didn't work. Dead.
- **Decision target**: **Delete.**

---

## Summary table

| Block | Lines | Decision |
|-------|-------|----------|
| Header comment | 1–12 | Rewrite or delete (stale rationale) |
| 1 | 14–17 | Move to editor.scss |
| 2 | 19–22 | Move to editor.scss |
| 3 | 24–28 | Move to editor.scss |
| 4 | 30–33 | Delete (redundant with editor.scss:22-23) |
| 5 | 35–39 | Verify broken → rewrite in editor.scss |
| 6 | 41–44 | Verify broken → rewrite in editor.scss |
| 7 | 46–49 | Verify + rewrite (var has to land in iframe) |
| 8 | 51–56 | Move to editor.scss |
| 9 | 58–62 | Move to editor.scss |
| 10 | 64–68 | Move to editor.scss |
| 11 | 70–77 | Move to editor.scss |
| 12 | 79–101 | Delete dead `.is-sidebar-opened` halves; rewrite responsive logic in editor.scss |
| 13 | 103–110 | Delete; rewrite if needed |
| 14 | 112–126 | Delete; rewrite if needed |
| 15 | 128–139 | Delete; rewrite if needed |
| 16 | 141–147 | Verify; likely keep in admin.scss |
| 17 | 149–167 | Keep, fix double-`1px solid` typo, delete empty 3rd rule |
| 18 | 169–216 | Keep as-is |
| 19 | 219–227 | Keep, verify selectors vs current ACF |
| 20 | 229–342 | Verify wizard still ships; keep if yes |
| 21 | 344–348 | Scope under `.mai-setup-wizard` or delete |
| 22 | 350–353 | Scope under `.mai-setup-wizard` or delete |
| 23 | 355–384 | Scope under `.mai-setup-wizard` |
| 24 | 386–388 | Scope under `.mai-setup-wizard` or delete |
| 25 | 390–469 | Keep |
| 26 | 471–474 | Keep |
| 27 | 476–511 | Scope under `.mai-setup-wizard #content` (risky ID) |
| 28 | 513–527 | Delete (already commented) |

---

## Related cleanup in `enqueue.php`

- Lines 328–329: commented-out `mai_remove_global_styles_css` action registrations. The function itself (lines 337–339) has no live callers. **Delete the function and the commented hooks.**
- Lines 342–354: `mai_remove_block_library_theme_css` deregisters `wp-block-library-theme`. Confirmed still registered in WP 6.9 — **keep.**
- Lines 356–382: `mai_admin_bar_inline_styles` adds inline CSS for admin-bar offset on the frontend. Uses `100vh` — could be modernized to `100dvh` but works. **Keep.**

---

## Backlog (after Passes 1–4)

- **Harden `mai_do_classic_editor_styles`** (`lib/customize/output.php:612-653`). The user has seen it throw errors occasionally. Consider:
  - Guard each block (Kirki transient read, font @import generation, `\Kirki\Module\CSS` class call, `mai_has_boxed_container()`) so a failure in one doesn't abort the whole response.
  - When the generated CSS is empty or noticeably small, log a warning rather than caching empty for an hour.
  - Sanity check: `class_exists('Kirki\Module\CSS')` is already there — also verify `ob_start()` / `ob_get_clean()` pairing under error conditions.
  - Send an empty 200 with `Content-Type: text/css` instead of 500-style failures so the editor doesn't fall back to weird states.

- **Block color picker for buttons — design contract.** Document and intentionally constrain. Today:
  - `mai_add_colors_css()` (`lib/customize/output.php:327`) emits `.has-{class}-color { color: var(--color-{name}) !important; --body-color: ...; --heading-color: ...; --caption-color: ...; --cite-color: ...; }` and a matching `.has-{class}-background-color { background-color: !important; }`.
  - When the picker is applied to a button block, the **resting color/background** is overridden via `!important`. **Hover, outline border, and other `--button-*` derivatives are NOT propagated** — they keep their Mai customizer defaults. This is by design: the block picker is a per-instance override; the Mai customizer is the brand-level control.
  - **Do NOT add `--button-color` to the global `.has-{class}-color` rule.** CSS custom properties inherit to descendants, so doing so would cause buttons inside any colored Group/Cover/etc. container to inherit the wrong color even when the user didn't pick a color for the button itself.
  - If a future requirement asks for full picker propagation to buttons specifically, the targeted approach is to add **button-scoped** rules — `.wp-block-button__link.has-{class}-color { --button-color: ...; --button-color-hover: ...; --button-outline-color: ...; }` plus a matching background variant. Scope only to `.wp-block-button__link` to avoid the inheritance leak.
  - Similar consideration applies to any other Mai UI element reading `--button-color` (menu items, Woo product buttons, EDD buttons). Per-element scoping required if propagation is desired.

- **Search block overflows narrow sidebar columns.** (`components/blocks/_search.scss:22`.) Reported on the frontend 2026-05-29 — the search bar/button extends past a narrow sidebar column. Suspected cause: the inside-wrapper width is `min(var(--search-min-width, 320px), var(--search-max-width, 100%))` with **no hard `100%`/container cap**, so a search block with an explicit width (the `lib/blocks/search.php` `render_block` filter turns an inline `width` into `--search-max-width` as a fixed px) can exceed a column narrower than that px. Likely fix: add `, 100%` to the `min()` so it never exceeds the container regardless of the other inputs. If the overflow is instead the flex children not shrinking, add `min-width: 0` to `.wp-block-search__input`. **Reproduce against an actual rendered search before committing a fix** — confirm whether it's the wrapper width or input min-width. Not part of the iframe/alignment work; standalone CSS fix, test in a real sidebar at multiple column widths.

---

## Phase B test matrix (when stripping iframe rules)

For each of these visual states, edit a page on poinstitute and screenshot before/after:

1. Default body, layout = `standard-content`, no sidebar open.
2. Default body, layout = `content-sidebar`, sidebar open.
3. Default body, layout = `narrow-content`.
4. `has-dark-body` true, no boxed container.
5. `has-boxed-container` true.
6. Fullscreen / distraction-free mode.
7. Below 960px viewport (collapsed admin menu).
8. Insert: alignfull, alignwide, default-width blocks. Verify each centers/extends correctly.
9. Post title spacing below it.
10. Block layout radio in inspector (border-radius on first option — Block 16).

A block whose removal produces no visible difference across all 10 states → it was dead. Delete.

---

## Open questions for you

1. Is the setup wizard still shipping and reachable in current builds? (Block 20 + dependents.)
2. The `[data-checked]:first-of-type` rule (Block 16) — do you know what radio group it was targeting? Worth keeping?
3. For Blocks 5 / 6 / 7, the cleanest fix may involve adding an `enqueue_block_assets`-fired mirror of the body classes onto the iframe body (via a small JS snippet or via the iframe styles enqueue). Want to scope that into Phase B, or live with those features being decorative-only in the editor?

---

# `editor.scss` cleanup catalog

`assets/scss/editor.scss` is loaded into the iframe correctly via `add_editor_style()` in `lib/functions/setup.php:40`. **By design, everything in this file is iframe-content scope.** The file is much more coherent than `admin.scss` — it's almost entirely block-styling against `.wp-block`, `[data-type=…]`, `[data-align=…]`, and Mai-specific data attributes. Few cross-iframe issues here.

The catalog below groups consecutive related rules into sections rather than per-block, because most of the file is intentional and doesn't need a test plan. Flagged anything genuinely worth questioning.

## Section catalog

### E0 — Module imports (lines 1–12)

```scss
@use "abstracts/__index" as *;
@use "base/box-sizing" as *;
@use "base/form" as *;
@use "base/globals" as *;
@use "base/heading" as *;
@use "base/list" as *;
@use "base/table" as *;
@use "base/typography" as *;
// @import "layout/columns";
@use "components/blocks/_index-editor" as *;
@use "components/entry/_index" as *;
@use "utilities/_index" as *;
```

- **Intent**: Pull in shared SCSS modules (typography, lists, blocks, entries, utilities) so editor styles match frontend.
- **Status**: Live. Line 9 `// @import "layout/columns";` is a commented-out legacy import using old `@import` syntax (Sass is moving away from `@import`). **Delete the dead import.**
- **Decision**: Keep all `@use` lines. Delete line 9.

### E1 — `html { font-smoothing }` (lines 14–18)

```scss
html {
    // font-size: 100%; // 16px browser default. This was breaking after the changes in 2.38.0.
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
```

- **Intent**: Smooth font rendering inside the editor canvas.
- **Note**: Comment about `font-size: 100%` removed in 2.38.0 — can drop the explanatory comment, keep the rule. Or leave the comment as institutional memory.
- **Decision**: Keep. Optional cleanup: drop the dead-code comment.

### E2 — `body` defaults (lines 20–27)

```scss
body {
    min-height: auto;
    --viewport-width: calc(100vw - var(--admin-menu-width, 160px) - var(--block-sidebar-width, 0px));
    --editor-viewport-width: calc(100vw - var(--admin-menu-width, 160px) - var(--block-sidebar-width, 0px));
    --list-item-margin-bottom: var(--spacing-xs);
    @extend %body;
}
```

- **Intent**: Editor canvas body base — disable forced min-height, compute viewport-width vars for alignfull/alignwide math, set list-item margin var, extend frontend body styles.
- **Note**: `--viewport-width` and `--editor-viewport-width` are duplicates of each other (same calc, both used elsewhere — see content-align section). And these vars are also defined on `.editor-styles-wrapper` in `admin.scss:30-33` (the admin.scss copy is redundant and gets deleted in admin.scss cleanup).
- **Suspected limitation**: `--admin-menu-width` and `--block-sidebar-width` are referenced as fallback vars (defaults 160px / 0px). The "true" values are set in `admin.scss` against outer-doc selectors (`body`, `.interface-interface-skeleton__body:has(…)`) and don't reach the iframe in 6.9. So in practice the iframe always falls back to defaults (160px / 0px). Alignfull/alignwide math is therefore approximate, not dynamic.
- **Decision**: Keep. After admin.scss cleanup, move `--admin-menu-width` setters into this file (or a new iframe-scoped block) so the calc actually responds to admin menu collapse / fullscreen. Consider whether `--viewport-width` and `--editor-viewport-width` need to be separate variables — pick one and alias.

### E3 — `p` (lines 29–31)

```scss
p { font-size: var(--body-font-size, var(--font-size-md)); }
```

- **Decision**: Keep.

### E4 — Lists (`ul.wp-block`, `ol.wp-block`) (lines 33–49)

- **Intent**: Margin/padding/list-style for list blocks in editor, including nested lists.
- **Decision**: Keep.

### E5 — Editor canvas post title (lines 51–58)

```scss
.edit-post-visual-editor__post-title-wrapper { margin-bottom: var(--spacing-md); }
.editor-post-title__block .editor-post-title__input { @extend %heading; }
```

- **Note 1**: Inline comment "this element is using `var(--wp--style--block-gap)` which is not defined in this non-FSE theme." — explains the override. Useful institutional knowledge, keep.
- **Note 2**: When admin.scss Block 8 (`.edit-post-visual-editor__post-title-wrapper { max-width: …; margin: auto; }`) moves into editor.scss, it joins this section.
- **Decision**: Keep.

### E6 — Block list appender (lines 60–64)

```scss
.block-editor-block-list__block .block-list-appender.block-list-appender {
    margin-right: auto;
    margin-left: auto;
}
```

- **Intent**: Center the "+ insert block" appender in nested block lists. Comment: "These are 0 in WP so the appender isn't centered by default."
- **Decision**: Keep.

### E7 — `.block-editor-plain-text` (lines 66–70)

```scss
.block-editor-plain-text {
    /* TODO: Make this work if body color is set to white. */
    /* TODO: --input-color: #000; is necessary when this happens as well. */
    color: var(--color-body);
}
```

- **Intent**: Override WP core plain-text input color to match site body color.
- **Note**: Two TODO comments about white body color edge case — old, may or may not still apply.
- **Decision**: Keep. **Verify TODOs are still relevant** (test a dark-body site).

### E8 — Dark/light background classes (lines 72–85)

```scss
.has-dark-background { @extend %dark-bg; }
.has-light-background { color: var(--color-body); --heading-color: var(--color-heading); }
.has-inline-color.has-link-color { color: var(--color-link); }
```

- **Intent**: Apply Mai dark/light background styles to blocks tagged with these classes (Group/Cover with Mai background presets). Inline color rule overrides link color when both classes present.
- **Comment**: "FYI: The has-dark-background class is not automatically added to Group blocks with dark color in the editor." — institutional knowledge that this only works via Mai's own class assignment, not WP core's color picker.
- **Decision**: Keep. **Cross-reference**: lines 530–537 redefine `--mai-block-appender-color` for `.has-dark-background` and `.has-light-background` — same selectors used twice in the file. Worth consolidating.

### E9 — `.alignfull` reset, `.wp-block.is-reusable` (lines 87–98)

```scss
.alignfull { width: 100%; max-width: 100%; margin-right: 0; margin-left: 0; }
.wp-block.is-reusable { max-width: unset !important; margin-right: 0; margin-left: 0; }
```

- **Intent**: Reset margins for alignfull (Mai's own override of WP defaults) and for reusable blocks (let them break out).
- **Decision**: Keep.

### E10 — Pullquote left/right (lines 100–112)

- **Intent**: Match frontend pullquote float behavior in editor.
- **Decision**: Keep.

### E11 — Pullquote/quote citation spacing (lines 114–118)

```scss
.wp-block-quote__citation, .wp-block-pullquote__citation { margin-top: var(--spacing-sm); }
```

- Inline comment: "Not sure why the editor markup for these blocks is so different, but we need space in editor."
- **Decision**: Keep. Comment is fine.

### E12 — Block list children (Group / Cover / Mai Post Grid) (lines 120–128)

```scss
.block-editor-block-list__layout > [data-type="core/group"], > [data-type="core/cover"], > [data-type="acf/mai-post-grid"] {
    margin-top: 0; margin-bottom: 0;
}
```

- **Decision**: Keep.

### E13 — Alignfull Group/Cover side padding (lines 130–138)

```scss
.wp-block[data-align="full"] > .wp-block-group { padding-right/left: var(--side-spacing); }
.wp-block[data-align="full"] > .wp-block-cover { padding-right/left: var(--side-spacing); }
```

- **Decision**: Keep.

### E14 — Content-align variants (lines 140–175)

```scss
[data-content-align="start"]  { … left-aligned children math, alignfull negative margin … }
[data-content-align="center"] { --group-block-justify-content: center; --cover-block-justify-content: center; }
[data-content-align="end"]    { … right-aligned children math … }
```

- **Intent**: Mai's content-align Group/Cover behavior — children alignment plus alignfull breakout math using `--editor-viewport-width` and `--side-spacing`.
- **Note**: Uses `--editor-viewport-width` which currently falls back to defaults (see E2 caveat).
- **Decision**: Keep.

### E15 — Heading/paragraph content-align text alignment (lines 177–185)

```scss
p[data-content-align="start"], .wp-block-heading[data-content-align="start"] { margin-left: 0; }
p[data-content-align="end"],   .wp-block-heading[data-content-align="end"]   { margin-right: 0; }
```

- **Decision**: Keep.

### E16 — Padding @each generator (lines 187–202)

```scss
@each $padding-name, $padding-size in $padding_scale {
    @each $padding-setting in $padding_settings {
        .wp-block-cover[data-spacing-#{ $padding-setting }="#{ $padding-name }"],
        .wp-block-group[data-spacing-#{ $padding-setting }="#{ $padding-name }"] {
            padding-#{ $padding-setting }: #{ $padding-size } !important;
        }
    }
}
```

- **Intent**: Generate per-scale-step padding rules for Cover/Group when Mai's `enableLayoutSettingsBlocks` option is set. Documented in the preceding comment.
- **Decision**: Keep. **Generator** — only meaningful while `$padding_scale` × `$padding_settings` matrix is what Mai needs.

### E17 — Max-width @each generator (lines 204–228)

```scss
@each $breakpoint-name, $breakpoint-size in $breakpoints {
    [data-type="core/heading"][data-max-width="#{ $breakpoint-name }"],
    [data-type="core/paragraph"][data-max-width="#{ $breakpoint-name }"] {
        --wp-block-max-width: var(--breakpoint-#{ $breakpoint-name });
    }
    [data-content-width="#{ $breakpoint-name }"] {
        --content-max-width: var(--breakpoint-#{ $breakpoint-name });
        --wp-block-max-width: var(--breakpoint-#{ $breakpoint-name });
    }
}
```

- **Intent**: Per-breakpoint max-width rules for Mai's `enableMaxWidthSettingsBlocks` and `enableLayoutSettingsBlocks` options (Heading, Paragraph, Cover, Group).
- **Decision**: Keep.

### E18 — `data-content-width="no"` (lines 230–234)

```scss
[data-content-width="no"] {
    --content-max-width: var(--viewport-width);
    --wp-block-max-width: var(--viewport-width);
    --side-spacing: 0;
}
```

- **Decision**: Keep.

### E19 — Margin @each generator (positive) (lines 236–255)

- **Intent**: Per-scale-step margin rules for Heading/Paragraph/Separator via Mai's `enableSpacingSettingsBlocks`.
- **Decision**: Keep.

### E20 — Margin @each generator (positive, second set) (lines 257–274)

- **Intent**: Per-scale-step margin rules for Image/Cover/Group via Mai's `enableMarginSettingsBlocks`.
- **Decision**: Keep.

### E21 — Negative margin @each generator (lines 276–295)

- **Intent**: Same as E20 but for negative margins, with `position: relative; z-index: 1`.
- **Decision**: Keep.

### E22 — `[data-margin-*="no"]` reset (lines 297–302)

- **Intent**: Zero out margin on `no` setting.
- **Decision**: Keep.

### E23 — Font-size @each generator (lines 304–309)

```scss
@each $size-name, $size-value in $font_sizes {
    [data-font-size="#{ $size-name }"] > .rich-text { font-size: #{ $size-value }; }
}
```

- **Decision**: Keep.

### E24 — Image block auto margins (lines 311–315)

```scss
// Our default of 0 left/right margin caused overlap in editor.
.wp-block-image { margin-right: auto; margin-left: auto; }
```

- **Decision**: Keep.

### E25 — Button block styling (lines 317–367)

- **Intent**: Apply Mai button extends to editor button blocks (default, is-style-default, is-style-secondary, is-style-link, is-style-outline, button-small, button-large variants).
- **Note**: Inline comment "// button, // This breaks core block control buttons." documents why we don't target bare `button`. Keep.
- **Note**: Comment "Makes sure block styles are rendered correctly in editor. See #303." — references an internal issue. Keep.
- **Decision**: Keep.

### E26 — Entry-more button overrides (lines 369–372)

```scss
.entry-more.is-style-link .wp-block-button__link,
.entry-more:not([class*="is-style-link"]):not([class*="is-style-"]) .wp-block-button__link { padding: 0; }
```

- **Note**: The selector `:not([class*="is-style-link"]):not([class*="is-style-"])` is redundant — the second `:not` already covers the first since `is-style-link` matches `is-style-`. Minor.
- **Decision**: Keep. Optional cleanup: simplify the redundant `:not()`.

### E27 — Search block (lines 374–424)

- **Intent**: Layout + button styling for the WP Core Search block in the editor.
- **Note**: Comment "When no alignment is set the wp-block is the same as the search block. When you set alignment it adds wp-block as a wrapper. Strange." — useful institutional knowledge.
- **Decision**: Keep.

### E28 — Mai grid pointer-events (lines 426–428)

```scss
.mai-grid a { pointer-events: none; }
```

- **Intent**: Disable clicks on grid links inside the editor (prevent accidental navigation when editing a grid).
- **Decision**: Keep.

### E29 — `.entry-grid` order (lines 430–432)

```scss
.entry-grid { order: var(--entry-order, var(--entry-index, var(--order, unset))) !important; }
```

- **Decision**: Keep.

### E30 — Columns system (lines 434–528)

- **Intent**: Flexbox-based responsive columns for Mai's `has-columns` / `has-columns-nested` / `mai-columns` / `is-column` / `mai-column` system. Includes the `@flex-basis` calc trick (with the `0.025px` margin-collapse workaround, commented). Uses Mai's `mq()` mixin.
- **Note**: Comments link to two external articles about flex min-content sizing — `https://defensivecss.dev/tip/flexbox-min-content-size/` and `https://dfmcphee.com/flex-items-and-min-width-0/`. Worth keeping.
- **Decision**: Keep. This is core to Mai's columns architecture.

### E31 — `--mai-block-appender-color` for dark/light backgrounds (lines 530–537)

```scss
// This is also handled for dark body in admin.scss.
.has-dark-background { --mai-block-appender-color: var(--color-white); }
.has-light-background { --mai-block-appender-color: #1e1e1e; }
```

- **Intent**: Set the block-appender color for `.mai-column` empty drop zones (consumed in E32) based on the background class on the parent.
- **Cross-reference**: The "also handled for dark body in admin.scss" comment refers to `admin.scss:35-44` (Blocks 5 & 6) which sets `--mai-block-appender-color` keyed on `body.has-dark-body` / `body.has-boxed-container` instead — and those rules are suspected broken since 6.9. Once admin.scss Blocks 5/6 are rewritten in editor.scss, this section should consolidate with them. Also conflicts with E8 (`.has-light-background` defined twice in this file).
- **Decision**: Keep but **consolidate**: merge with E8 so each class has one home. Update the "also handled" comment after admin.scss cleanup lands.

### E32 — Mai column empty drop zone (lines 539–565)

```scss
.mai-column .block-editor-block-list__layout[data-is-drop-zone="true"]:empty { …36px box with appender-color outline… }
.mai-column .block-editor-block-list__layout[data-is-drop-zone="true"]:empty::after { …big "+" content… }
.mai-column .block-editor-block-list__layout[data-is-drop-zone="true"]:empty:hover, :focus { color = wp-admin-theme-color }
```

- **Intent**: Show a visible drop target for empty Mai columns. The pseudo-element with `+` is a fallback when the empty "Type / to choose a block" placeholder gets deleted.
- **Comment**: "This is only needed if you delete the empty 'Type / to choose a block' element from the column."
- **Decision**: Keep. **Verify**: WP/Gutenberg may have changed `[data-is-drop-zone="true"]` between versions — confirm selector still applies in 6.9.

### E33 — Mai accordion item spacing (lines 567–579)

```scss
.wp-block[data-type="acf/mai-accordion-item"] { margin-bottom: var(--row-gap); }
.wp-block[data-type="acf/mai-accordion-item"] .mai-accordion-item { margin-bottom: 0; }
.wp-block[data-type="acf/mai-accordion-item"] + .wp-block[data-type="acf/mai-accordion-item"] { margin-top: 0; }
```

- **Decision**: Keep.

---

## editor.scss summary

| Section | Lines | Status / Decision |
|---------|-------|-------------------|
| E0 — imports | 1–12 | Keep `@use`s; delete commented `// @import "layout/columns"` |
| E1 — html font-smoothing | 14–18 | Keep (optional: drop dead-code comment) |
| E2 — body + viewport vars | 20–27 | Keep; resolve duplicate `--viewport-width` vs `--editor-viewport-width`; move admin-menu-width setters here once admin.scss cleanup lands |
| E3 — p | 29–31 | Keep |
| E4 — lists | 33–49 | Keep |
| E5 — post title | 51–58 | Keep; admin.scss Block 8 moves here |
| E6 — appender center | 60–64 | Keep |
| E7 — plain-text | 66–70 | Keep; verify TODOs still real |
| E8 — bg classes | 72–85 | Keep; consolidate w/ E31 |
| E9 — alignfull + reusable | 87–98 | Keep |
| E10 — pullquote left/right | 100–112 | Keep |
| E11 — citation margin | 114–118 | Keep |
| E12 — block list children | 120–128 | Keep |
| E13 — alignfull G/C padding | 130–138 | Keep |
| E14 — content-align | 140–175 | Keep (depends on E2 vars) |
| E15 — heading/p content-align | 177–185 | Keep |
| E16 — padding @each | 187–202 | Keep |
| E17 — max-width @each | 204–228 | Keep |
| E18 — content-width=no | 230–234 | Keep |
| E19 — margin @each (spacing) | 236–255 | Keep |
| E20 — margin @each (img/cover/group) | 257–274 | Keep |
| E21 — negative margin @each | 276–295 | Keep |
| E22 — margin=no reset | 297–302 | Keep |
| E23 — font-size @each | 304–309 | Keep |
| E24 — image auto margin | 311–315 | Keep |
| E25 — button extends | 317–367 | Keep |
| E26 — entry-more | 369–372 | Keep; optional: simplify redundant `:not()` |
| E27 — search block | 374–424 | Keep |
| E28 — mai-grid pointer | 426–428 | Keep |
| E29 — entry-grid order | 430–432 | Keep |
| E30 — columns system | 434–528 | Keep |
| E31 — appender-color (dark/light bg) | 530–537 | Keep; consolidate w/ E8; revisit "also handled" comment after admin.scss cleanup |
| E32 — mai-column drop zone | 539–565 | Keep; verify `[data-is-drop-zone="true"]` still rendered in 6.9 |
| E33 — mai-accordion | 567–579 | Keep |

## What this means for the overall cleanup

- editor.scss is in much better shape than admin.scss. **No suspected-broken rules. No dead WP selectors.** Most of it stays.
- Concrete cleanup actions in editor.scss are small:
  1. Delete the commented `// @import "layout/columns"` (line 9).
  2. Decide whether `--viewport-width` and `--editor-viewport-width` need to be separate or can be one var.
  3. Consolidate `.has-dark-background` / `.has-light-background` rules (E8 + E31).
  4. Verify the two TODO comments in E7 and the `[data-is-drop-zone]` selector in E32 are still real.
  5. Optional: simplify the redundant `:not()` chain in E26.
- The bulk of editor.scss's role in this cleanup is as the **destination** for iframe-content rules moving out of admin.scss (Blocks 1–11 from admin.scss).

## Open questions for editor.scss

1. ~~`--viewport-width` vs `--editor-viewport-width` (E2): is the distinction meaningful, or can we collapse to one?~~ **RESOLVED via codebase search.** They serve different scopes:
   - `--viewport-width` is **shared frontend+editor**. Defined in `base/_globals.scss:124` for frontend (`calc(100vw - scrollbar)`). Re-defined in `editor.scss:22` for editor (`calc(100vw - admin-menu - sidebar)`) so the 19 frontend SCSS references (in `utilities/_alignment.scss`, `_misc.scss`, `components/blocks/_group.scss`, `_cover.scss`, `themes/fabulous.scss`, `themes/sleek.scss`, `components/menu/_nav.scss`, `components/header/_title-area.scss`, `_mobile.scss`, `base/_html.scss`) pick up the editor-aware value when rendered in the canvas.
   - `--editor-viewport-width` is **editor-only**. Used by editor-canvas-only selectors (`.edit-post-visual-editor__post-title-wrapper`, `.wp-block[data-align="full"|"wide"]` in admin.scss; `[data-content-align="start|end"]` alignfull math in editor.scss).
   - **Action**: Keep both. Delete admin.scss's redundant definitions (lines 31-33). Add a doc comment in `editor.scss:22-23` explaining the distinction.
2. Are the TODOs in E7 (`.block-editor-plain-text` dark-body color) still real, or stale?
3. Does `[data-is-drop-zone="true"]` still get rendered on `.mai-column` empty layouts in WP 6.9? (E32)

