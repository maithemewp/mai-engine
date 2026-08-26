# Kirki patches

Kirki is vendored by hand into `packages/kirki/` rather than installed by composer, because Themeum published no GitHub release between v4.2.0 (June 2023) and v5.2.0-beta.1 (February 2026). The version we need, 5.1.0, only ever existed on the master branch and was never tagged, so composer could not install it.

Pinned to upstream `themeum/kirki` master at commit `edf27a0b` (2024-03-21).

Note: that commit's `kirki.php` header says `Version: 5.1.0` while `KIRKI_VERSION` says `5.0.0`. That mismatch is upstream's own bug, present verbatim in their master for 20 months. 5.1.0 is the accurate version, confirmed by their `CHANGELOG.md` and `readme.txt`.

## Why we are still pinned

Themeum did resume releasing. v5.2.3 shipped 2026-04-10. We are not on it for three reasons.

**Two bugs we reported against 5.2.3, both still open and unanswered after four months:**

- [#2570](https://github.com/themeum/kirki/issues/2570) webfontloader.js outputs HTML instead of JavaScript
- [#2569](https://github.com/themeum/kirki/issues/2569) querySelector SyntaxError with nav menu sections breaks the Customizer

Both are artifacts of the 5.2.x restructure and exist in no earlier version. #2569 is their jQuery to vanilla JS migration: jQuery wrapped `querySelectorAll` in a try/catch and fell back to its own selector engine, so a section id like `nav_menu[394]` parsed harmlessly. Raw `document.querySelector` throws. #2570 is their release ZIP builder, `dev/build-zip.mjs`, running a blanket `find ... -name "*.js" -delete` over the packages directory with no exclusion for `webfontloader.js`. The file exists in the repo but not in the shipped ZIP, so it 404s and the browser parses the HTML error page as JavaScript. Their other build script, `dev/build.sh:75`, already carries the exclusion.

Both have small clean fixes we could offer upstream: wrap the six `document.querySelector` selector strings in `CSS.escape()`, and add the missing `! -path` exclusion to `build-zip.mjs`.

**The 5.2.x restructure makes upgrading a re-vendor, not a bump.** `kirki-packages/` moved under `customizer/packages/`, jQuery was removed, React went to 18, and third party packages were dropped.

**A silent regression upstream has never fixed.** Commit `067d7a52` (2024-03-24) typo'd the color sanitizer regex as `\d{2]`, a closing bracket instead of a brace, in three branches of `control-color-palette/src/Field/Color_Palette.php`. Three digit hue `hsl()`, `hsv()` and `hsva()` values silently stop sanitizing. `preg_last_error` stays clean, so it fails quietly. Verified on PHP 8.4: `hsl(200, 50%, 50%)` sanitizes at our pinned commit and fails at anything later. Still present on master.

### Reviewed 2026-08-26: staying at `edf27a0b`

We evaluated bumping to `4ba58e9f` (2025-12-01), the last commit before the restructure began. Rejected.

That 46 commit window is almost entirely a new `pnpm-lock.yaml`, a dev build script, and about 20 single word comment typo fixes. The only functional changes are an L10n hook move (inert for us, we ship no `languages/` directory so the registry lookup returns nothing), a `public $compiler;` declaration in `Kirki\Compatibility\Field` (never constructed on our path, we only use the modern `\Kirki\Field\*` API), and a `kirki_googlefonts_subset` filter we do not use.

Against that we would inherit the color sanitizer regression above, for no gain.

If we ever do bump, add a third patch reverting `\d{2]` to `\d{2}` in `kirki-packages/control-color-palette/src/Field/Color_Palette.php`.

## Applying these patches after a re-vendor

Both still apply to current upstream, but the paths moved in 5.2.x:

| Patch | Path at 5.1.0 | Path at 5.2.x |
|---|---|---|
| `kirki-option-default.diff` | `kirki-packages/data-option/src/Option.php` | `customizer/packages/utils/data-option/src/Option.php` |
| `kirki-webfonts-useragent.diff` | `kirki-packages/module-webfonts/src/Webfonts/Downloader.php` | `customizer/packages/modules/webfonts/src/Webfonts/Downloader.php` |

## The patches

### kirki-option-default.diff

**Keep this one.** In `kirki_get_value()`, when a nested option key like `foo[bar]` is missing, upstream returns an empty string. We return the field's declared `$default` instead. `$default` is already a parameter of the method, so this is a behavior fix rather than a hack.

This patch was undocumented anywhere until 2026-08-26. Until then the only record of it was a local branch named `kirki` with no remote, carrying the July 2024 commits that first vendored Kirki. That branch was deleted on 2026-08-26 once this file existed: its file content was byte identical to `develop`, so nothing unique was lost. Its tip was `e6295448899caacc2ff8b449ac78adc015c66074` if it is ever needed from the reflog.

### kirki-webfonts-useragent.diff

**Safe to drop.** It adds one commented out line recording the old Safari user-agent. The active user-agent line is byte identical to upstream. It changes nothing at runtime and is kept only as a breadcrumb to [#2524](https://github.com/themeum/kirki/issues/2524).

## Removed patches

### The md5 filename hack (removed 2026-08-26)

`Downloader.php` used to write font files as `md5( $url )` with no extension. That was an emergency fix for [#2524](https://github.com/themeum/kirki/issues/2524), where the old Safari user-agent made Google return extensionless URLs like `/l/font?kit=...`. `basename()` returned the literal string `font` for every variant, so all of them collided onto one file.

Upstream fixed the root cause by changing the user-agent in commit `6908691b`, 49 minutes before the commit we vendored. We already had the real fix, so the hack was redundant and actively harmful: stripping the extension meant extension keyed server cache rules never matched, so fonts had no `Cache-Control` header and served as `application/octet-stream`. Every repeat visitor re-downloaded them.

Verified before removal against the live Google Fonts v1 API using Kirki's exact request shape and user-agent: 222 URLs across 16 families, including icon, emoji, variable and CJK fonts, all carry a `.woff2` extension, and deduplicated basenames are collision free both within and across families.

Confirmed in production on larrybrownsports.com, which had both naming styles side by side on the same Kontrol v4 stack:

| File | Content-Type | Cache-Control |
|---|---|---|
| `tinos/buE2poGnedXvwjX-TmZJ9Q.woff2` | `font/woff2` | `max-age=315360000, public` |
| `tinos/0d64fc8cedb98870356394c83785751b` | `application/octet-stream` | none |

Do not reintroduce it. If a future font source ever does produce colliding basenames, add the extension back rather than replacing the filename:

    $extension = pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
    $filename  = md5( $url ) . ( $extension ? '.' . $extension : '' );

## Worth doing

There is no patch mechanism here. These diffs are a record, not an enforcement. A re-vendor still silently overwrites everything and nothing fails loudly.

Moving Kirki to a composer dependency with `cweagans/composer-patches` would fix that, and would also drop 2,156 files and about 14MB out of this repo's history. We already commit `vendor/` and already use VCS repositories for `mai-cache` and `deployable-guard`, so the groundwork exists. That is its own piece of work, and it should happen before any Kirki upgrade rather than after.
