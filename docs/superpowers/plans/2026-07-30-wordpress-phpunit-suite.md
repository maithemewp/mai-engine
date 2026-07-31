# WordPress-Integrated PHPUnit Suite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a second PHPUnit suite that boots real WordPress via `wp-phpunit`, alongside the existing WordPress-free brain/monkey suite, without ever putting a with-dev Composer autoloader into the committed `vendor/`.

**Architecture:** Test dependencies move out of the root `composer.json` into a separate `tests/composer.json` that installs to `tests/vendor/`, so running tests never regenerates the plugin's committed autoloader. Two PHPUnit configs with separate bootstraps: the existing root config keeps the WordPress-free `unit` suite, and a new `tests/phpunit/integration/phpunit.xml.dist` boots WordPress without activating the plugin.

**Tech Stack:** PHP 8.1 and 8.4, PHPUnit 10.5, brain/monkey, wp-phpunit 7.0, roots/wordpress-no-content 7.0, yoast/phpunit-polyfills 2.0, MySQL 8, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md`

## Global Constraints

- PHP floor is `^8.1`. Every new Composer requirement must accept 8.1.
- The committed root `vendor/` autoloader must never contain dev entries. `deployable-guard` must pass at every commit. Never run `composer install` or `composer dump-autoload` at the repo root without following it with `composer dump-autoload --no-dev`.
- `npm run start`, `styles`, `blocks`, `dev`, `beta`, `release` must keep working unchanged.
- The existing WordPress-free unit suite must keep running with no WordPress.
- Never push any branch to GitHub without explicit per-push consent from the user.
- No em-dashes in shipped text: commit messages, code comments, changelog entries. Use commas, semicolons, periods or parentheses.
- WordPress pin is `^7.0` for both `wp-phpunit/wp-phpunit` and `roots/wordpress-no-content`.
- `tests/vendor/` must be gitignored **before** any `composer install -d tests` runs. Task 2 enforces this ordering and it is not negotiable.
- The plugin deploys as a raw git tree with no build step. Anything committed ships to production.

## Execution order: fix-first

**Revised 2026-07-30.** The encoding defect this plan's fixture was meant to de-risk turned
out to be a confirmed stored XSS, reproduced end to end and copied across several other Mai
plugins. See "Security finding" in the spec. The fix now leads. Nothing below is discarded;
the tasks are reordered and three are added.

| Phase | Tasks | Status |
|---|---|---|
| A | Task 1 | **DONE** `691298f89`. Suite went red to green, 69 tests. |
| B | Task 7 | **DONE** `2bec7b900`. 63-row fixture on the existing suite, no new deps. |
| C | Tasks 10, 11 | **DONE** `204eae813`. Corpus diff over 289,572 posts, then the fix. |
| D | Task 12 | **DONE** `69240d3`, `426746a`, `96d83ee`. Three plugins, delegate with fallback. |
| E | Tasks 2, 3, 4, 5, 6, 8, 9 | **DONE.** See the commit list below. |

All phases shipped. Phase E commits: `df26fa6e3` (tests/composer.json plus the two gitignore
lines), `be8408603` (root composer migration, define-abspath.php deleted), `1478462e1`
(phpunit source scoping), `3ee5035f2` (integration harness), `05d0cc0a2`
(RenderBlockLinkColorTest), `1327bce50` (CI), `c272da3bb` (README).

Two things differed from the plan as written. WP_PHP_BINARY needed
`escapeshellarg( PHP_BINARY )`, not bare `PHP_BINARY`: the wp-phpunit bootstrap concatenates
it into `system()` unquoted, and Herd installs PHP under a path containing a space. And the
smoke test was kept, renamed to HarnessTest, rather than deleted, because it covers the
harness itself (database, utf8mb4, factories, loader) and no feature test does.

Phases A through D shipped. What follows below for tasks 10 through 12 is the plan as
written before execution; the spec's "Encoding migration: shipped" section is the record of
what was actually built and why it differs. The short version: task 11 proposed candidate C,
and the task 10 corpus diff disqualified it by finding 12,257 posts of legacy CP1252 content
it would corrupt. The shipped fix keeps the encode and makes the decode selective.

Phase B was the key scheduling fact, and it paid off: `DomEncodingTest` is a plain unit test
in the existing suite, needing none of `tests/composer.json`, wp-phpunit, MySQL or CI. The
safety net landed immediately rather than behind the harness work, which is what made the
corpus diff possible before any code changed.

The release question is answered: this rides the next release, 2.41 or 2.40.x. Retired
plugins (`_legacy/*`) are out of scope by decision. Nothing has been pushed.

## File Structure

**Created:**
- `tests/composer.json`: test-only dependency manifest and PSR-4 map
- `tests/composer.lock`: committed, pins the WordPress and PHPUnit versions
- `tests/phpunit/unit/fixtures/encoding.php`: the encoding characterization table
- `tests/phpunit/unit/DomEncodingTest.php`: drives the fixture
- `tests/phpunit/integration/bootstrap.php`: boots WordPress via wp-phpunit
- `tests/phpunit/integration/wp-tests-config.php`: DB and constants, env-driven
- `tests/phpunit/integration/plugin-loader.php`: curated `lib/` requires on `muplugins_loaded`
- `tests/phpunit/integration/MaiIntegrationTestCase.php`: PHPUnit 10 compatibility shim plus assertion helpers
- `tests/phpunit/integration/phpunit.xml.dist`: integration suite config
- `tests/phpunit/integration/RenderBlockLinkColorTest.php`: the link-color regression suite
- `.github/workflows/tests.yml`: unit and wp jobs

**Modified:**
- `tests/phpunit/unit/MaiQueryCacheInvalidationTest.php`: add the missing stub (Task 1)
- `.gitignore`: two lines, ordering-sensitive (Task 2)
- `composer.json`: remove test deps, delete `autoload-dev`, rewire scripts (Task 3)
- `phpunit.xml.dist`: scope `<source>`, fix stale glob (Task 4)
- `tests/phpunit/unit/bootstrap.php`: absorb the comment from the deleted file (Task 3)
- `README.md`: testing section (Task 9)

**Deleted:**
- `tests/phpunit/define-abspath.php`: dead once PHPUnit runs from `tests/vendor/` (Task 3)

---

### Task 1: Fix the already-red unit test

The suite is red on `develop` before any of this work. It must be green before CI is added in Task 8.

**Files:**
- Modify: `tests/phpunit/unit/MaiQueryCacheInvalidationTest.php:9-12`

**Interfaces:**
- Consumes: nothing
- Produces: a green `unit` suite, which every later task's verification depends on

- [ ] **Step 1: Reproduce the failure**

Run: `composer test-unit`

Expected: `Tests: 69, Assertions: 90, Errors: 1, Warnings: 1` with

```
MaiQueryCacheInvalidationTest::test_pre_query_passes_through_when_not_cacheable
Error: Call to undefined function wp_using_ext_object_cache()
  lib/classes/class-mai-query-cache.php:282
```

Note this runs `composer dump` first, which dirties the root autoloader. That is expected for now and Task 3 removes it. Do not commit anything from `vendor/` in this task.

- [ ] **Step 2: Add the stub**

`Mai_Query_Cache::pre_query()` reaches `use_single_flight()`, which calls `wp_using_ext_object_cache()`. The sibling `MaiQueryCacheSingleFlightTest` stubs it per-test; this class needs it class-wide because several tests reach the same path. Add to `setUp()`:

```php
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
		// pre_query() reaches use_single_flight(), which calls this. Off by default here so
		// the invalidation tests exercise the plain path; MaiQueryCacheSingleFlightTest owns
		// the on/off behavior itself.
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
	}
```

- [ ] **Step 3: Verify green**

Run: `composer test-unit`

Expected: `OK (69 tests, ...)`, exit 0. If a warning remains, read it and fix it in this task; the suite must be clean, not merely error-free.

- [ ] **Step 4: Restore the autoloader before committing**

Run: `composer dump-autoload --no-dev && php vendor/bin/deployable-guard check`

Expected: `OK: committed autoloader is deployable as-is.`

- [ ] **Step 5: Commit**

```bash
git add tests/phpunit/unit/MaiQueryCacheInvalidationTest.php
git status --short
git commit -m "test(query-cache): stub wp_using_ext_object_cache in the invalidation suite"
```

`git status --short` must show nothing from `vendor/` staged. If it does, stop and re-run Step 4.

---

### Task 2: Test dependency manifest and gitignore

These land in one commit because the gitignore lines must exist before anything installs into `tests/vendor/`.

**Files:**
- Create: `tests/composer.json`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: nothing
- Produces: `tests/vendor/bin/phpunit`, `tests/vendor/autoload.php`, and WordPress core at `tests/vendor/roots/wordpress-no-content/`. Every later task depends on these paths.

- [ ] **Step 1: Add the gitignore lines FIRST**

`vendor/*` is anchored to the `.gitignore`'s own directory and does **not** match `tests/vendor/`. `npm run beta` runs `git add -A` and pushes to `develop` and `beta`, both of which deploy as raw git trees to live sites. Without this, beta would push roughly 98 MB and 3,006 files of WordPress core and PHPUnit to production branches.

Insert `!tests/composer.lock` immediately after the existing `*.lock` on line 8, and add `tests/vendor/` at the end of the file:

```
*.lock
!tests/composer.lock
```

```
.phpunit.result.cache
.githooks/
tests/vendor/
```

The negation must come **after** `*.lock`. Reversed, `*.lock` wins and the lock file is silently never committed, leaving CI to resolve unpinned on every run.

- [ ] **Step 2: Verify the ignore rules resolve correctly**

Run:

```bash
git check-ignore --no-index -v tests/composer.lock; echo "exit=$?"
git check-ignore --no-index -v tests/vendor/anything.php; echo "exit=$?"
```

Expected: `tests/composer.lock` matches `!tests/composer.lock` and exits 1 (not ignored). `tests/vendor/anything.php` matches `tests/vendor/` and exits 0 (ignored).

- [ ] **Step 3: Create `tests/composer.json`**

```json
{
  "name": "maithemewp/mai-engine-tests",
  "description": "Test-only dependencies for mai-engine. Installed into tests/vendor/ so the plugin's committed vendor/ autoloader is never regenerated with dev entries.",
  "license": "gpl-2.0-or-later",
  "require": {
    "php": "^8.1",
    "phpunit/phpunit": "^10.5",
    "brain/monkey": "^2.6",
    "wp-phpunit/wp-phpunit": "^7.0",
    "yoast/phpunit-polyfills": "^2.0",
    "roots/wordpress-no-content": "^7.0"
  },
  "autoload": {
    "psr-4": {
      "BizBudding\\MaiEngine\\Tests\\": "",
      "BizBudding\\MaiEngine\\Tests\\Unit\\": "phpunit/unit/",
      "BizBudding\\MaiEngine\\Tests\\Integration\\": "phpunit/integration/"
    }
  },
  "minimum-stability": "stable"
}
```

Two deliberate omissions. There is no `extra.wordpress-install-dir`: `roots/wordpress-no-content` declares no dependency on `roots/wordpress-core-installer`, so with no plugin registered for type `wordpress-core` Composer ignores that setting entirely and installs to `vendor/roots/wordpress-no-content/`. Rather than adding a plugin to relocate it, `wp-tests-config.php` points at where Composer actually puts it. There is also no `allow-plugins` block, because no dependency in this set is a Composer plugin.

`wp-phpunit` and `roots/wordpress-no-content` are meant to move together, and only via `composer update -d tests`. JSON has no comments, so that constraint is recorded in the README instead (Task 9).

- [ ] **Step 4: Install and verify the layout**

Run:

```bash
composer install -d tests
ls tests/vendor/bin/phpunit
ls tests/vendor/roots/wordpress-no-content/wp-settings.php
tests/vendor/bin/phpunit --version
```

Expected: all three paths exist, and PHPUnit reports 10.5.x.

If `wp-settings.php` is missing, WordPress landed somewhere else. Run `find tests/vendor -name wp-settings.php` and use that directory in Task 5 instead of assuming.

- [ ] **Step 5: Verify root vendor/ was untouched**

This is the whole point of the design. Run:

```bash
git status --short -- vendor/
git diff --exit-code -- vendor/; echo "vendor diff exit=$?"
git status --short -- tests/vendor; echo "(must print nothing)"
php vendor/bin/deployable-guard check
```

Expected: no output from the first three, `vendor diff exit=0`, and `OK: committed autoloader is deployable as-is.`

- [ ] **Step 6: Commit**

```bash
git add .gitignore tests/composer.json tests/composer.lock
git status --short
git commit -m "test: add an isolated composer project for test dependencies

Installs to tests/vendor/ so running tests never regenerates the plugin's
committed autoloader. Pins WordPress to 7.0 to match the fleet."
```

`git status --short` must show exactly those three files staged and nothing from `tests/vendor/`.

---

### Task 3: Migrate the root composer.json off test dependencies

**Files:**
- Modify: `composer.json`
- Modify: `tests/phpunit/unit/bootstrap.php:1-7`
- Delete: `tests/phpunit/define-abspath.php`

**Interfaces:**
- Consumes: `tests/vendor/bin/phpunit` from Task 2
- Produces: `composer test-unit`, `composer test-integration`, `composer test`, `composer test-setup`

- [ ] **Step 1: Remove the test packages properly**

Do **not** hand-edit `require-dev`. `composer.lock` is gitignored but present on every machine, and `composer install` installs from the lock, so a hand edit leaves the packages installed indefinitely. This step needs the ACF Pro credential in `auth.json`, so it runs locally, once.

```bash
composer remove --dev phpunit/phpunit brain/monkey wp-phpunit/wp-phpunit
```

- [ ] **Step 2: Delete the `autoload-dev` block**

Remove this entire block from `composer.json`:

```json
  "autoload-dev": {
    "psr-4": {
      "BizBudding\\MaiEngine\\Tests\\Unit\\": "tests/phpunit/unit/",
      "BizBudding\\MaiEngine\\Tests\\Integration\\": "tests/php/integration/"
    }
  },
```

The `tests/php/integration/` path never existed and disagreed with the `test-integration` script. Both namespaces now live in `tests/composer.json`. This deletion is verified safe: `composer dump-autoload --no-dev --optimize` produces a byte-identical `vendor/composer/` tree with and without it, because `--no-dev` already ignores `autoload-dev`.

- [ ] **Step 3: Rewire the scripts**

Replace the two existing test scripts and add two more:

```json
    "test-setup": "composer install -d tests",
    "test-unit": "tests/vendor/bin/phpunit --testsuite unit --color=always",
    "test-integration": "tests/vendor/bin/phpunit --configuration tests/phpunit/integration/phpunit.xml.dist --color=always",
    "test": [
      "@test-unit",
      "@test-integration"
    ],
```

`test-unit` no longer runs `composer dump`, which is what used to dirty the root autoloader on every test run. `test-setup` is deliberately **not** hooked to `post-install-cmd`; doing so would make `npm run dev` download roughly 98 MB of WordPress core for developers who never run tests.

- [ ] **Step 4: Move the ABSPATH comment into the unit bootstrap**

`tests/phpunit/define-abspath.php` exists because PHPUnit's Composer bin proxy required the root `vendor/autoload.php` before any bootstrap ran, and that eagerly loads `mai-cache/init.php`, which exits on an undefined `ABSPATH`. With `tests/vendor/bin/phpunit`, the proxy resolves to `tests/vendor/autoload.php` and never reaches root vendor.

The invariant that survives is subtle and fails **silently**, with exit code 0 and no output. Preserve the reasoning. Edit `tests/phpunit/unit/bootstrap.php` so the top reads:

```php
<?php
// tests/phpunit/unit/bootstrap.php
// Unit suite: no WordPress, no DB. brain/monkey mocks WP functions.

// ABSPATH must be defined BEFORE the root autoloader is required below. That autoloader
// eagerly loads vendored WordPress drop-ins guarded by `defined( 'ABSPATH' ) || exit`
// (mai-cache's init.php, via autoload.files). Reorder these two lines and the process
// exits with status 0 and no output at all, which reads as "no tests ran" rather than
// as an error. tests/vendor/autoload.php has its own files-autoloader, but nothing in
// it is ABSPATH-guarded, which is why the phpunit bin proxy can load safely.
defined( 'ABSPATH' ) || define( 'ABSPATH', sys_get_temp_dir() . '/' );

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/TestCase.php';
```

Leave the rest of the file, including the `Mai_*` autoloader shim, unchanged.

- [ ] **Step 5: Delete the dead file**

```bash
git rm tests/phpunit/define-abspath.php
```

- [ ] **Step 6: Verify the unit suite still runs with no WordPress**

Run: `composer test-unit`

Expected: `OK (69 tests, ...)`, exit 0, identical to Task 1's result. If the process exits 0 with no output at all, the bootstrap lines were reordered; re-read Step 4.

- [ ] **Step 7: Verify the deploy-critical invariants**

```bash
composer validate --no-check-lock; echo "validate exit=$?"
composer dump-autoload --no-dev --optimize
php vendor/bin/deployable-guard check
git diff --stat -- vendor/
```

Expected: `validate exit=0` (warnings are acceptable, errors are not), guard reports OK, and the `vendor/` diff is empty or limited to churn that also occurs on an unmodified checkout.

- [ ] **Step 8: Commit**

```bash
git add composer.json tests/phpunit/unit/bootstrap.php
git add -u tests/phpunit/define-abspath.php
git status --short
git commit -m "test: move test dependencies out of the root composer project

Running the suites no longer regenerates the committed autoloader, so a test
run can no longer leave the tree in a state the deployable guard rejects."
```

---

### Task 4: Scope the root PHPUnit source and fix the stale glob

**Files:**
- Modify: `phpunit.xml.dist:19-24`

**Interfaces:**
- Consumes: nothing
- Produces: nothing consumed by later tasks

- [ ] **Step 1: Replace the `<source>` block**

The current `<directory suffix=".php">.</directory>` already covers 4,653 vendor files and 1,085 Kirki files, and `tests/vendor/` now adds roughly 3,006 more. Measured cost today is zero because no coverage driver is installed, but the deferred `failOnDeprecation` work depends on `<source>` for attribution, at which point WordPress core and PHPUnit would be classified as first-party code.

```xml
    <source>
        <include>
            <directory suffix=".php">./lib/</directory>
            <directory suffix=".php">./config/</directory>
        </include>
    </source>
```

The `<exclude><file>./tests/TestCase.php</file></exclude>` is no longer needed, since `tests/` is outside the include set now. Remove it.

- [ ] **Step 2: Fix the stale fixtures exclude**

The testsuite's `<exclude>./tests/phpunit/unit/*/*/fixtures/</exclude>` does not match the `tests/phpunit/unit/fixtures/` directory Task 7 creates. Correct it:

```xml
            <exclude>./tests/phpunit/unit/fixtures/</exclude>
