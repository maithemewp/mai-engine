# Mai Package Loader

Loads the newest copy of each shared mai library, whichever plugin loads first.

When several plugins bundle the same library, such as mai-cache, PHP can only load one copy of each class. Without this package, the copy that loads is whichever plugin WordPress happened to load first. A plugin built against a newer version can then call a method the older copy lacks, and the site fatals.

This package finds every copy this request will load, picks the newest of each library, and loads its classes from there. It works from the first moment any plugin uses a library, before most plugins have loaded. It costs about 0.5 ms on a site with 40 plugins that all bundle a library, and nothing on a page that loads no `Mai\` or `Mai_` class.

Why it works this way, the measurements, and every test: [`docs/specs/2026-10-03-mai-package-loader.md`](docs/specs/2026-10-03-mai-package-loader.md).

## Using a shared library in a plugin

Nothing about the loader shows up in the plugin's code.

1. Add each GitHub repository to the plugin's `composer.json`.

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/maithemewp/mai-cache"
    },
    {
      "type": "vcs",
      "url": "https://github.com/maithemewp/mai-package-loader"
    }
  ]
}
```

2. Require the library.

```sh
composer require maithemewp/mai-cache
```

3. Load Composer near the top of the main plugin file.

```php
require_once __DIR__ . '/vendor/autoload.php';
```

4. Use the library's classes as normal.
5. Commit the `vendor/` folder with the plugin.

Composer only reads repository lists from the plugin itself, so every GitHub-hosted package in the chain is listed in step 1.

## Making a library shared

1. Require this package in the library's `composer.json`.

```json
{
  "require": {
    "maithemewp/mai-package-loader": "^0.1"
  }
}
```

2. Remove the library's own `autoload` entry for its classes from `composer.json`.
3. Add a `mai-package.php` file to the library's root.

```php
<?php
return [
	'name'      => 'maithemewp/mai-cache',
	'version'   => '0.6.0',
	'namespace' => 'Mai\\Cache\\',
	'path'      => 'src',
];
```

4. Bump `version` in `mai-package.php` with every release.

A library of global classes lists them instead of a namespace:

```php
<?php
return [
	'name'    => 'maithemewp/mai-logger',
	'version' => '0.2.0',
	'classes' => [
		'Mai_Logger' => 'Mai_Logger.php',
	],
];
```

**The rules, and why:**

- **Every class name starts with `Mai\` or `Mai_`.** Anything else is turned away before any work is done.
- **The library is published under `maithemewp/`, and `name` is its Composer name.** Only those packages are looked at, and a declaration naming another package is skipped.
- **No Composer autoload of its own.** Composer's autoloader would otherwise serve whichever copy it reached first. So no helper functions or constants either: a `files` entry only runs from the first plugin's copy.
- **The version is plain numbers, like `0.6.0`.** A copy with any other version is skipped.
- **The API only grows.** Every plugin on a site gets the newest copy, so an older plugin must still work against it.
- **The first release on the loader is newer than every old copy** still on sites, so it wins wherever both exist.

## Limits

**A class already loaded cannot be swapped.** If something uses a shared class before a newer copy is visible, that page load keeps the older class, and the next one is right. That only happens before WordPress has said what will load: in a drop-in such as `object-cache.php`, in multisite's `sunrise.php`, or in a must-use plugin that uses it before a later must-use plugin loads.

**Only what this request loads counts.** Inactive plugins, plugins WP-CLI skips and plugins recovery mode pauses are never used, even if they hold a newer copy, and nothing in a request can change which folders are looked in.

**Using Strauss or PHP-Scoper in a plugin?** Exclude `maithemewp/*` from prefixing, or it renames this loader and starts a second one.

## When a class is not found

Every copy the loader skipped is listed with the reason:

```php
print_r( Mai_Package_Loader::rejected() );
```

With `WP_DEBUG` on, each is also logged to the debug log.

## Releasing

1. Push the loader's `develop` and `main`.
2. Tag the loader `v0.1.0`.
3. Tag each library on the loader.

Never ship a plugin with an untagged development copy of the loader. Composer records it as `dev-develop`, which never counts as newer, so a later fixed loader could not take over from it.

## Testing

```sh
./tests/run.sh
```

Level 1 uses real Composer installs with no WordPress. Level 2 runs inside a throwaway WordPress on SQLite, single site and multisite, and downloads it the first time. Then `tests/mutations.sh` breaks the loader one behaviour at a time and checks a test notices each break. Needs Composer and, the first time, network access.

To run the first two levels on another PHP version, point `PHP_BIN` at it:

```sh
PHP_BIN=/path/to/php81 ./tests/level1.sh
```
