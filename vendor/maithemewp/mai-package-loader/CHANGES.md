# Changelog

## [0.1.0] - unreleased

### Added

- First version. Loads the newest copy of each shared mai library, whichever plugin loads first, from the first moment any plugin uses one, and hands over to a newer copy of itself that ships `takeover.php`. See `docs/specs/2026-10-03-mai-package-loader.md`.