```

This is belt-and-braces, since only `*Test.php` files are collected, but the glob was evidently written for exactly this case.

- [ ] **Step 3: Verify the same tests still run**

Run: `composer test-unit`

Expected: `OK (69 tests, ...)`. The test count must be unchanged from Task 3. If it changed, the testsuite element was edited by mistake rather than the source element.

- [ ] **Step 4: Commit**

```bash
git add phpunit.xml.dist
git commit -m "test: scope phpunit source to lib and config, fix the fixtures exclude"
```

---

### Task 5: Integration harness

**Files:**
- Create: `tests/phpunit/integration/wp-tests-config.php`
- Create: `tests/phpunit/integration/plugin-loader.php`
- Create: `tests/phpunit/integration/bootstrap.php`
- Create: `tests/phpunit/integration/MaiIntegrationTestCase.php`
- Create: `tests/phpunit/integration/phpunit.xml.dist`

**Interfaces:**
- Consumes: `tests/vendor/` from Task 2, `composer test-integration` from Task 3
- Produces: class `BizBudding\MaiEngine\Tests\Integration\MaiIntegrationTestCase` with public method `assertNoTagHasClass( string $html, string $class, string $message = '' ): void`. Task 6 extends this class and calls that helper.

- [ ] **Step 1: Create the test database**

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS mai_engine_tests"
```

