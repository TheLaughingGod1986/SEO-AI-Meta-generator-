# Suppressing WordPress Core Warnings

## The Issue

WordPress 6.8 with PHP 8.3 shows deprecation warnings from WordPress core files. These are **not from our plugin** but from WordPress itself.

## Quick Fix (Temporary)

### Option 1: Suppress in wp-config.php (Recommended for Development)

Add this to your `wp-config.php` file:

```php
// Suppress PHP 8.3 deprecation warnings (development only)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
}
```

### Option 2: Plugin Already Includes Suppression

The plugin now includes automatic suppression of deprecation warnings when `WP_DEBUG` is enabled and PHP 8.3+ is detected.

### Option 3: Docker Environment Variable

If using Docker, you can set error reporting in the container:

```bash
docker compose exec wordpress bash -c "echo 'error_reporting = E_ALL & ~E_DEPRECATED' >> /usr/local/etc/php/conf.d/docker-php.ini"
```

## Important Notes

1. **These warnings are harmless** - They don't affect functionality
2. **They're from WordPress core** - Not our plugin code
3. **WordPress will fix them** - Future WordPress updates will address these
4. **Production should be fine** - These warnings typically only show with `WP_DEBUG` enabled

## For Production

In production:
- `WP_DEBUG` should be `false`
- Warnings won't display to users
- Only logged if `WP_DEBUG_LOG` is enabled

## Verification

After applying the fix:
1. Refresh the WordPress admin page
2. Warnings should no longer display
3. Plugin functionality remains unchanged

