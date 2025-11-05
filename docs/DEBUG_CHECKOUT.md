# 🔍 Debugging Checkout Issue

## Problem
Clicking upgrade buttons just reloads the same page instead of redirecting to Stripe.

## Possible Causes

### 1. Backend API Not Responding
- Backend might return 502 or error
- Check: `curl "https://alttext-ai-backend.onrender.com/health"`

### 2. User Not Authenticated
- Backend requires authentication for checkout
- Check: Is user logged in via plugin?

### 3. Price ID Validation Failing
- Backend might reject the price IDs
- Check: Are price IDs in backend's valid list?

### 4. Handler Not Triggering
- The `admin_init` hook might not catch the page
- Check WordPress debug log for "SEO AI Meta Checkout" messages

## How to Debug

### Enable WordPress Debug Logging

Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Check Error Messages

1. Look for error message at top of dashboard
2. Check browser console for JavaScript errors
3. Check WordPress debug log: `wp-content/debug.log`

### Test Backend Directly

```bash
# Test health
curl "https://alttext-ai-backend.onrender.com/health"

# Test checkout (requires auth token)
curl -X POST "https://alttext-ai-backend.onrender.com/billing/checkout" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "priceId": "price_1SQ6a5Jl9Rm418cMx77q8KB9",
    "service": "seo-ai-meta",
    "successUrl": "https://yoursite.com/success",
    "cancelUrl": "https://yoursite.com/cancel"
  }'
```

## Quick Fixes Applied

1. ✅ Changed redirect to use `wp_safe_redirect()` instead of `window.open()`
2. ✅ Added error logging for debugging
3. ✅ Better error message display
4. ✅ Added debug logging when WP_DEBUG is enabled

## Next Steps

1. **Enable WP_DEBUG** and check logs
2. **Check if user is authenticated** to backend
3. **Test backend API** directly
4. **Check browser console** for errors