If your MySQL root user has a password, export `WP_TESTS_DB_PASS` before running the suite.

**Warning:** the WordPress test bootstrap drops the WordPress core tables carrying the configured `$table_prefix` in this database on every run. It does not drop every table, but pointing it at a live site's database with a matching prefix destroys that site's content. Keep the dedicated database name.

- [ ] **Step 2: Write `wp-tests-config.php`**

```php
<?php
/**
 * Config for the WordPress-loaded integration suite.
 *
 * WARNING: the WordPress test bootstrap DROPS the WordPress core tables carrying the
 * configured $table_prefix in this database on every run. Never point it at a real site.
 */

// roots/wordpress-no-content installs here because no wordpress-core installer plugin is
// present, so composer falls back to vendor/<vendor>/<name>. See tests/composer.json.
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/vendor/roots/wordpress-no-content/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'mai_engine_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASS' ) ?: '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Mai Engine Tests' );

// PHP_BINARY, not the string 'php'. The bootstrap shells out to run install.php, and with
// a CI matrix spanning 8.1 and 8.4 the string form would install WordPress under whatever
// php resolves to in PATH rather than the interpreter running the tests.
define( 'WP_PHP_BINARY', PHP_BINARY );

define( 'WP_DEBUG', true );
```

`$table_prefix` must be a plain local variable, not a constant. The wp-phpunit shim depends on it being one.

- [ ] **Step 3: Write `plugin-loader.php`**

```php
<?php
/**
 * Loads the pieces of mai-engine under test, on muplugins_loaded.
 *
 * The plugin is deliberately NOT activated. lib/init.php expects Genesis as the parent
 * theme, and Genesis is neither in this repo nor composer-installable, so a real
 * activation cannot run in CI. This makes the suite "WordPress-loaded tests" rather than
 * full-plugin integration: real WP_HTML_Tag_Processor, WP_Query, options and posts, but
 * no Genesis-dependent code path.
 *
 * Add files here as tests need them. Keep the list curated; requiring all of lib/ would
 * drag in the Genesis dependency this design exists to avoid.
 */

$plugin_root = dirname( __DIR__, 3 );

// The plugin's committed runtime autoloader. Note this is not inert: it files-autoloads
// plugin-update-checker (registers an autoloader and factory versions) and mai-cache
// (defines Mai_Cache_Bootstrap, registers an autoloader). The integration suite therefore
// depends on the committed autoloader being valid, which deployable-guard enforces.
require_once $plugin_root . '/vendor/autoload.php';

require_once $plugin_root . '/lib/functions/helpers.php';
require_once $plugin_root . '/lib/blocks/general.php';
```

- [ ] **Step 4: Write `bootstrap.php`**

```php
<?php
/**
 * Bootstrap for the WordPress-loaded integration suite.
 */

$tests_root = dirname( __DIR__, 2 );

// Must come first: this autoloader's files-autoload includes wp-phpunit's __loaded.php,
// which is what sets WP_PHPUNIT__DIR.
require_once $tests_root . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require_once __DIR__ . '/plugin-loader.php';
	}
);

require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
```

Ordering matters: `tests_add_filter()` is defined by the explicit `includes/functions.php` require, which must happen before it is called. wp-phpunit's own bootstrap re-requires that file with `require_once`, so there is no conflict.

- [ ] **Step 5: Write `MaiIntegrationTestCase.php`**

`WP_UnitTestCase_Base::set_up()` unconditionally calls `expectDeprecated()`, which calls `PHPUnit\Util\Test::parseTestMethodAnnotations()` and `TestCase::getName()`. Both were removed in PHPUnit 10, so every test extending `WP_UnitTestCase` errors before its first assertion. WordPress core has not adopted PHPUnit 10, and wp-phpunit 7.0.2 carries the identical code, so this is not fixed by a version bump.

