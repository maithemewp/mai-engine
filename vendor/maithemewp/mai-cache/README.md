# Mai Cache

`remember()`-pattern wrapper around WordPress transients. Auto-bypasses caching during `SCRIPT_DEBUG` so you never debug stale data.

Safe to bundle in several plugins on one WordPress site. Each plugin can ship its own copy, and [maithemewp/mai-package-loader](https://github.com/maithemewp/mai-package-loader) loads the newest one, whichever plugin loads first.

---

## Requirements

- **PHP 8.1+**
- **[maithemewp/mai-package-loader](https://github.com/maithemewp/mai-package-loader)**, which Composer installs with it and which loads its classes.

---

## Installation

Add both GitHub repositories to the plugin or theme's `composer.json`, and require the library. Composer only reads repository lists from the plugin itself, so the loader's repository is listed too.

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/maithemewp/mai-cache" },
        { "type": "vcs", "url": "https://github.com/maithemewp/mai-package-loader" }
    ],
    "require": {
        "maithemewp/mai-cache": "^0.6"
    }
}
```

Then `composer install`, and require `vendor/autoload.php`. The newest copy on the site loads from the first use, even while plugins are still loading.

### Local development

List the loader's working copy too. Composer only reads repositories from the plugin itself, and only honours `@dev` on the plugin's own requirements, so both are listed:

```json
{
    "repositories": [
        { "type": "path", "url": "~/LocalPackages/mai-cache" },
        { "type": "path", "url": "~/LocalPackages/mai-package-loader" }
    ],
    "require": {
        "maithemewp/mai-cache": "@dev",
        "maithemewp/mai-package-loader": "@dev"
    }
}
```

---

## Quick start

```php
use Mai\Cache\Cache;

$value = Cache::for( 'acme' )->remember(
    'popular_posts',
    fn() => wp_get_recent_posts( [ 'numberposts' => 10 ] ),
    HOUR_IN_SECONDS
);
```

Or with an instance:

```php
$cache = new Cache( 'acme' );
$value = $cache->remember( 'popular_posts', fn() => …, HOUR_IN_SECONDS );
```

---

## API

| Method | Returns | Notes |
|--------|---------|-------|
| `new Cache(string $prefix = 'mai')` | `Cache` | All keys are stored as `{prefix}_{key}`. |
| `static for(string $prefix = 'mai')` | `Cache` | Memoized factory: same prefix returns the same instance. Transient-backed (Redis when present, DB fallback otherwise). |
| `static object(string $prefix = 'mai')` | `Cache` | Object-cache-only factory: uses `wp_cache_*` with no DB fallback. No-op when there is no persistent object cache. |
| `prefix()` | `string` | The instance's prefix. |
| `remember(string $key, callable $callback, int $expire)` | `mixed` | Get cached value; on miss, run callback and cache the result. WP_Error results are NOT cached. |
| `pull(string $key, mixed $default = null)` | `mixed` | Read-once: get value and delete it in one call. Returns `$default` if missing. |
| `get(string $key)` | `mixed` | Direct read. Returns `false` on miss or when caching is disabled. A stored `false` also reads as `false`, so use `has()` when `false` is a value you cache. |
| `has(string $key)` | `bool` | Whether a value is stored, whatever it is, including `false`. The only way to tell a stored `false` from a miss. |
| `set(string $key, mixed $value, int $expire)` | `bool` | Direct write. Returns `false` when caching is disabled. |
| `delete(string $key)` | `bool` | Direct delete. |
| `key(string $key)` | `string` | Builds the fully-prefixed transient key: prefix, storage schema, version token, optional group and its token, then your key. |
| `group(string $area)` | `Cache` | Return a scoped instance for the given sub-group (shares the same backing store). |
| `flush()` | `bool` | Invalidate all entries under the current prefix or group by rotating the version token. Then deletes the old rows when the store can (see [Grouping and flushing](#grouping-and-flushing)). |
| `version(array $scopes)` | `string` | Current version for one or more scopes, such as post types. Mints a token for a scope that has none. |
| `bump(string $scope)` | `bool` | Rotate one scope's version. Entries keep their keys and read as stale. Runs even when caching is disabled. |
| `write_swr(string $key, mixed $value, string $version, int $ttl, ?int $hard_ttl = null)` | `bool` | Store a value stamped with a version. `$ttl` is the soft lifetime and `$hard_ttl` is when the entry is removed. See [Stale-while-revalidate](#stale-while-revalidate). |
| `read_swr(string $key, string $version)` | `?array` | `null` when cold. Otherwise `value`, `fresh`, `stale` and `written`. See [Stale-while-revalidate](#stale-while-revalidate). |
| `lock(string $key, int $ttl = 30)` | `bool` | True for the one caller that should rebuild a stale or cold key. Atomic only with a persistent object cache. |
| `unlock(string $key)` | `bool` | Release a lock taken with `lock()`, so the next caller can take it. Release only a lock you still hold. An expired lock may belong to another request. |
| `can_cache()` | `bool` | False when SCRIPT_DEBUG is on or `{prefix}_can_cache` filter returns false. |
| `static has_persistent_object_cache()` | `bool` | True when WordPress is using an external object cache (e.g. Redis). |
| `static now()` | `int` | The current Unix time. Code in this package reads the time here so a test can control it. |
| `static set_clock(?\Closure $clock)` | `void` | Replace the clock `now()` reads. Pass `null` to go back to `time()`. `reset_runtime()` also restores it. |

---

## Storage modes

```php
use Mai\Cache\Cache;

