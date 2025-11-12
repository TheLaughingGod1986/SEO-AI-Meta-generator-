# Button Debug Guide

## What Was Added

### Version Update
- Plugin version bumped from 1.1.0 → 1.1.1 to force cache bust

### Debug Logging Added
All JavaScript files now have comprehensive console logging to help identify issues.

## Testing Steps

### 1. Clear All Caches
```bash
# WordPress:
- Deactivate plugin
- Reactivate plugin
- Clear any caching plugins

# Browser:
- Hard refresh: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
- Or: Clear browser cache completely
```

### 2. Open Browser Console
1. Right-click on page → Inspect → Console tab
2. Clear console
3. Refresh page

### 3. Expected Console Messages

You should see these messages in this order:

```javascript
// 1. Helper functions load
SEO AI Meta: Helpers loaded

// 2. Helper functions registered
SEO AI Meta: Functions registered: {
  seoAiMetaTrackEvent: "function",
  seoAiMetaLogout: "function",
  seoAiMetaTogglePasswordVisibility: "function",
  seoAiMetaShowLoginTab: "function",
  seoAiMetaShowForgotPassword: "function"
}

// 3. Upgrade modal script loads
SEO AI Meta: Upgrade modal script loading...

// 4. Upgrade modal functions registered
SEO AI Meta: Modal functions registered: {
  seoAiMetaShowUpgradeModal: "function",
  seoAiMetaCloseModal: "function"
}

// 5. Auth modal mounting
SEO AI Meta: Auth modal mounting...
SEO AI Meta: Auth modal mounted successfully

// 6. Login modal functions registered
SEO AI Meta: Registering login modal functions
SEO AI Meta: Login modal functions registered: {
  seoAiMetaShowLoginModal: "function",
  seoAiMetaCloseLoginModal: "function"
}
```

### 4. Test Modal Buttons

Click each button and watch console:

#### Test 1: Login Button
**Button:** "Login" in header
**Expected console output:**
```
SEO AI Meta Event: login_click {source: "header"}
SEO AI Meta: seoAiMetaShowLoginModal called
```
**Expected result:** Login modal opens

#### Test 2: Upgrade Button
**Button:** Any "Upgrade" or "Get Started" button
**Expected console output:**
```
SEO AI Meta Event: upgrade_click {source: "...", location: "..."}
SEO AI Meta: seoAiMetaShowUpgradeModal called
```
**Expected result:** Upgrade modal opens

#### Test 3: Close Modal
**Button:** X button in modal
**Expected console output:**
```
SEO AI Meta: seoAiMetaCloseLoginModal called
```
**Expected result:** Modal closes

## Troubleshooting

### Problem: No console messages at all
**Solution:**
1. Check if scripts are loading in Network tab
2. Filter for "seo-ai-meta"
3. Verify all .js files return 200 status
4. Check for any red errors in console

### Problem: "...is not a function" error
**Diagnosis:**
- If error shows before "Functions registered" message → Script loading order issue
- If error shows after "Functions registered" message → Timing issue

**Solution:**
- Check which function is undefined
- Look for that function in the "Functions registered" log
- If it's missing, the function wasn't created properly

### Problem: Some modal buttons work, others don't
**Diagnosis:**
- Check which specific button isn't working
- Note the onclick attribute value
- Search for that function name in console logs

**Common causes:**
1. `seoAiMetaShowLoginModal` not defined → Auth modal didn't mount
2. `seoAiMetaShowUpgradeModal` not defined → Upgrade modal template not included
3. `seoAiMetaTrackEvent` not defined → Helpers.js didn't load

### Problem: Functions registered but button still doesn't work
**Solution:**
1. Test function directly in console:
   ```javascript
   typeof seoAiMetaShowUpgradeModal
   // Should return: "function"

   seoAiMetaShowUpgradeModal()
   // Should open modal
   ```

2. If direct call works but button doesn't:
   - Check button's onclick attribute in Elements tab
   - Verify it matches exactly: `onclick="seoAiMetaShowUpgradeModal();"`
   - Check for typos or extra characters

3. If direct call doesn't work:
   - Note the exact error message
   - Share console output for further debugging

## Share This Info

When reporting issues, please share:

1. **All console messages** (copy entire console output)
2. **Which specific button** isn't working
3. **Button's onclick attribute** (from Elements tab)
4. **Any red errors** in console
5. **Browser and version** (Chrome 120, Firefox 121, etc.)

## Quick Function Test

Paste this in console to test all functions:

```javascript
console.log('Function Test Results:', {
  seoAiMetaTrackEvent: typeof seoAiMetaTrackEvent,
  seoAiMetaLogout: typeof seoAiMetaLogout,
  seoAiMetaShowLoginModal: typeof seoAiMetaShowLoginModal,
  seoAiMetaCloseLoginModal: typeof seoAiMetaCloseLoginModal,
  seoAiMetaShowUpgradeModal: typeof seoAiMetaShowUpgradeModal,
  seoAiMetaCloseModal: typeof seoAiMetaCloseModal
});
```

**Expected output:** All should show "function"

If any show "undefined", that's the problem function.