```php
<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

abstract class MaiIntegrationTestCase extends WP_UnitTestCase {

	/**
	 * PHPUnit 10 removed PHPUnit\Util\Test::parseTestMethodAnnotations() and
	 * TestCase::getName(), both of which WP_UnitTestCase_Base::expectDeprecated() calls.
	 * Re-register the same hooks without the annotation parsing. The only thing lost is
	 * the @expectedDeprecated / @expectedIncorrectUsage docblock annotations, which
	 * PHPUnit 10 does not read anyway; setExpectedDeprecated() and
	 * setExpectedIncorrectUsage() still work from inside a test body.
	 */
	public function expectDeprecated() {
		add_action( 'deprecated_function_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_argument_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_class_run', [ $this, 'deprecated_function_run' ], 10, 3 );
		add_action( 'deprecated_file_included', [ $this, 'deprecated_function_run' ], 10, 4 );
		add_action( 'deprecated_hook_run', [ $this, 'deprecated_function_run' ], 10, 4 );
		add_action( 'doing_it_wrong_run', [ $this, 'doing_it_wrong_run' ], 10, 3 );

		add_action( 'deprecated_function_trigger_error', '__return_false' );
		add_action( 'deprecated_argument_trigger_error', '__return_false' );
		add_action( 'deprecated_class_trigger_error', '__return_false' );
		add_action( 'deprecated_file_trigger_error', '__return_false' );
		add_action( 'deprecated_hook_trigger_error', '__return_false' );
		add_action( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Assert no tag anywhere in the document carries a class.
	 *
	 * WP_HTML_Tag_Processor::has_class() is scoped to one tag, so it cannot express the
	 * document-wide negative that actually catches the lost-rewrite regression. It also
	 * returns null (not false) when the processor is not on a tag, so a bare
	 * assertFalse( $p->has_class( ... ) ) passes whether the class is absent or the tag
	 * was never found.
	 */
	public function assertNoTagHasClass( string $html, string $class, string $message = '' ): void {
		$tags = new WP_HTML_Tag_Processor( $html );

		while ( $tags->next_tag() ) {
			$this->assertNotTrue(
				$tags->has_class( $class ),
				$message ?: sprintf( 'Expected no tag to carry "%s", found one in: %s', $class, $html )
			);
		}
	}
}
```

- [ ] **Step 6: Write `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="bootstrap.php"
         cacheDirectory="../../../.phpunit.cache/integration"
         backupGlobals="false"
         colors="true"
         beStrictAboutCoverageMetadata="true"
         failOnRisky="true"
         failOnWarning="true">

    <testsuites>
        <testsuite name="integration">
            <directory suffix="Test.php">./</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">../../../lib/</directory>
        </include>
    </source>
</phpunit>
```

`cacheDirectory` is set because PHPUnit 10 warns without it, and because both suites run from the repo root and would otherwise share `.phpunit.result.cache`. `beStrictAboutOutputDuringTests` is deliberately absent: the WordPress bootstrap echoes status lines.

- [ ] **Step 7: Write a throwaway smoke test**

Create `tests/phpunit/integration/SmokeTest.php`:

```php
<?php

namespace BizBudding\MaiEngine\Tests\Integration;

final class SmokeTest extends MaiIntegrationTestCase {

	public function test_wordpress_is_loaded(): void {
		$this->assertTrue( class_exists( 'WP_HTML_Tag_Processor' ) );
		$this->assertTrue( function_exists( 'mai_isset' ) );
	}

	public function test_plugin_loader_registered_the_filter(): void {
		$this->assertNotFalse( has_filter( 'render_block', 'mai_render_block_handle_link_color' ) );
	}

	public function test_factories_work(): void {
		$id = self::factory()->post->create( [ 'post_title' => 'Zażółć gęślą 🎉' ] );
		$this->assertSame( 'Zażółć gęślą 🎉', get_post( $id )->post_title );
	}
}
```

The third test proves both the DB round-trip and that `utf8mb4` is configured, which matters for the content this plugin handles.

- [ ] **Step 8: Run it**

Run: `composer test-integration`

Expected: `OK (3 tests, 4 assertions)`. First run is slow because it installs WordPress into the test database.

If it fails with `Call to undefined method PHPUnit\Util\Test::parseTestMethodAnnotations()`, the test extended `WP_UnitTestCase` directly instead of `MaiIntegrationTestCase`. If it fails on `require ABSPATH . 'wp-settings.php'`, run `find tests/vendor -name wp-settings.php` and correct `ABSPATH` in `wp-tests-config.php`.

- [ ] **Step 9: Delete the smoke test and commit**

The smoke test proved the harness; Task 6 supersedes it.

```bash
rm tests/phpunit/integration/SmokeTest.php
git add tests/phpunit/integration/
git status --short
git commit -m "test: add a WordPress-loaded integration harness

Boots WordPress via wp-phpunit without activating the plugin, since lib/init.php
requires Genesis. MaiIntegrationTestCase works around WP_UnitTestCase calling
PHPUnit 9 APIs that PHPUnit 10 removed."
```

Also confirm `.phpunit.cache/` is ignored. `.gitignore` already has `.cache` but not `.phpunit.cache`; if `git status` shows it, add `.phpunit.cache/` to `.gitignore` in this commit.

---

### Task 6: RenderBlockLinkColorTest

**Files:**
- Create: `tests/phpunit/integration/RenderBlockLinkColorTest.php`

**Interfaces:**
- Consumes: `MaiIntegrationTestCase` and its `assertNoTagHasClass()` from Task 5; `mai_render_block_handle_link_color()` loaded by `plugin-loader.php`
- Produces: nothing consumed by later tasks

Every expected string below was measured against the current implementation. Do not adjust them to taste; if one does not match, that is a finding.

- [ ] **Step 1: Write the regression locks**

These four fail against the historical buggy versions. BUG1 is the guard that matched only `has-link-color`, missing `has-link-background-color` (introduced `366b63788`, fixed `d2a559cb2`). BUG2 is the lost text rewrite when text and background both used the Link color (fixed `89250280f`).

```php
<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers mai_render_block_handle_link_color() in lib/blocks/general.php.
 *
 * Two shipped regressions are locked here, and four known-defective behaviors are pinned
 * as characterization so a future fix shows up as a deliberate diff. See F1 and F2 in
 * docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md.
 */
final class RenderBlockLinkColorTest extends MaiIntegrationTestCase {

	private function render( string $html, array $attrs ): string {
		// Always pass an attrs key. Without one the function emits three "Undefined array
		// key" warnings, which failOnWarning turns into a test failure.
		return mai_render_block_handle_link_color( $html, [ 'attrs' => $attrs ] );
	}

	// ---- Regression locks ----

	/** BUG1: a guard matching only has-link-color skips this entirely. */
	public function test_background_color_is_renamed(): void {
		$out = $this->render(
			'<div class="wp-block-group has-link-background-color"><p>x</p></div>',
			[ 'backgroundColor' => 'link' ]
		);

		$this->assertSame( '<div class="wp-block-group has-links-background-color"><p>x</p></div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG1, and the only case reaching the $overlay branch. */
	public function test_overlay_color_is_renamed(): void {
		$out = $this->render(
			'<div class="wp-block-cover has-link-background-color"><p>x</p></div>',
			[ 'overlayColor' => 'link' ]
		);

		$this->assertSame( '<div class="wp-block-cover has-links-background-color"><p>x</p></div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG2: the background pass used to rebuild the processor and drop the text rewrite. */
	public function test_text_and_background_together_both_survive(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color">x</div>',
			[ 'textColor' => 'link', 'backgroundColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color has-links-background-color">x</div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-color' );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG2 through the second branch of the same condition. */
	public function test_text_and_overlay_together_both_survive(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color">x</div>',
			[ 'textColor' => 'link', 'overlayColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color has-links-background-color">x</div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-color' );
	}
```

- [ ] **Step 2: Write the characterization pins**

These record defects. They pass today and must keep passing until someone deliberately fixes the underlying bug, at which point the diff makes the change visible.

