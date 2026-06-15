# mai-engine — GitHub Issue Triage (running doc)

**Repo:** maithemewp/mai-engine · **Branch:** develop · **Release in progress:** 2.39.1
**Created:** 2026-06-03 · **Last updated:** 2026-06-03

## Purpose
Working list for clearing the open-issue backlog during the 2.39.1 cycle. Triaged all 80 open issues against git history, `CHANGES.md`, and current code. This doc is the source of truth.

## How to use
- `[ ]` = queued, not yet closed. `[x]` = closed on GitHub.
- **NOTHING is closed/commented on GitHub until explicitly told** (no team notifications mid-work). Everything is queued here and fired in one batch later.
- **Two workflows:**
  - **Bucket A (needs new code):** commit the fix locally with `Fixes #N`, do **not** push. Pushing `develop` later auto-closes.
  - **Bucket B (no code):** manual close from the command block. None of the fix commits reference issue numbers, so nothing auto-closes on push.

## Decision log
- **2026-06-03:** Alignment cluster — close #499 (fixed), #614 (cannot-reproduce/superseded); consolidate #570 into #540; fixed `_alignment.scss` margin-right typo (commit `1e70f5d7c`, local).
- **2026-06-03:** Verify-first cluster — close #543/#457/#156/#324 (resolved); keep #419/#388/#453/#103/#117 open. Won't-fix cluster — close #638/#658/#301/#368. Corrected #103/#117 mislabel (not duplicates).
- **2026-06-03:** Setup-wizard cluster — close #525/#444 (stale, no recurrence), #99 (obsolete; Woo removed the theme-switch onboarding). Keep #101 (not now), #102 (future phone-home), #177 (low priority). #100 keep open + WANTED (setting to disable wizard). #420 confirmed still a real bug (only the from-address is filtered, not the name) — fix candidate, approach agreed. #539 reframed to "hide wizard when no demos" and DEFERRED with a turnkey plan (see Deferred fix plans); not 2.39.1.
- **2026-06-03:** AMP cluster — close #441 (largely addressed by the 2.7.0 amp-sidebar + search exclusion in `lib/structure/amp.php`) and #321 (won't-fix; transparent/sticky header needs scroll JS AMP forbids). Logged "evaluate dropping AMP support" as a future deprecation item (AMP support is one self-contained file, `lib/structure/amp.php`; legacy tech, near-zero maintenance, but actively helps the few AMP users — deprecate deliberately, not in 2.39.1).
- **2026-06-03:** WooCommerce cluster — close #5 (obsolete; vague 2020 note, Woo support is now comprehensive), #133 and #135 (stale 2020 styling nits, no recurrence; reopen if they recur).
- **2026-06-03:** #653 (transparent header + Cover on static posts page) — root-caused and DEFERRED (not 2.39.1). Detection-level bug, not CSS-only; carries breaking-change risk. See Deferred fix plans.
- **2026-06-03:** Final bug cluster — #564 FIXED (local commit `e74acc6fc`, `Fixes #564`). #554 close (verified fixed via RTL devtools test, no overflow). #616 close (not currently reproducible; reopen note records the currentColor-in-background-image cause). #375 keep (worth testing), #381 defer, #338 keep. #355/#582 still pending close-confirm.
- **2026-06-03:** #635 IMPLEMENTED (local commit `3c530d367`, `Fixes #635`). Verified via WP core that button width is class-based (not inline), so no `render_block`/Tag Processor needed; fixed in CSS with a `min-width: min(max-content, 100%)` floor in frontend + editor styles. Pending live verification; revert with `git revert 3c530d367 --no-edit` if it misbehaves.
- **2026-06-03:** NEW bug (found testing on sportsdataio.test) — alignfull broke out symmetrically over the sidebar in boxed sidebar layouts. FIXED (local commit `f6523051d`): pin alignfull to the content column in `.has-boxed-container` sidebar layouts. Asymmetric breakout (content-side to box edge, sidebar-side pinned) considered but DEFERRED as risky — see Deferred fix plans.
- **2026-06-03:** Link color in editor — investigated the "set white, shows blue / has-link-color" confusion. Root cause: the theme.json preset stylesheet generated a `.has-link-color { color: var(--wp--preset--color--link) !important }` rule (from Mai's `link` palette slug) that collided with WP 6.4's link-element class. The theme.json cleanup (`caeba7028`) removed that rule everywhere (front end + editor iframe), so it's resolved. Also confirmed the missing per-block Link control is expected: `settings.color.link` is `false` (not caused by our filter). DECIDED (option A) to KEEP per-block link color OFF — links stay on Mai's global `--color-link` model. Enabling it would mean removing the `render_block` strip and, because the cleanup removed the color preset vars, palette-based link colors wouldn't resolve (only custom hex). Not worth it; no change.
- **2026-06-03:** Opened real GH issues for the no-issue work: **#661** theme.json perf (fixed `caeba7028`), **#662** alignfull sidebar pin (fixed `f6523051d`), **#663** alignwide editor cap + typo (fixed `1e70f5d7c`), **#664** asymmetric alignfull breakout (deferred enhancement), **#665** evaluate dropping AMP (deferred). **#661/#662/#663 closed manually** with comments referencing the fixing commits (commit messages don't say `Fixes #N`, so they weren't going to auto-close). **#664/#665 left open** (deferred). Fixes are committed to develop locally, pending push.
- **2026-06-04:** #635 button fix was BROKEN in Safari/WebKit (the pushed `3c530d367` used `min-width: min(max-content, 100%)`; WebKit drops `min()` with an intrinsic keyword, so buttons never expanded — confirmed live via cmux at 362px). Reworked LOCALLY (commit `1e0f43a10`, NOT pushed): mobile-scoped (`max-width: md`) `min-width: fit-content` on custom-width buttons — keeps the set % when the label fits (50%→50%, 75%→75%), grows to fit when it can't (long labels → up to 100%, then wrap), 100% unchanged, no overflow. Verified in WebKit. **GH note:** I wrongly reopened #635 on GH during the workday (user did not want any workday GH activity); leaving it reopened, no further GH actions, awaiting user's call. cmux browser shows stale cached CSS (`?ver` not busting), so verification was via injection + computed value + built-file inspection.

## Commits this session (PUSHED to origin/develop on 2026-06-03)
On push, `#564` and `#635` auto-closed (their commit messages used `Fixes #N`). The three no-`Fixes` commits are tracked by `#661`/`#662`/`#663`, which were closed manually with commit references.
- `1e70f5d7c` — Alignwide: read `--alignwide-margin-right` for the right margin. Latent typo fix + re-syncs `editor.min.css` with `140dfc1aa` (the `--alignwide-max-width:100%` sidebar cap was only in `utilities.min.css`). Tracked as **#663**. Frontend behavior-neutral; restores alignwide-beside-sidebar cap in the editor canvas.
  - ⚠️ Run a full `gulp build:css` before release — this proved `.min.css` can drift from source.
- `e74acc6fc` — Lookbook: scope larger excerpt type to `.entry-excerpt-single` (was on `:root`, leaking 1.2em into grid/archive excerpts). `Fixes #564` (auto-closes on push). Rebuilt `lookbook.min.css`. Verify on a Lookbook site.
- `3c530d367` — Buttons (#635): `min-width: min(max-content, 100%)` on non-100% custom-width buttons so a long label expands the button (capped at full width, then wraps) instead of cramping. Frontend (`_button.scss` → `blocks.min.css`) + editor (`editor.scss` → `editor.min.css`). `Fixes #635`. **Needs live verification (CSS intrinsic-sizing); revert if it misbehaves: `git revert 3c530d367 --no-edit`.**
- `caeba7028` — Theme.json: remove WP core's unused default presets (palette/gradients/duotone/font-sizes/spacing/shadows/aspect-ratios) via `wp_theme_json_data_default`. Performance. CHANGES.md entry added. Verified on sportsdataio.test. Tracked as **#661**.
- `f6523051d` — Alignfull pin in boxed sidebar layouts (NEW bug found while testing on sportsdataio.test/test/). Tracked as **#662** (asymmetric follow-up: #664). `.has-boxed-container .has-content-sidebar/.has-sidebar-content` broke alignfull out symmetrically over the sidebar; now pinned to the content column (`--alignfull-margin: 0`, `--alignfull-max-width: 100%`). `_alignment.scss` → `utilities.min.css` + `editor.min.css`. Verify on the /test/ page. Needs a CHANGES.md entry + maybe a tracking issue (batch later).

## ⭐ 2.39.1 fix candidates found during triage
Small, real, on-theme. Decide if you want them this release (would be Bucket A: local commit, no push).
- **#419** — anchor link inside the mobile menu doesn't close the menu (`maybeCloseMobileMenu` only fires on outside-clicks/Escape).
- **#388** — extend "use featured image as page header" to term archives (currently gated to `is_singular()`/front page).
- **#420** — wizard optin email sets sender name "WordPress" in ActiveCampaign. Add a `wp_mail_from_name` filter returning `''` around the existing `wp_mail()` call (`lib/admin/setup-wizard.php:217-223`). Tiny, self-scoped. Approach agreed; awaiting go-ahead to commit.
- **#100** (WANTED) — setting to disable the setup wizard (+ optional notice once run). Needs a short design spec before building.
- **#635** — DONE (commit `3c530d367`, `Fixes #635`), pending live verification. Final approach: `min-width: min(max-content, 100%)` floor on non-100% custom-width buttons (better than the mobile-only unset; works at all sizes, no `calc` duplication, no `render_block` needed since width is a class not inline). Confirmed via WP core CSS that button width is class-based.

---

## ✅ Queued to close — resolved (23)
Verified in code/changelog. Close-comment text in the command block.

**Verified-fixed (17):** #647, #93, #528, #526, #464, #421, #331, #308, #479, #415, #392, #274, #520, #304, #106, #44, #67
**Alignment (2):** #499 (fixed), #614 (cannot-reproduce/superseded)
**Verify-first resolved (4):** #543 (grid field-query, fixed Dec 2024 like #93), #457 (preload shipped), #156 (fluid clamp/vw type scale), #324 (separator `.is-style-dots`)

(Per-issue evidence for the original 17 is preserved in the command block comments below.)

## ✅ Queued to close — won't-fix / works-as-intended (6)
- **#638** Term Grid multiple taxonomies — "leave as-is, use `mai_term_grid_query_args`."
- **#658** mobile "Logo on Scroll" — prefer main logo on mobile; invite reopen with version.
- **#301** taxonomy description on term archives — intended (first-page only).
- **#368** first/last helper classes — "too soon" (2020 deferral).
- **#441** AMP mobile header + search icon — largely addressed by the 2.7.0 amp-sidebar (search excluded in `amp.php:109-111`); "layout a bit off" too vague to chase.
- **#321** transparent/sticky header not AMP-compatible — needs scroll JS AMP forbids; AMP de-emphasized.

## ✅ Queued to close — stale / obsolete (6)
Setup-wizard cluster:
- **#525** demo import fails on full-size images — no recurrence in years.
- **#444** setup wizard timeout not surfaced — no recurrence in years.
- **#99** Woo theme support in wizard — obsolete; WooCommerce removed the old theme-switch onboarding step years ago.

WooCommerce cluster:
- **#5** WooCommerce default setup — obsolete; vague 2020 note, Mai's Woo support is now comprehensive.
- **#133** product search ugly button — stale 2020 styling nit, no recurrence.
- **#135** cart widget wonky — stale 2020 styling nit on the legacy cart widget (superseded by Woo cart blocks).

## ✅ Queued to close — verified fixed / can't-reproduce (2)
- **#554** RTL side scroll — verified fixed (tested with `html dir="rtl"`, no overflow). Resolved by horizontal-scroll fixes + `scrollbar-gutter` + logical properties.
- **#616** admin menu icon black — not currently reproducible; close until seen again. (If it recurs: the icon SVG uses `fill="currentColor"` but is loaded as a base64 background image, which has no color context, so it can render black; fix would hardcode the menu-icon color.)

## 🔗 Consolidate — #540 + #570
Keep **#540** open (canonical, needs the PHP/helper-class approach). Close **#570** as duplicate. Comments queued in command block. Not slated for 2.39.1.

---

## ⬜ Keep open — confirmed still valid this session
- **#419** mobile menu anchor close — real bug (see 2.39.1 candidates above).
- **#388** featured image as term page header — not implemented for terms (see candidates above).
- **#453** heading bottom spacing in editor — no positive evidence of fix; needs a live editor check before deciding.
- **#103** `mai_is_type_single/archive` cache — static cache disabled as a workaround (`b13b2f174`) because it broke `is_front_page()`; root cause never chased. Low priority; verify whether caching was ever restored.
- **#117** grid "current entry" class — feature request, never implemented. Distinct from #103 (doc previously mislabeled it as a dup).

## ⏸️ You explicitly chose "leave open" — close only if changed your mind (4)
- **#524** white logo on login page · **#448** `[mai_price]` margins · **#395** divider over overlapped image · **#285** move Genesis Layout/Custom Classes metaboxes (deferred to "v3").

---

## ⬜ Keep open — remaining backlog (not yet individually triaged)
Next clusters to work through.

**Setup wizard (remaining open):** #100 (WANTED — setting to disable wizard + optional notice), #420 (2.39.1 fix candidate — from-name filter), #539 (reframed: hide wizard when no demos — see Deferred fix plans), #101 (demo generator; not now), #102 (phone-home data aggregation; future), #177 (separate home/inner; low priority).
**Header/scroll-logo:** #653 (transparent header + Cover on static blog) — root-caused, deferred; see Deferred fix plans.
**Other bugs (open):** #381 (Events Calendar page header; deferred, needs live TEC test), #375 (small custom archive image coverage; worth testing), #338 (Woo smallscreen CSS). · Pending close-confirm: #355, #582. · #635 now a 2.39.1 candidate (see above); #564 fixed; #554/#616 queued to close.
**Other features:** #510 (paste SVG icon), #504 (Mai Spacer block), #495 (Mai Columns inherit), #480 (grid block patterns), #458 (Custom CSS doc link), #456 (Order By/Order on archives), #454 (filter for Mai_Entry args), #413 (negative margin on pullquote), #349 (theme screenshots 1200x900), #269 (menu item icon fields), #227 (site header template part), #179 (template part hierarchy), #291 (theme CSS to helper classes), #531 (per-block stylesheet loading), #521 (`[mai_menu]` center align in mobile).

---

## 🗑️ Future deprecation candidates (deliberate, not 2.39.1)
- **Drop AMP support (#665).** All AMP code is one self-contained file (`lib/structure/amp.php`, ~200 lines, loaded at `init.php:307`); `mai_is_amp()` is used only there; no settings. Legacy tech (Google dropped AMP from Top Stories / Page Experience in 2021), near-zero maintenance, but it actively gives AMP users a working `<amp-sidebar>` menu — removing it degrades their pages. Before dropping: check whether any BizBudding/client sites still serve AMP, then remove in a planned minor/major with a changelog deprecation note.

## ✅ DONE — Removed WP core default theme.json presets (performance) · commit `caeba7028`
`lib/functions/theme-json.php` (`mai_remove_default_theme_json_presets`, hooked `wp_theme_json_data_default`) empties the default presets, so the big `global-styles-inline-css` dump is gone: core palette, gradients, duotone, font sizes, shadows, aspect ratios. Spacing presets are generated from `spacingScale`, so its `steps` are zeroed (emptying the array wasn't enough). CHANGES.md perf entry added. Registered in `init.php`.

**What actually worked:** the array-empty method (mai-builder style), NOT the `defaultPalette`-style boolean opt-outs (those did not take effect via the filter, despite being core's documented `prevent_override` mechanism).

**Verified on sportsdataio.test/test/** with a real front-end request after `wp transient delete --all` (site has no persistent object cache → requests recompute fresh): all core defaults gone. Remaining `--wp--preset--*` are only Mai's own `color--white` plus `font-size--huge`/`font-size--normal` (the latter two come from core's block-library CSS in wp-includes, a separate stylesheet, not theme.json — negligible). Mai editor palette + `--color-*` intact.

**Still to do:** open/queue a GH issue (none existed). Not pushed.

## 📋 Deferred fix plans (turnkey for later)

### Asymmetric alignfull breakout in sidebar layouts (enhancement) — #664
Deferred (risky). Current behavior after `f6523051d`: alignfull pins to the content column in sidebar layouts. Nicer target: break out on the **content side** to the box edge, **pin the sidebar side** to the content column, and go full width when the layout stacks (below lg).
- Direction-aware: `content-sidebar` (sidebar right) breaks left; `sidebar-content` (sidebar left) breaks right.
- **KEY RISK (per user):** the block's INNER content must stay put. The wrapper can break out, but its inner padding has to compensate so text/buttons don't drag sideways with the breakout. That compensation is the hard part.
- Test matrix: `content-sidebar` / `sidebar-content` × boxed / non-boxed, plus the lg breakpoint transition. Test page: `sportsdataio.test/test/`.

### #653 — Transparent header + Cover block on a static posts page
Deferred (detection-level bug, breaking-change risk; not 2.39.1).

**Root cause:** `mai_get_first_block()` (`lib/functions/helpers.php:434`) returns false unless `mai_is_type_single()`. A static blog/posts page is an archive, so first-block detection never runs there. Result: `mai_has_alignfull_first()` / `mai_has_dark_background_first()` are false and the `has-transparent-header` / `has-dark-transparent-header` body classes are never added (`lib/structure/layout.php:88-96`). The Cover also renders inside the `archive-description` wrapper.

**Fix spans three parts:**
1. Detection: handle the static-posts-page case (`is_home() && ! is_front_page()`) by sourcing the assigned page's content in `mai_get_first_block()` (and any `mai_is_type_single()` gate in the dark/alignfull-first helpers).
2. CSS: pull a first Cover up under the transparent header when it sits inside `.archive-description`.
3. Decision: whether the `archive-description` wrap is still needed (the original issue's open question) — removing/changing it risks regressions across all blog/archive layouts.

**Verify live:** static posts page + transparent header enabled + leading Cover block; confirm transparent + dark-transparent classes apply and the Cover pulls under the header, without breaking normal archives.

### #539 — Hide the setup wizard when there are no demos
Reframed from "show plugins step without demos." Deferred (behavior change, needs live verification; not 2.39.1).

**Approach**
1. **Gate the wizard on demos.** Where the wizard menu/page (and any redirect) is registered (`lib/admin/setup-wizard.php`, around the `mai_setup_wizard_menu` filter, `menu_slug => 'mai-setup-wizard'`), skip registration when `mai_get_config('demos')` is empty. Result: demo-less themes (e.g. Mai Slate) don't show an empty wizard.
2. **Make required plugins surface deterministically.** Today Mai Icons (`config/_default.php:164`) has no `'required'` flag, so for demo-less themes it only appears via a coincidental `0 === 0` match in `mai_get_plugin_dependencies()` (`lib/admin/dependencies.php`). Mark theme-required plugins `'required' => true` so the explicit required-plugins loop adds them regardless of demos. This is the part that actually replaces #539's intent.

**Why it's safe:** wizard steps are all demo-derived; `WP_Dependency_Installer` via `mai_load_dependencies()` already prompts recommended plugins "in case setup wizard wasn't run," so plugin installs don't depend on the wizard.

**Verify live (demo-less theme, e.g. Mai Slate):**
- Wizard menu/page is gone when no demos.
- Dependency admin notice still appears and offers to install/activate required plugins (Mai Icons).
- No errors/broken links from the wizard menu being absent (check redirects, `dependencies.php`).

**Caveat:** demo-less themes lose the wizard email optin (acceptable).
**Files:** `lib/admin/setup-wizard.php`, `lib/admin/dependencies.php`, plugin configs (`config/_default.php` + per-theme).

## ✅ BATCH EXECUTED 2026-06-04
Ran the full staged batch: closed all 38 verified-fixed / alignment / won't-fix / stale / can't-reproduce issues, posted the #540 consolidation comment + closed #570, and closed #582/#355. Open issues went 80 → 40. The commands below are kept for the record.

## Ready-to-run: batch close

### Resolved — verified-fixed (17)
```bash
gh issue close 647 --comment "Fixed in 1246e0f2c. mai_get_processed_content() now runs do_blocks() before do_shortcode(), so synced patterns expand before shortcodes are parsed."
gh issue close 93  --comment "Fixed via the Mai Post/Term Grid field-query fixes in Dec 2024 (098794415, 6c88db636). Heavy ACF field queries now use acf/prepare_field to load only the current value on page load."
gh issue close 528 --comment "Shipped in b1ddc4d57. Before/after date query settings were added to the Mai Post Grid block."
gh issue close 526 --comment "Working as of current develop. taxonomies_relation is applied to the grid tax_query (lib/classes/class-mai-grid.php)."
gh issue close 464 --comment "Fixed in 02b041951 (2.34.0). Use the --menu-item-name-filter-hover custom property to control hover dimming."
gh issue close 421 --comment "Fixed in 8560eb62c, shipped in 2.39.0. Exclude Current and Exclude Displayed now work on term archives."
gh issue close 331 --comment "Shipped in 93fe66e85. Mai Term Grid shows top-level terms only by default."
gh issue close 308 --comment "Fixed in 2.35.0. Mai Divider now renders full width inside Group/Cover blocks in the editor (switched to WP_HTML_Tag_Processor)."
gh issue close 479 --comment "Fixed in 2.34.0. Search block width settings now work correctly."
gh issue close 415 --comment "Shipped. A scroll logo setting was added for sticky/transparent headers."
gh issue close 392 --comment "Shipped in 2.36.0. Custom image orientations can be added via config.php and appear in the Customizer and Mai Post/Term Grid blocks."
gh issue close 274 --comment "Addressed across multiple releases. Default pullquote and blockquote styling were reworked."
gh issue close 520 --comment "Done. Kirki v4 has been in production since 2023."
gh issue close 304 --comment "Done. The Tested up to header is maintained (currently WordPress 7.0)."
gh issue close 106 --comment "Fixed. Mai uses a custom adjacent entry nav (class-mai-entry.php) that does not rely on post_type support, so it no longer always shows."
gh issue close 44  --comment "Fixed in f4987ff68. Sub-menus in the header-before area now work on mobile."
gh issue close 67  --comment "Delivered in 2.39.0 as the mai_style_guide shortcode (logo, typography, headings, colors, buttons, lists, blockquote). Drop it on a page for a style guide."
```

### Resolved — alignment + verify-first (6)
```bash
gh issue close 499 --comment "Closing as fixed. The editor alignwide system was reworked in 2.35.0 and again in 2.39.1 (canvas/iframe width routing, valid width expression). Align Wide now renders wider than the content column in the editor, so the original behavior no longer applies."
gh issue close 614 --comment "Closing as cannot reproduce / superseded. When this was filed the alignwide width was invalid CSS that browsers dropped (width fell back to auto), and it was not reproducible at the time. The alignwide system has since been rewritten, and the one real overflow case (alignwide beside a sidebar) is now explicitly capped. Please reopen with exact alignment and layout settings if it still occurs."
gh issue close 543 --comment "Closing as fixed. This was the same grid field-query issue as #93 (dropdowns not loading/saving in the editor). Resolved by the Dec 2024 Mai Post/Term Grid fixes (098794415, 6c88db636) plus ACF updates. Please reopen if it recurs on current develop."
gh issue close 457 --comment "Closing as implemented. Font preloading (lib/functions/fonts.php) and image preloading for featured/page-header images (lib/functions/images.php) ship today. If a specific case is still missing (e.g. first-block cover or webp), open a focused issue."
gh issue close 156 --comment "Closing as implemented. Headings use a fluid type scale with clamp() and vw-based scaling (assets/scss/base/_globals.scss), so they scale on smaller screens."
gh issue close 324 --comment "Closing as resolved. The Separator block has dedicated .is-style-dots styling. Please reopen with a specific theme/config if a dots separator still renders incorrectly."
```

### Won't-fix (6)
```bash
gh issue close 638 --comment "Closing as working-as-intended. This is an ACF taxonomy-field limitation; use the mai_term_grid_query_args filter for multi-taxonomy queries."
gh issue close 658 --comment "Closing. The main logo is intentionally used on mobile (the scroll logo is typically an icon/emblem, not the full brand logo). Please reopen with the version where the behavior changed if you believe this regressed."
gh issue close 301 --comment "Closing as intended behavior. Term/taxonomy descriptions display on the first archive page only; intro text always shows. Documentation, not a bug."
gh issue close 368 --comment "Closing. Deferred at the time as premature, and no demand since. Reopen if there is a concrete need for first/last mobile-order helper classes."
gh issue close 441 --comment "Closing as addressed. On AMP pages the JS-based header menus are replaced with a native amp-sidebar (since 2.7.0), and search items are excluded from it. The remaining 'layout a bit off' note is too vague to action years later; please reopen with a specific AMP URL and screenshot if needed."
gh issue close 321 --comment "Closing as won't-fix. The transparent/sticky header transition is driven by scroll JavaScript, which AMP does not allow, so it cannot work on AMP pages without a full AMP-native reimplementation. Given AMP's reduced relevance this is not planned."
```

### Setup-wizard cluster (stale / obsolete)
```bash
gh issue close 525 --comment "Closing as stale. No reports in years. Please reopen if demo import still fails on full-size images on a current setup."
gh issue close 444 --comment "Closing as stale. No reports of setup wizard timeouts in years. Please reopen if it recurs."
gh issue close 99  --comment "Closing as obsolete. This was about WooCommerce's old onboarding nudging users to switch themes, which WooCommerce removed years ago. Reopen if there is a concrete need to formally declare WooCommerce theme support."
gh issue close 5   --comment "Closing as obsolete. This was a 2020 note to borrow from genesis-sample's WooCommerce setup. Mai Engine has since built comprehensive WooCommerce support (cart/checkout blocks, account navigation, product pages, button classes, and more), so this is superseded."
gh issue close 133 --comment "Closing as stale. This 2020 styling note on the WooCommerce product search has had no recurrence, and search/Woo styling has been reworked since. Please reopen with a current screenshot if the search button still renders poorly."
gh issue close 135 --comment "Closing as stale. This 2020 styling note on the legacy WooCommerce cart widget has had no recurrence; that classic widget has largely been superseded by Woo cart blocks, which Mai styles. Please reopen with a current screenshot if needed."
```

### Verified fixed / can't-reproduce
```bash
gh issue close 554 --comment "Closing as fixed. Verified with the layout flipped to RTL (html dir=rtl): no horizontal overflow. Addressed by the horizontal-scroll fixes (30683c1d2, 63fdceff2, 60074f429), scrollbar-gutter: stable, and the move to logical properties."
gh issue close 616 --comment "Closing as not currently reproducible; no recurrence in a while and the icon renders correctly on current WordPress. If it shows black again, the likely cause is the icon SVG using fill=currentColor while loaded as a base64 background image (no color context to inherit). Reopen with the WP version and admin color scheme and we can hardcode the menu-icon color."
```

### Consolidate #570 into #540
```bash
gh issue comment 540 --body "Folding #570 into this issue. Scope: (1) non-boxed full-aligned grid has no side spacing; (2) boxed full-aligned grid keeps border, radius, and shadow. Preferred approach per earlier notes is PHP (helper class / inline props) rather than brittle attribute-selector CSS. Not slated for 2.39.1."
gh issue close 570 --comment "Consolidating into #540, which covers the same full-aligned Mai grid styling problem (no side spacing when not boxed; border/radius/shadow when boxed). Tracking there."
```