// Transient-backed: Redis when present, database fallback otherwise.
Cache::for( 'mai' )->remember( 'key', fn() => expensive(), HOUR_IN_SECONDS );

// Object-cache-only: wp_cache_* with no DB fallback. No-op without a
// persistent object cache, so it never writes to wp_options.
Cache::object( 'mai' )->remember( 'key', fn() => expensive(), HOUR_IN_SECONDS );

if ( Cache::has_persistent_object_cache() ) {
    // Only worth caching this when Redis is present.
}
```

---

## Grouping and flushing

Use one prefix per plugin and a group per cache area. Bind the prefix once:

```php
function mai_cache( string $group = '' ): \Mai\Cache\Cache {
    $cache = \Mai\Cache\Cache::for( 'mai' );
    return $group ? $cache->group( $group ) : $cache;
}

mai_cache( 'menus' )->remember( $location, fn() => render_menu( $location ), DAY_IN_SECONDS );

mai_cache( 'menus' )->flush();   // bust every menu cache
mai_cache( 'menus' )->delete( $location ); // bust one entry
mai_cache()->flush();            // bust everything under this prefix
```

`flush()` rotates the token, so the old entries can no longer be read. It then deletes their rows. With the default transient store and no persistent object cache, that removes the old rows from `wp_options`, 1000 rows at a time. With a persistent object cache it does nothing, because the object cache expires its own keys.

`flush()` skips cleanup if a stored token is not the 12 lowercase hex characters mai-cache makes. The old rows then stay until their own expiry, and rows written without an expiry, such as version rows, stay for good.

A custom store can opt in by implementing `Mai\Cache\PrefixDelete`, which has one method: `delete_prefix( string $prefix ): int`. A store without it is skipped. Its old entries stay until their own expiry, and entries written without an expiry, such as version rows, stay for good.

---

## Stale-while-revalidate

For content that is costly to build, serve the old copy while one request builds the new one. Each entry is stamped with a version, and `read_swr()` tells you whether the copy you got is current.

```php
use Mai\Cache\Cache;

$cache   = Cache::for( 'acme' );
$version = $cache->version( [ 'post' ] );
$hit     = $cache->read_swr( 'archive_html', $version );

if ( null === $hit || ! $hit['fresh'] ) {
    // With a persistent object cache, lock() lets only one request rebuild
    // and the rest keep serving the old copy. Without one, each request
    // takes its own lock, so several may rebuild at once.
    if ( $cache->lock( 'archive_html' ) ) {
        $html = build_archive();

        // Soft lifetime 1 hour, hard lifetime 1 day.
        $cache->write_swr( 'archive_html', $html, $version, HOUR_IN_SECONDS, DAY_IN_SECONDS );
    }
}