```php
	// ---- Characterization pins: known defects, see F1 and F2 in the spec ----

	/**
	 * PINS F1. Each pass breaks after the first match, so a second highlight in the same
	 * paragraph keeps has-link-color, which WordPress 6.4+ reads as link element color.
	 * Fixing this is not just deleting the break: the guard comment notes this filter runs
	 * for every block on every page, so removing the early exit needs its own measurement.
	 */
	public function test_only_the_first_mark_is_renamed(): void {
		$out = $this->render(
			'<p><mark class="has-link-color">one</mark> and <mark class="has-link-color">two</mark></p>',
			[]
		);

		$this->assertSame(
			'<p><mark class="has-links-color">one</mark> and <mark class="has-link-color">two</mark></p>',
			$out
		);
	}

	/** PINS F1, text pass. */
	public function test_only_the_first_text_element_is_renamed(): void {
		$out = $this->render(
			'<div class="has-link-color"><span class="has-link-color">x</span></div>',
			[ 'textColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color"><span class="has-link-color">x</span></div>', $out );
	}

	/** PINS F1, background pass. */
	public function test_only_the_first_background_element_is_renamed(): void {
		$out = $this->render(
			'<div class="has-link-background-color"><span class="has-link-background-color">x</span></div>',
			[ 'backgroundColor' => 'link' ]
		);

		$this->assertSame(
			'<div class="has-links-background-color"><span class="has-link-background-color">x</span></div>',
			$out
		);
	}

	/**
	 * PINS F2. The implementation splits classes on the space character; the HTML API
	 * splits on all ASCII whitespace. So the unset() misses and both classes survive.
	 */
	public function test_tab_separated_class_list_keeps_both_classes(): void {
		$out = $this->render( "<p class=\"has-text-color\thas-link-color\">x</p>", [ 'textColor' => 'link' ] );

		$this->assertSame( "<p class=\"has-text-color\thas-link-color has-links-color\">x</p>", $out );
	}
```

- [ ] **Step 3: Write the no-op and guard coverage**

```php
	// ---- No-op and guard coverage ----

	/** The guard is a raw substring check, so text content can pass it with nothing to match. */
	public function test_has_link_in_text_content_is_left_alone(): void {
		$html = '<p>the has-link-color class</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** Guard passes on the background class, but textColor finds no matching element. */
	public function test_text_color_with_no_matching_element_is_unchanged(): void {
		$html = '<p class="has-link-background-color">x</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** Early return: no has-link- substring at all. */
	public function test_content_without_link_classes_passes_through(): void {
		$html = '<p class="has-text-color">x</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** The mark pass, text pass and background pass all applying to one block. */
	public function test_mark_text_and_background_all_apply(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color"><mark class="has-link-color">m</mark></div>',
			[ 'textColor' => 'link', 'backgroundColor' => 'link' ]
		);

		$this->assertSame(
			'<div class="has-links-color has-links-background-color"><mark class="has-links-color">m</mark></div>',
			$out
		);
	}
}
```

- [ ] **Step 4: Run and verify**

Run: `composer test-integration`

Expected: `OK (12 tests, ...)`.

If `test_text_and_background_together_both_survive` fails, the BUG2 fix has been reverted. If either background test fails, BUG1 has returned.

- [ ] **Step 5: Commit**

```bash
git add tests/phpunit/integration/RenderBlockLinkColorTest.php
git commit -m "test(blocks): cover mai_render_block_handle_link_color

Locks the two shipped regressions (the has-link-color guard missing background
and overlay, and the lost text rewrite) and pins four known defects so a future
fix is a deliberate diff."
```

Note for the record, not for a test: the trailing hyphen in the `has-link-` guard is a performance-only refinement. The inner `next_tag()` matchers use full class names, so a `has-link` guard produces byte-identical output. It cannot be tested through this function's return value.

---

### Task 7: Encoding characterization fixture

**Files:**
- Create: `tests/phpunit/unit/fixtures/encoding.php`
- Create: `tests/phpunit/unit/DomEncodingTest.php`

**Interfaces:**
- Consumes: `mai_get_dom_document()` and `mai_get_dom_html()` from `lib/functions/utilities.php`
- Produces: the fixture file, which is the hard prerequisite for the separate encoding migration spec

The goldens cannot be hand-written. They are generated from the current implementation, then reviewed by hand. Goldens are stored as `\u{...}` escapes rather than raw UTF-8: an earlier draft of the spec recorded one golden with two spaces where the real output has U+0020 followed by U+00A0, lost to a copy/paste round trip. That is exactly the failure this fixture exists to catch.

- [ ] **Step 1: Write the generator**

Create `tests/phpunit/unit/fixtures/generate.php`. This is a one-shot developer tool, committed alongside the fixture so goldens can be regenerated deliberately.

```php
<?php
/**
 * Regenerates encoding.php from the CURRENT implementation.
 *
 * Run: php tests/phpunit/unit/fixtures/generate.php
 *
 * This records what ships TODAY, defects included. Every regenerated golden must be
 * reviewed by hand before committing. Group G3 in particular records dangerous behavior
 * (escaped markup becoming live markup); it is pinned, not endorsed. See F3 in the spec.
 */

define( 'ABSPATH', sys_get_temp_dir() . '/' );

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
	function add_action() {}
}

require_once dirname( __DIR__, 3 ) . '/lib/functions/utilities.php';

/** Escape every non-printable-ASCII byte so the golden survives copy/paste. */
function mai_test_escape( string $s ): string {
	$out = '';
	foreach ( preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY ) as $ch ) {
		$cp = mb_ord( $ch, 'UTF-8' );
		if ( 0x20 <= $cp && 0x7E >= $cp && '\\' !== $ch && '"' !== $ch && '$' !== $ch ) {
			$out .= $ch;
		} elseif ( "\n" === $ch ) {
			$out .= '\n';
		} elseif ( "\t" === $ch ) {
			$out .= '\t';
		} else {
			$out .= sprintf( '\u{%X}', $cp );
		}
	}
	return $out;
}

$cases = require __DIR__ . '/inputs.php';

$php = "<?php\n";
$php .= "/**\n";
$php .= " * GENERATED by generate.php. Do not hand-edit goldens.\n";
$php .= " *\n";
$php .= " * Characterization of mai_get_dom_document() + mai_get_dom_html() as they ship TODAY,\n";
$php .= " * defects included. Group g3 pins dangerous behavior deliberately; see F3 in\n";
$php .= " * docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md.\n";
$php .= " */\n\nreturn [\n";

foreach ( $cases as $key => $in ) {
	$out  = mai_get_dom_html( mai_get_dom_document( $in ) );
	$php .= sprintf(
		"\t%s => [\n\t\t'in'  => \"%s\",\n\t\t'out' => \"%s\",\n\t],\n",
		var_export( $key, true ),
		mai_test_escape( $in ),
		mai_test_escape( $out )
	);
}

$php .= "];\n";

file_put_contents( __DIR__ . '/encoding.php', $php );

echo 'Wrote ' . count( $cases ) . " cases to encoding.php\n";
```

- [ ] **Step 2: Write the input table**

Create `tests/phpunit/unit/fixtures/inputs.php`. Six groups. G2 is not optional: it is the entire divergence surface between the current implementation and the leading replacement candidate, and without it the fixture produces zero signal for that comparison.

