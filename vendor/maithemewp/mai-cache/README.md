# Mai Cache

`remember()`-pattern wrapper around WordPress transients. Auto-bypasses caching during `SCRIPT_DEBUG` so you never debug stale data.

Versioned and drop-in safe: multiple plugins on the same WordPress install can each bundle their own copy of `mai-cache`; the highest registered version wins at runtime via a shared bootstrap registry (same pattern as [maithemewp/mai-logger](https://github.com/maithemewp/mai-logger)).

---

## Requirements

- **PHP 8.1+**
- **WordPress** (uses `ABSPATH` as a load guard; bootstrap autoload runs from Composer's `vendor/autoload.php`).

---

## Installation

```json
{
    "require": {
        "maithemewp/mai-cache": "^0.1"
    }
}
```

`composer install`. The bootstrap runs automatically when `vendor/autoload.php` is required.

### Local development

```json
{
    "repositories": [
        { "type": "path", "url": "~/LocalPackages/mai-cache" }
    ],
    "require": {
        "maithemewp/mai-cache": "*"
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
| `get(string $key)` | `mixed` | Direct read. Returns `false` on miss or when caching is disabled. |
| `set(string $key, mixed $value, int $expire)` | `bool` | Direct write. Returns `false` when caching is disabled. |
| `delete(string $key)` | `bool` | Direct delete. |
| `key(string $key)` | `string` | Builds the fully-prefixed transient key. |
| `group(string $area)` | `Cache` | Return a scoped instance for the given sub-group (shares the same backing store). |
| `flush()` | `bool` | Invalidate all entries under the current prefix or group by rotating the version token. |
| `can_cache()` | `bool` | False when SCRIPT_DEBUG is on or `{prefix}_can_cache` filter returns false. |
| `static has_persistent_object_cache()` | `bool` | True when WordPress is using an external object cache (e.g. Redis). |

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

## Versioned coexistence (advanced)

When more than one plugin on the same WP install bundles `mai-cache`, all versions register themselves with `Mai_Cache_Bootstrap`. On first request for any `Mai\Cache\*` class, the autoloader picks the highest registered version and loads from that version's `src/`.

```
Plugin A (vendor/maithemewp/mai-cache @ 0.1.0)
Plugin B (vendor/maithemewp/mai-cache @ 0.2.0)
                  │
                  ▼
       Both register on autoload
                  │
                  ▼
       First Mai\Cache\Cache request
                  │
                  ▼
       Autoloader picks 0.2.0's src/
                  │
                  ▼
       Both plugins use 0.2.0
```

**Bootstrap protocol is frozen.** Never change `Mai_Cache_Bootstrap::register()`'s signature; old bundled copies in the wild will call the original signature on whichever bootstrap loaded first.

---

## License

GPL-2.0-or-later