$html = $html ?? ( $hit['value'] ?? '' );
```

Call `$cache->bump( 'post' )` when a post is saved. Every entry stamped with the old version then reads as stale, but is still served until it is rebuilt.

`write_swr()` takes two lifetimes:

- **`$ttl` is the soft lifetime.** After it the entry is still served, but `read_swr()` reports it as stale.
- **`$hard_ttl` is the hard lifetime.** It is the store's own expiry, and after it the entry is gone. Leave it out and it equals `$ttl`. A value below `$ttl` is raised to `$ttl`.
- **A `$ttl` of 0 or less sets no soft deadline**, so the entry never goes stale by age.

`read_swr()` returns `null` when there is nothing stored. Otherwise it returns an array:

- **`value`** is the stored value.
- **`fresh`** is `true` only when `stale` is `null`.
- **`stale`** says why the entry is not fresh. It is `'version'` when the stored version no longer matches the one you passed, `'age'` when the version matches but the soft lifetime has passed, and `null` when neither applies. If both apply, `'version'` wins.
- **`written`** is the Unix time the entry was written, or `null` when unknown. An entry written by 0.4.0 has no write time and no soft deadline, so `written` is `null` and it is never stale by age.

To test age-based staleness without waiting, replace the clock:

```php
$now = 1_000_000;
Cache::set_clock( function () use ( &$now ) { return $now; } );

$cache->write_swr( 'key', 'value', $version, 60, 3600 );

$now += 61;
$cache->read_swr( 'key', $version )['stale']; // 'age'

Cache::reset_runtime(); // puts the real clock back
```

---

## Read-once state (flash messages, one-time tokens)

Beyond performance caching, `pull()` reads a value and deletes it in one call, which fits consume-once state. Use the transient mode (not object-only) and a dedicated prefix so content flushes never touch it. It is best-effort: never store state that must survive cache eviction (use options or user meta for that).

```php
// Store a one-time admin notice, then redirect.
mai_cache( 'flash' )->set( 'saved_' . get_current_user_id(), 'Settings saved.', 5 * MINUTE_IN_SECONDS );

// On the next page load, show it exactly once: pull() returns it and deletes it.
$notice = mai_cache( 'flash' )->pull( 'saved_' . get_current_user_id() );
if ( $notice ) {
    printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $notice ) );
}
```

---

## Examples

### Memoize an expensive query

```php
$posts = Cache::for( 'acme' )->remember(
    'popular_posts',
    fn() => $wpdb->get_results( "SELECT … FROM {$wpdb->posts} …" ),
    HOUR_IN_SECONDS
);
```

### Cache an external API response

```php
$weather = Cache::for( 'acme' )->remember(
    'weather_orlando',
    function () {
        $r = wp_remote_get( 'https://api.example.com/weather/orlando' );
        if ( is_wp_error( $r ) ) return $r; // not cached; try again next request
        return json_decode( wp_remote_retrieve_body( $r ), true );
    },
    15 * MINUTE_IN_SECONDS
);
```

WP_Error responses are deliberately not cached, so a transient failure doesn't get pinned.

### Read-once / single-use values

```php
// Use pull() for things like a one-time notice payload or a flash message.
// pull() returns the value and deletes it in one call, so it shows exactly once.
$message = Cache::for( 'acme' )->pull( 'flash_admin_message', '' );
if ( $message ) {
    echo '<div class="notice notice-info">' . esc_html( $message ) . '</div>';
}
```

### Manual cache busting

```php
// Force-refresh on save_post for a specific category.
add_action( 'save_post', function ( $post_id ) {
    if ( has_category( 'news', $post_id ) ) {
        Cache::for( 'acme' )->delete( 'latest_news_widget' );
    }
} );
```

### Multiple prefixes for isolated caches

```php
$theme_cache = Cache::for( 'acme_theme' );
$cli_cache   = Cache::for( 'acme_cli' );

// Different namespaces, no key collisions across concerns.
$theme_cache->set( 'rebuild_timestamp', time(), DAY_IN_SECONDS );
$cli_cache->set( 'migration_progress', $progress, HOUR_IN_SECONDS );
```

### Disable caching at runtime

```php
// Per prefix:
add_filter( 'acme_can_cache', '__return_false' );