```php
<?php
/**
 * Inputs for the encoding characterization fixture. Goldens live in encoding.php,
 * generated by generate.php.
 */

return [
	// G1: baseline content classes.
	'g1_polish_diacritics'   => '<p>Zażółć gęślą jaźń</p>',
	'g1_curly_quotes'        => '<p>“quoted” and ‘single’ and it’s</p>',
	'g1_dashes'              => '<p>a — b – c</p>',
	'g1_entity_amp'          => '<p>&amp;</p>',
	'g1_entity_lt_gt'        => '<p>&lt;tag&gt;</p>',
	'g1_entity_quot'         => '<p>&quot;q&quot;</p>',
	'g1_entity_apos'         => '<p>&#039;a&#039;</p>',
	'g1_entity_nbsp'         => '<p>a&nbsp;b</p>',
	'g1_double_escaped'      => '<p>&amp;amp;</p>',
	'g1_bare_ampersand'      => '<p>Tom & Jerry</p>',
	'g1_amp_in_href'         => '<a href="?a=1&amp;b=2">link</a>',
	'g1_emoji_bmp'           => '<p>☕</p>',
	'g1_emoji_astral'        => '<p>🎉</p>',
	'g1_emoji_zwj'           => '<p>👩‍💻</p>',
	'g1_numeric_entity'      => '<p>&#380;</p>',
	'g1_nested_markup'       => '<p><strong>bold</strong> and <em>italic</em></p>',
	'g1_combined'            => '<p>Zażółć &amp; “curly” — 🎉&nbsp;end</p>',

	// G2: Unicode noncharacters and the numeric boundary. The entire divergence surface
	// between the current implementation and candidate A. Do not drop this group.
	'g2_null'                => '<p>&#0;</p>',
	'g2_surrogate'           => '<p>&#55296;</p>',
	'g2_fdd0'                => '<p>&#64976;</p>',
	'g2_replacement'         => '<p>&#65533;</p>',
	'g2_fffe'                => '<p>&#65534;</p>',
	'g2_ffff'                => '<p>&#65535;</p>',
	'g2_10fffd'              => '<p>&#1114109;</p>',
	'g2_10fffe'              => '<p>&#1114110;</p>',
	'g2_10ffff'              => '<p>&#1114111;</p>',
	'g2_out_of_range'        => '<p>&#1114112;</p>',

	// G3: escaping-sensitive. PINS DANGEROUS BEHAVIOR. See F3 and F5 in the spec.
	'g3_escaped_script'      => '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>',
	'g3_escaped_img_onerror' => '<p>&lt;img src=x onerror=alert(1)&gt;</p>',
	'g3_quot_in_attribute'   => '<a title="a&quot; onmouseover=&quot;alert(1)">x</a>',
	'g3_apos_in_attribute'   => '<a title="a&#039;b">x</a>',
	'g3_lt_in_attribute'     => '<a title="a&lt;b">x</a>',
	'g3_script_body'         => '<script>if (a &amp;&amp; b &lt; c) x("&quot;");</script>',
	'g3_style_body'          => '<style>a[title="&quot;"] { color: red; }</style>',
	'g3_textarea'            => '<textarea>&lt;b&gt; &amp; é</textarea>',
	'g3_pre'                 => '<pre>&lt;tag&gt; &amp;amp;</pre>',

	// G7: quotes, and JSON in data attributes. PINS F5, a live HTML corruption bug:
	// saveHTML() escapes these attributes correctly and the decode step then breaks them
	// out of their own quotes. Every g7_data_* row below is currently corrupted output.
	'g7_straight_and_curly'  => '<p>She said "hi" and \'bye\' then “hi” and ‘bye’ and it’s fine</p>',
	'g7_polish_both_cases'   => '<p>ZAŻÓŁĆ GĘŚLĄ JAŹŃ / zażółć gęślą jaźń / ĄĆĘŁŃÓŚŹŻ ąćęłńóśźż</p>',
	'g7_typographic'         => '<p>© ® ™ ° ½ € £ ¥ § ¶ † ‡ • … ‰ ± × ÷ ≠ ≤ ≥ → ← ↔</p>',
	'g7_data_json_single'    => '<div data-config=\'{"title":"Zażółć","q":"say \"hi\"","n":1,"ok":true}\'>x</div>',
	'g7_data_json_escaped'   => '<div data-config="{&quot;title&quot;:&quot;A &amp; B&quot;,&quot;n&quot;:1}">x</div>',
	'g7_data_mixed_attrs'    => '<button data-a="1" data-label="Zażółć “x”" data-json=\'{"k":"v & w"}\' aria-label="It’s">go</button>',
	'g7_data_url_and_json'   => '<a href="/x?a=1&amp;b=2&amp;c=%20" data-track=\'{"u":"/x?a=1&b=2"}\'>l</a>',
	'g7_kitchen_sink'        => '<div class="c" data-cfg=\'{"t":"Zażółć & “curly”","d":"—"}\'><p>He said "it’s" — 🎉 &amp; &nbsp;done</p></div>',

	// G4: malformed and structural.
	'g4_unclosed_tag'        => '<p>unclosed',
	'g4_crossed_tags'        => '<p><b>x</p></b>',
	'g4_stray_lt'            => '<p>a < b</p>',
	'g4_stray_gt'            => '<p>a > b</p>',
	'g4_comment_with_entity' => '<p>x</p><!-- &amp; comment -->',
	'g4_cdata'               => '<p><![CDATA[a & b]]></p>',
	'g4_empty'               => '',
	'g4_text_only'           => 'plain text',

	// G5: international content beyond G1.
	'g5_rtl_arabic'          => '<p>مرحبا بالعالم</p>',
	'g5_rtl_hebrew'          => '<p>שלום עולם</p>',
	'g5_bidi_controls'       => "<p>a\u{200E}b\u{200F}c\u{202B}d\u{202C}e</p>",
	'g5_cjk'                 => '<p>日本語のテキスト</p>',
	'g5_nfc_precomposed'     => "<p>\u{00E9}</p>",
	'g5_nfd_combining'       => "<p>e\u{0301}</p>",
	'g5_vietnamese'          => '<p>Tiếng Việt nghiêng</p>',
	'g5_raw_nbsp'            => "<p>a\u{00A0}b</p>",
	'g5_soft_hyphen'         => "<p>sig\u{00AD}nal</p>",

	// G6: inline SVG. lib/functions/icons.php is a caller, and HTML named entities are
	// not valid in XML or SVG.
	'g6_inline_svg'          => '<svg viewBox="0 0 10 10"><title>A &amp; B</title><path d="M0 0"/></svg>',
	'g6_svg_with_accent'     => '<svg><title>Zażółć é</title></svg>',
];
```

- [ ] **Step 3: Generate and review the goldens**

Run:

```bash
php tests/phpunit/unit/fixtures/generate.php
```

Expected: `Wrote 63 cases to encoding.php` (G1 17, G2 10, G3 9, G4 8, G5 9, G6 2, G7 8).

Now **read `encoding.php` end to end**. This is a required review step, not a formality. For each row ask whether the recorded output is what should happen. Expect to be uncomfortable with the `g3_` rows: escaped markup becomes live markup, and attribute values break out of their quotes. That is the F3 defect, and pinning it is the point. Do not "fix" a golden by editing it; if a golden looks wrong, that is a finding to raise, and the encoding spec owns the fix.

Confirm specifically that `g4_empty` records `"\n"` rather than `""`, and that every golden ends with `\n`, since `saveHTML()` appends one to every output.

- [ ] **Step 4: Write the test**

```php
<?php

namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;

require_once dirname( __DIR__, 3 ) . '/lib/functions/utilities.php';

/**
 * Characterization of mai_get_dom_document() + mai_get_dom_html().
 *
 * This does not assert correct behavior. It asserts CURRENT behavior, so that migrating
 * off the deprecated mb_convert_encoding( ..., 'HTML-ENTITIES' ) call becomes a measured
 * comparison instead of a third guess. Two prior attempts were reverted after non-English
 * content broke.
 *
 * Regenerate goldens with: php tests/phpunit/unit/fixtures/generate.php
 */
final class DomEncodingTest extends TestCase {

	public static function encodingCases(): array {
		$out = [];
		foreach ( require __DIR__ . '/fixtures/encoding.php' as $key => $case ) {
			$out[ $key ] = [ $case['in'], $case['out'] ];
		}
		return $out;
	}

	/**
	 * @dataProvider encodingCases
	 */
	public function test_round_trip_matches_golden( string $in, string $expected ): void {
		$this->assertSame( $expected, mai_get_dom_html( mai_get_dom_document( $in ) ) );
	}

	public function test_fixture_covers_every_group(): void {
		$keys = array_keys( require __DIR__ . '/fixtures/encoding.php' );

		foreach ( [ 'g1_', 'g2_', 'g3_', 'g4_', 'g5_', 'g6_', 'g7_' ] as $group ) {
			$this->assertNotEmpty(
				array_filter( $keys, static fn ( $k ) => str_starts_with( $k, $group ) ),
				sprintf( 'Fixture group %s is empty. g2_ in particular is the entire divergence surface for the migration.', $group )
			);
		}
	}
}
```

