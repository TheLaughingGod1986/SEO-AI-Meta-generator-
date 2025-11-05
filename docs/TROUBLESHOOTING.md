# Troubleshooting Guide

## Common Errors and Solutions

### 1. "OpenAI API key is not configured"

**Error Message:** `OpenAI API key is not configured.`

**Solution:**
- Go to **Posts > SEO AI Meta > Settings**
- Enter your OpenAI API key
- OR ensure `OPENAI_API_KEY` is set in Render environment variables
- Save settings

### 2. AJAX Errors in Browser Console

**Symptoms:** Console shows AJAX errors when generating meta

**Common Causes:**
- Nonce expired (refresh the page)
- Permission denied (check user capabilities)
- Network timeout (check internet connection)

**Solutions:**
1. Refresh the page and try again
2. Check browser console for specific error message
3. Verify you're logged in as administrator
4. Check WordPress debug log: `wp-content/debug.log`

### 3. "Permission Denied" Error

**Error Message:** `Permission denied.`

**Solution:**
- Ensure you're logged in as an Administrator
- The plugin requires `manage_seo_ai_meta` capability
- This is automatically granted to administrators on activation

### 4. "Limit Reached" Error

**Error Message:** `You have reached your monthly limit.`

**Solution:**
- Free plan: 10 posts/month
- Upgrade to Pro (100/month) or Agency (1000/month)
- Wait for monthly reset (first day of next month)
- Check usage in Dashboard tab

### 5. Meta Tags Not Generating

**Symptoms:** Clicking "Generate" shows error or nothing happens

**Check:**
1. **API Key:** Is OpenAI API key configured?
2. **Network:** Is internet connection working?
3. **OpenAI API:** Is OpenAI API accessible? (check status page)
4. **Usage:** Have you reached your monthly limit?
5. **Browser Console:** Check for JavaScript errors

### 6. Bulk Generation Not Working

**Symptoms:** Bulk generate button doesn't work or processes fail

**Solutions:**
1. Check browser console for errors
2. Verify posts are selected (checkbox checked)
3. Check if individual generation works first
4. Ensure AJAX is working (test with single post)
5. Check WordPress AJAX endpoint: `/wp-admin/admin-ajax.php`

### 7. PHP Deprecation Warnings

**Symptoms:** Warnings about `strpos()` or `str_replace()` with null

**Note:** These are from WordPress core, not the plugin. They're harmless but can be suppressed:

```php
// In wp-config.php (development only)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
}
```

### 8. Headers Already Sent Warnings

**Symptoms:** "Cannot modify header information - headers already sent"

**Causes:**
- Output before headers (usually from WordPress core in PHP 8.3)
- Whitespace in PHP files
- Plugin output too early

**Solutions:**
- Check for whitespace before `<?php` tags
- Ensure no `echo` statements before headers
- These warnings are usually from WordPress core, not the plugin

### 9. Meta Tags Not Showing on Frontend

**Symptoms:** Generated meta tags don't appear in page source

**Check:**
1. **Meta exists:** Check postmeta: `SELECT * FROM wp_postmeta WHERE meta_key = '_seo_ai_meta_title'`
2. **Frontend output:** Verify `wp_head` hook is firing
3. **Theme:** Some themes don't output `wp_head` properly
4. **Cache:** Clear WordPress cache if using caching plugin

### 10. Settings Not Saving

**Symptoms:** Settings page doesn't save changes

**Solutions:**
1. Check file permissions on WordPress files
2. Verify database connection
3. Check for PHP errors in debug log
4. Ensure `options.php` is accessible
5. Try deactivating other plugins (conflict check)

## Debug Mode

Enable WordPress debug mode to see detailed errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Hide from frontend
```

Check logs at: `wp-content/debug.log`

## Checking AJAX Endpoint

Test if AJAX is working:
1. Open browser console
2. Go to Network tab
3. Click "Generate Meta"
4. Look for request to `admin-ajax.php`
5. Check response status and content

## Common Error Codes

- `no_api_key` - OpenAI API key missing
- `permission_denied` - User doesn't have capability
- `invalid_post` - Post not found
- `api_error` - OpenAI API request failed
- `parse_error` - Failed to parse API response
- `usage_limit_reached` - Monthly limit exceeded

## Getting Help

1. **Check Browser Console:** Most errors show here
2. **Check WordPress Debug Log:** `wp-content/debug.log`
3. **Check Network Tab:** See AJAX requests/responses
4. **Test Individual Generation:** Isolate bulk vs single issues
5. **Check Plugin Settings:** Verify API key and configuration

## Still Having Issues?

1. Deactivate and reactivate the plugin
2. Clear browser cache
3. Try different browser
4. Check with WordPress default theme
5. Deactivate other plugins (conflict check)
6. Check PHP error log
7. Verify WordPress and PHP versions meet requirements