// Or globally during dev: define SCRIPT_DEBUG in wp-config.php.
define( 'SCRIPT_DEBUG', true );
```

`SCRIPT_DEBUG` is checked automatically: when true, every `get()` returns `false` and `set()` is a no-op. No more "why is this still showing the old value" debugging sessions.

### Caching a `false`

Values are stored in an envelope, so a stored `false` is a real hit: `remember()` will not re-run its callback for it. `get()` still returns `false` for both a miss and a stored `false`, because that is its long-standing contract. Use `has()` or `remember()` when the distinction matters.

```php
$cache->set( 'has_tag', false, 300 );

$cache->get( 'has_tag' );  // false, same as a miss
$cache->has( 'has_tag' );  // true, it is there
```

### Direct get / set when you need it

```php
$cache = new Cache( 'acme' );

if ( false === ( $value = $cache->get( 'key' ) ) ) {
    $value = expensive_computation();
    $cache->set( 'key', $value, HOUR_IN_SECONDS );
}
```

Equivalent to `remember()` but spelled out; useful when the cache write should be conditional on more than just `is_wp_error`.

---

## Real-world WordPress recipes

### WP-CLI batch processing: memoize a queue

```php
use Mai\Cache\Cache;
use WP_CLI;

WP_CLI::add_command( 'acme fix-legacy-embeds', function () {
    $cache = Cache::for( 'acme_cli' );

    $remaining = $cache->remember( 'fix_embeds_queue', function () {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_status='publish' AND post_content LIKE '%facebook.com%'"
        );
    }, HOUR_IN_SECONDS );

    $batch = array_splice( $remaining, 0, 50 );

    foreach ( $batch as $id ) {
        // … do the fix …
        WP_CLI::log( "Fixed #{$id}" );
    }

    // Save what's left for the next run.
    if ( $remaining ) {
        $cache->set( 'fix_embeds_queue', $remaining, HOUR_IN_SECONDS );
    } else {
        $cache->delete( 'fix_embeds_queue' );
    }

    WP_CLI::success( count( $batch ) . ' processed; ' . count( $remaining ) . ' remaining.' );
} );
```

Idempotent and re-runnable. Survives across `wp acme fix-legacy-embeds` invocations.

### Hot widget on a high-traffic page

```php
add_action( 'wp_loaded', function () {
    add_shortcode( 'acme_top_commenters', function () {
        return Cache::for( 'acme' )->remember(
            'top_commenters_widget',
            function () {
                global $wpdb;
                $rows = $wpdb->get_results( "SELECT comment_author, COUNT(*) as n FROM {$wpdb->comments} WHERE comment_approved=1 GROUP BY comment_author ORDER BY n DESC LIMIT 10" );
                ob_start();
                foreach ( $rows as $row ) {
                    printf( '<li>%s (%d)</li>', esc_html( $row->comment_author ), $row->n );
                }
                return '<ul class="top-commenters">' . ob_get_clean() . '</ul>';
            },
            10 * MINUTE_IN_SECONDS
        );
    } );
} );
```

### Render-block expensive transform

```php
add_filter( 'render_block_core/post-content', function ( $html, $block ) {
    $key = 'rendered_post_' . get_the_ID();

    return Cache::for( 'acme' )->remember( $key, function () use ( $html ) {
        // ... expensive DOM rewriting via Mai\DOM\Document ...
        return $html;
    }, DAY_IN_SECONDS );
}, 10, 2 );
```

Pair this with `delete()` calls on `save_post` so the cache invalidates correctly.

---

## Several plugins bundling it

Every copy ships a `mai-package.php` declaring its version, and mai-package-loader loads the newest copy of the library on the site, whichever plugin loads first. Up to 0.5.0, copies used their own bootstrap, which in practice always loaded the first plugin's copy, because Composer runs a package's `files` entry only once per request.

Those older copies still work alongside this one. The loader answers before their bootstrap does, so this copy wins wherever both are installed, unless an older plugin uses a `Mai\Cache` class while its own file is loading.

---

## License

GPL-2.0-or-later