The `@dataProvider` annotation works here because this suite does not extend `WP_UnitTestCase`; PHPUnit 10 reads data providers from attributes and annotations both. If a deprecation warning appears about annotation-based providers, convert to `#[DataProvider('encodingCases')]`.

- [ ] **Step 5: Run and verify**

Run: `composer test-unit`

Expected: `OK (133 tests, ...)` (69 existing, 63 fixture rows, 1 group check). Every fixture row must pass on the first run, since the goldens were generated from the same implementation. A failure means the generator and the test disagree about the input, most likely an escaping bug in `mai_test_escape()`.

- [ ] **Step 6: Commit**

```bash
git add tests/phpunit/unit/fixtures/ tests/phpunit/unit/DomEncodingTest.php
git commit -m "test(utilities): characterize the DOM encoding round trip

53 fixtures across baseline content, Unicode noncharacters, escaping-sensitive
input, malformed HTML, international text and inline SVG. Records current
behavior, defects included, so the HTML-ENTITIES migration can be a measured
comparison rather than a third guess."
```

---

### Task 8: CI workflow

**Files:**
- Create: `.github/workflows/tests.yml`

**Interfaces:**
- Consumes: `composer test-unit` and `composer test-integration` from Task 3
- Produces: nothing consumed by later tasks

`.github/workflows/deployable.yml` is not modified. Each GitHub Actions job gets its own checkout, so a test job cannot contaminate the deployable job.

- [ ] **Step 1: Write the workflow**

```yaml
name: Tests

# Neither job runs a root `composer install`, so no ACF Pro credential is needed. A bare
# checkout plus `composer install -d tests` is sufficient: the plugin's runtime deps are
# committed to vendor/, and only the test deps are resolved.

on: [push, pull_request]

jobs:
  unit:
    name: Unit (PHP ${{ matrix.php }})
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.4']
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, libxml, mbstring, json
          coverage: none

      - name: Install test dependencies
        run: composer install -d tests --no-interaction --no-progress

      - name: Run the unit suite
        run: composer test-unit

      - name: Committed vendor/ untouched
        run: git diff --exit-code -- vendor/

      - name: tests/vendor is not tracked
        run: |
          count=$(git ls-files tests/vendor | wc -l | tr -d ' ')
          test "$count" -eq 0 || { echo "tests/vendor has $count tracked files"; exit 1; }

  wp:
    name: WordPress (PHP 8.4)
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: mai_engine_tests
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -uroot -proot"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=10
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, libxml, mbstring, json, mysqli
          coverage: none

      - name: Install test dependencies
        run: composer install -d tests --no-interaction --no-progress

      - name: Run the integration suite
        env:
          WP_TESTS_DB_NAME: mai_engine_tests
          WP_TESTS_DB_USER: root
          WP_TESTS_DB_PASS: root
          WP_TESTS_DB_HOST: 127.0.0.1
        run: composer test-integration

      - name: Committed vendor/ untouched
        run: git diff --exit-code -- vendor/

      - name: tests/vendor is not tracked
        run: |
          count=$(git ls-files tests/vendor | wc -l | tr -d ' ')
          test "$count" -eq 0 || { echo "tests/vendor has $count tracked files"; exit 1; }
```

The `mysql` service image refuses to start without `MYSQL_ROOT_PASSWORD` or `MYSQL_ALLOW_EMPTY_PASSWORD`, hence the explicit password threaded through to `WP_TESTS_DB_PASS`.

On the two closing assertions: `git ls-files tests/vendor` is the one that catches something real, namely a `.gitignore` regression that would let `npm run beta` push WordPress core to a deploy branch. `git diff --exit-code -- vendor/` is a tripwire proving the test job did not dirty the committed autoloader; it is **not** proof that the committed autoloader is deployable, since CI diffs the working tree against the same commit. `deployable.yml` remains the real guard for that.

- [ ] **Step 2: Verify locally before committing**

CI cannot be tested without pushing, and pushing needs explicit consent. Simulate what the runner does, from a clean clone so root `vendor/` is exactly what is committed:

```bash
tmp=$(mktemp -d)
git clone --depth 1 --branch "$(git rev-parse --abbrev-ref HEAD)" . "$tmp/mai-engine"
cd "$tmp/mai-engine"
composer install -d tests --no-interaction --no-progress
composer test-unit
git diff --exit-code -- vendor/; echo "vendor diff exit=$?"
git ls-files tests/vendor | wc -l
cd -
```

Expected: unit suite green, `vendor diff exit=0`, and `0` tracked files under `tests/vendor`. Clean up with `rm -rf "$tmp"`.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "ci: run both test suites on push and pull request

Neither job runs a root composer install, so no ACF credential is needed and the
committed autoloader is never regenerated. Asserts that on the way out."
```

Do not push. Pushing requires explicit consent from the user.

---

### Task 9: README testing section

**Files:**
- Modify: `README.md`

**Interfaces:**
- Consumes: the scripts from Task 3
- Produces: nothing

- [ ] **Step 1: Add the section**

Place it after the existing development or installation section, matching the surrounding heading level.

```markdown
## Testing

Two suites. The `unit` suite runs with no WordPress and no database, using brain/monkey to
mock WordPress functions. The `integration` suite boots real WordPress via wp-phpunit and
needs MySQL.

Test dependencies live in their own Composer project at `tests/composer.json` and install
to `tests/vendor/`. That is deliberate: the plugin deploys as a raw git tree with no build
step, so the committed `vendor/` autoloader must never contain dev entries, and keeping the
test dependencies out of the root project means running tests cannot regenerate it.

### Setup

```bash
composer test-setup                                   # installs tests/vendor, one time
mysql -u root -e "CREATE DATABASE mai_engine_tests"   # integration suite only, one time
```

`npm run dev` does **not** install the test dependencies. If `composer test-unit` reports
`tests/vendor/bin/phpunit: No such file or directory`, run `composer test-setup`.

### Running

```bash
composer test-unit          # no WordPress, no database, sub-second
composer test-integration   # boots WordPress, needs MySQL
composer test               # both
```

Database connection is read from `WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASS`
and `WP_TESTS_DB_HOST`, defaulting to `mai_engine_tests` / `root` / empty / `127.0.0.1`.

**Warning:** the WordPress test bootstrap drops the WordPress core tables carrying the
configured `$table_prefix` in the configured database, on every run. It does not drop every
table, but pointing it at a real site's database with a matching prefix destroys that
site's content. Keep the dedicated database name.

### Notes

- `wp-phpunit/wp-phpunit` and `roots/wordpress-no-content` in `tests/composer.json` are
  meant to track together. Bump both, and only via `composer update -d tests`.
- The integration suite boots WordPress but does not activate the plugin, because
  `lib/init.php` expects Genesis as the parent theme. Tests load the specific `lib/` files
  they exercise via `tests/phpunit/integration/plugin-loader.php`.
- Integration tests extend `MaiIntegrationTestCase`, not `WP_UnitTestCase` directly. The
  base class works around `WP_UnitTestCase` calling PHPUnit 9 APIs that PHPUnit 10 removed.
- Encoding fixture goldens are generated, not hand-written. Regenerate with
  `php tests/phpunit/unit/fixtures/generate.php` and review every changed golden by hand.
```

- [ ] **Step 2: Verify the commands as written**

Run each command from the README verbatim in a shell. Any that fails is a documentation bug to fix now, not later.

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: document the two test suites and their setup"
```

---

---

### Task 10: Real-content corpus diff

Read-only. Establishes what candidate C actually changes across real editorial content,
because the fixture only covers failure modes that were anticipated.

**Files:**
- Create: scratch only, nothing committed to the repo

**Interfaces:**
- Consumes: `mai_get_dom_document()` / `mai_get_dom_html()` from Task 7's fixture work
- Produces: a reviewed list of content shapes that change, which gates Task 11

