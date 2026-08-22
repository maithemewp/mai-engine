# mai-engine: composer phpcs passes everything, silently

**Repo:** maithemewp/mai-engine
**Found:** 2026-08-22 · **Status:** not fixed, two small changes needed

## The symptom

`composer phpcs` prints nothing, writes nothing to stderr, and exits 0. For every input. Including `--version`. Including a deliberately malformed file.

Any CI step or git hook gating on it has been passing without checking anything.

Not a PHP version issue: identical on 8.3 and 8.4.

## Cause 1: the autoloader exits before phpcs starts

`vendor/autoload.php` files-autoloads mai-cache's `init.php`, which opens with:

```php
defined( 'ABSPATH' ) || exit;
```

Outside WordPress that exits with **status 0 and no output**. `vendor/bin/phpcs` is a Composer proxy that requires the project autoloader first, so it dies on that line and looks like it succeeded.

`tests/phpunit/unit/bootstrap.php` already documents this exact trap, for the same reason:

> "Reorder these two lines and the process exits with status 0 and no output at all, which reads as 'no tests ran' rather than as an error."

The test suites work because they define `ABSPATH` before requiring the autoloader. The composer scripts have no way to do that.

Proof:

```bash
printf '<?php define("ABSPATH", sys_get_temp_dir()."/");\n' > /tmp/abspath.php
php -d auto_prepend_file=/tmp/abspath.php vendor/bin/phpcs --version
# PHP_CodeSniffer version 3.13.5 (stable) by Squiz and PHPCSStandards
```

**Fix:** add a two-line bootstrap file that defines `ABSPATH`, and point the `phpcs` and `phpcbf` composer scripts at it with `-d auto_prepend_file=`.

## Cause 2: the WordPress standard was never registered

Once it runs, phpcs reports:

> ERROR: the "WordPress" coding standard is not installed. The installed coding standards are MySource, PEAR, PSR1, PSR2, PSR12, Squiz and Zend

`wp-coding-standards/wpcs` is on disk but its path was never registered. `composer.json` already has an `install-codestandards` script for this; it has not run.

**Fix:** run `composer install-codestandards`, or register the paths directly:

```bash
php -d auto_prepend_file=/tmp/abspath.php vendor/bin/phpcs --config-set installed_paths \
  vendor/wp-coding-standards/wpcs,vendor/phpcsstandards/phpcsutils,vendor/phpcsstandards/phpcsextra,vendor/sirbrillig/phpcs-variable-analysis
```

That writes to a gitignored file in `vendor/`, so it is per-machine and does not survive `composer install`.

## Before you switch it on as a gate

The WordPress standard as configured flags **short array syntax**, which this codebase uses everywhere. `lib/classes/class-mai-grid.php` alone has 54 pre-existing violations of that one rule. Turning phpcs on as a gate without narrowing the ruleset will light up the whole codebase over a convention the project has never followed.

Narrow the ruleset first, or the gate is unusable and gets switched off again.