- [ ] **Step 1: Write the corpus differ**

Save to the scratch directory, not the repo. Run per site via `wp eval-file`.

```php
<?php
// corpus-diff.php  ->  wp --path=<site> eval-file corpus-diff.php

/** Candidate C: no encode, no decode, UTF-8 declared to libxml. */
function mai_dom_candidate_c( string $html ): string {
	$dom  = new DOMDocument( '1.0', 'UTF-8' );
	$prev = libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="UTF-8">' . "<div>$html</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$c = $dom->getElementsByTagName( 'div' )->item( 0 );
	if ( ! $c ) { libxml_clear_errors(); libxml_use_internal_errors( $prev ); return $html; }
	$c = $c->parentNode->removeChild( $c );
	while ( $dom->firstChild ) { $dom->removeChild( $dom->firstChild ); }
	while ( $c->firstChild ) { $dom->appendChild( $c->firstChild ); }
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	return $dom->saveHTML();
}

global $wpdb;
$rows    = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','pending','private') AND post_content <> ''" );
$changed = 0;

foreach ( $rows as $row ) {
	$current = mai_get_dom_html( mai_get_dom_document( $row->post_content ) );
	$next    = mai_dom_candidate_c( $row->post_content );
	if ( $current === $next ) { continue; }
	$changed++;
	echo "--- post {$row->ID} ---\n";
	// Print only the first differing line so the output stays reviewable.
	$a = explode( "\n", $current );
	$b = explode( "\n", $next );
	foreach ( $a as $i => $line ) {
		if ( ( $b[ $i ] ?? null ) !== $line ) {
			echo "  current: " . trim( $line ) . "\n  next   : " . trim( $b[ $i ] ?? '' ) . "\n";
			break;
		}
	}
}

printf( "%d of %d posts change\n", $changed, count( $rows ) );
```

- [ ] **Step 2: Run it across every local site that has mai-engine**

```bash
for s in /Users/jivedig/Herd/*/; do
  [ -d "$s/wp-content/plugins/mai-engine" ] || continue
  echo "===== $(basename $s) ====="
  wp --path="$s" eval-file /path/to/corpus-diff.php 2>/dev/null | tail -40
done
```

This only reads. It writes nothing to any database.

- [ ] **Step 3: Review every distinct change shape by hand**

Group the output by what kind of change it is, not by post. Expect the known categories:
pre-escaped entities staying escaped, and attribute values keeping their escaping. Anything
outside those two categories is a finding and blocks Task 11 until understood.

Ask the user which sites carry Polish or other non-English content and read those first.

- [ ] **Step 4: Record the result in the spec**

Append the measured counts and the reviewed change categories to the spec's "Verification
cannot rest on synthetic fixtures alone" section, then commit that doc change alone.

---

### Task 11: Apply candidate C to mai-engine

**Files:**
- Modify: `lib/functions/utilities.php:1371-1429`
- Test: `tests/phpunit/unit/DomEncodingTest.php` (from Task 7)

**Interfaces:**
- Consumes: the fixture from Task 7, the corpus review from Task 10
- Produces: the reference implementation that Task 12 copies to the other plugins

- [ ] **Step 1: Confirm the fixture is green before touching anything**

Run: `composer test-unit`. Expected: `OK`. If it is not green, stop.

- [ ] **Step 2: Change both functions**

In `mai_get_dom_document()`, drop the `mb_encode_numericentity()` line and change the load:

```php
	// UTF-8 is declared to libxml directly rather than pre-encoding to numeric entities.
	// NOIMPLIED/NODEFDTD keep libxml from synthesizing html/body/doctype around the fragment.
	$dom->loadHTML( '<?xml encoding="UTF-8">' . "<div>$html</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
```

In `mai_get_dom_html()`, remove the decode entirely and replace the historical comment:

```php
function mai_get_dom_html( $dom ) {
	// No entity decode. The previous mb_convert_encoding( ..., 'HTML-ENTITIES' ) call decoded
	// entities across the whole document, which turned escaped text such as &lt;script&gt;
	// back into live markup and broke JSON in data attributes out of their own quotes. See
	// the security finding in docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md.
	return $dom->saveHTML();
}
```

- [ ] **Step 3: Regenerate and diff the fixture goldens**

```bash
php tests/phpunit/unit/fixtures/generate.php
git diff tests/phpunit/unit/fixtures/encoding.php
```

Expected: roughly 14 of 63 rows change, every one of them the current implementation losing
escaping. The `g7_data_*` rows must go from corrupted to correctly escaped, and the
`g3_escaped_*` rows must stay escaped. Any row where a Polish, quote, emoji or typographic
case changes is a stop condition, not something to accept.

- [ ] **Step 4: Re-run the real smoke test**

```bash
url=$(wp --path=/Users/jivedig/Herd/sportsdataio eval 'echo get_permalink(2937);')
curl -sk "$url" | grep -cE '<script>alert\(1\)</script>'
```

Expected: `0`. Before the fix this returns `1`.

- [ ] **Step 5: Run the whole suite and commit**

```bash
composer test-unit
composer dump-autoload --no-dev && php vendor/bin/deployable-guard check
git add lib/functions/utilities.php tests/phpunit/unit/fixtures/encoding.php
git commit -m "fix(security): stop decoding entities across the serialized document

mai_get_dom_html() decoded HTML entities over the whole document, so escaped text
such as &lt;script&gt; became live markup and JSON in data attributes was broken
out of its own quotes. A Contributor without unfiltered_html could get executable
script onto any page containing a group block with a background color.

Declares UTF-8 to libxml instead of pre-encoding to numeric entities, and returns
saveHTML() unmodified. Verified against a 63 row encoding fixture and a real
content corpus diff across local sites."
```

Do not push. Pushing needs explicit consent.

---

### Task 12: Propagate the fix to the other affected plugins

Retired plugins under `_legacy/` are out of scope by decision.

**Files:**
- Modify: `~/Plugins/mai-custom-content-areas/includes/utilities.php:286-287`
- Modify: `~/Plugins/mai-table-of-contents/classes/class-table-of-contents.php:359-360`
- Modify: `~/Plugins/mai-url-parameter-content/classes/class-mai-upa.php:122-123`

**Interfaces:**
- Consumes: the reference implementation from Task 11
- Produces: nothing

- [ ] **Step 1: Confirm each copy before editing**

For each plugin, read the surrounding function. These are independent copies, not wrappers,
and they may differ in wrapper handling. Do not assume the mai-engine diff applies verbatim.

- [ ] **Step 2: Apply the same change per plugin, one commit each**

Same two edits as Task 11: declare UTF-8 to libxml on load, drop the decode on save. Each
plugin gets its own commit with its own message referencing the mai-engine fix.

- [ ] **Step 3: Verify each against the same content**

These plugins have no test suite. Verify by running the encoding fixture inputs through the
changed function directly in a scratch script, and confirm the `g3_` and `g7_` shapes behave
as they do in mai-engine after Task 11.

- [ ] **Step 4: Check for other copies before declaring done**

```bash
grep -rn "HTML-ENTITIES" /Users/jivedig/Plugins/ --include='*.php' | grep -v vendor | grep -v _legacy
```

Expected: no output. Any hit is an unfixed copy.

---

## Verification

After all tasks, from a clean clone:

```bash
composer test-setup
composer test
php vendor/bin/deployable-guard check
git status --short
```

Expected: both suites green, guard reports OK, working tree clean.

## Deferred, not part of this plan

- **Retired plugins.** `_legacy/mai-ctas`, `_legacy/mai-performance-enhancer` and `_legacy/mai-ads-extra-content` carry the same decode but are not maintained and are not being fixed, by decision.
- **F1 and F2.** Pinned by Task 6, fixed separately. F1 needs a performance measurement, since removing the `break` turns a bounded scan into a full subtree walk on every block of every page.
- **Moving the linting tools into `tests/composer.json`.** `phpcs`, `php-cs-fixer`, `wpcs` and `phpcompatibility-wp` stay in root `require-dev`, so a plain `composer install` still writes a dev autoloader. The pre-commit hook still covers it.
