# Checkout Error Fix Summary

## Problem
Upgrade button was failing with "Backend service error during checkout" and 500 errors.

## Root Cause
**Stripe API Mode Mismatch**

The backend was using a **LIVE mode** Stripe secret key, but both the WordPress plugin and backend had **TEST mode** price IDs hardcoded.

```
Error: No such price: 'price_1SQ6aTJl9Rm418cMQz47wCZ2';
a similar object exists in test mode, but a live mode key was used to make this request.
```

## Solution Applied

### 1. Updated WordPress Plugin Files

Changed price IDs from test to live mode in:

**File: `includes/class-seo-ai-meta-core.php`**
- Lines 16-17: DEFAULT_CHECKOUT_PRICE_IDS
- Lines 25-28: LEGACY_PRICE_ID_MAP

**File: `templates/upgrade-modal.php`**
- Lines 34-37: Fallback price IDs

**File: `includes/class-seo-ai-meta.php`**
- Lines 166-167: Default price IDs (table creation)
- Lines 193-194: Default price IDs (table exists)

**File: `includes/class-seo-ai-meta-activator.php`**
- Lines 44-45: Activation default price IDs

### 2. Updated Backend Code

**File: `alttext-ai-backend-clone/routes/billing.js`**

Changed in two locations:

1. **Lines 39-40**: Checkout validation
   ```javascript
   'seo-ai-meta': [
     "price_1SQ72OJl9Rm418cMruYB5Pgb", // Pro (LIVE)
     "price_1SQ72KJl9Rm418cMB0CYh8xe"  // Agency (LIVE)
   ]
   ```

2. **Lines 440 & 455**: /billing/plans endpoint
   ```javascript
   priceId: "price_1SQ72OJl9Rm418cMruYB5Pgb", // Pro (LIVE)
   priceId: "price_1SQ72KJl9Rm418cMB0CYh8xe", // Agency (LIVE)
   ```

### 3. Price ID Mapping

| Product | Old (TEST) | New (LIVE) | Price |
|---------|-----------|------------|-------|
| SEO AI Meta Pro | `price_1SQ6a5Jl9Rm418cMx77q8KB9` | `price_1SQ72OJl9Rm418cMruYB5Pgb` | £12.99/month |
| SEO AI Meta Agency | `price_1SQ6aTJl9Rm418cMQz47wCZ2` | `price_1SQ72KJl9Rm418cMB0CYh8xe` | £49.99/month |

## Verification Steps

### 1. Verify Price IDs in Stripe
```bash
stripe prices retrieve price_1SQ72OJl9Rm418cMruYB5Pgb --live
stripe prices retrieve price_1SQ72KJl9Rm418cMB0CYh8xe --live
```

Both should show `"livemode": true` ✓

### 2. Test Backend /plans Endpoint
```bash
curl -s 'https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta'
```

Should return live price IDs.

### 3. WordPress Plugin
1. Deactivate and reactivate the SEO AI Meta Generator plugin to clear cached price IDs
2. OR add temporary code to update database (see update-price-ids.php)

## Backend Deployment

**Repository:** https://github.com/TheLaughingGod1986/alttext-ai-backend
**Commit:** e02d007 - "Update SEO AI Meta price IDs to live mode"
**Render Service:** https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g

Render auto-deploys on push to main branch (takes ~2-3 minutes).

## Testing

After backend deploys:

1. **Open WordPress admin** → SEO AI Meta Generator dashboard
2. **Click "Go Pro for More AI Power"** button
3. **Should open Stripe Checkout** with live mode products
4. **Test with Stripe test card:** 4242 4242 4242 4242
   - Note: Even with live price IDs, you can use test mode for testing if backend has test Stripe keys

## Environment Variable Notes

The backend does **NOT** use environment variables for individual price IDs (STRIPE_PRICE_PRO, STRIPE_PRICE_AGENCY). These can be removed from Render if they exist.

The backend only uses:
- `STRIPE_SECRET_KEY` - Main Stripe API key (live or test)
- `STRIPE_PRICE_CREDITS` - For the credits product (AI Alt Text only)

Price IDs are:
1. Hardcoded in backend `routes/billing.js` for validation and /plans endpoint
2. Sent from WordPress plugin in checkout request

## Files Changed

### WordPress Plugin
- includes/class-seo-ai-meta-core.php
- includes/class-seo-ai-meta.php
- includes/class-seo-ai-meta-activator.php
- templates/upgrade-modal.php
- RENDER_ENV_VARS.md (documentation)
- CHECKOUT_FIX_SUMMARY.md (this file)

### Backend
- routes/billing.js

## Git Commits

### WordPress Plugin
Not yet committed (working directory changes)

### Backend
```
commit e02d007
Author: Your Name
Date: Today

Update SEO AI Meta price IDs to live mode

- Changed SEO AI Meta Pro from test price_1SQ6a5... to live price_1SQ72O...
- Changed SEO AI Meta Agency from test price_1SQ6aT... to live price_1SQ72K...
- Updated both checkout validation and /plans endpoint
- This fixes checkout errors caused by live Stripe key with test price IDs
```

## Next Steps

1. ✅ Backend changes deployed to Render
2. ⏳ Wait for deployment to complete (~2-3 min)
3. ⏳ Test upgrade button
4. ⏳ If successful, commit WordPress plugin changes
5. ⏳ Deploy plugin to production WordPress site

## Troubleshooting

If checkout still fails:

1. **Check Render logs:**
   ```bash
   render logs -r srv-d3r1hjggjchc73bnp39g --limit 50
   ```

2. **Check backend deployment status:**
   - Go to Render dashboard
   - Verify deployment completed successfully
   - Check deploy logs for errors

3. **Clear WordPress cache:**
   - Deactivate/reactivate plugin
   - Clear any object cache (Redis, Memcached)
   - Clear any page cache

4. **Verify Stripe configuration:**
   - Backend has correct STRIPE_SECRET_KEY (live mode)
   - Price IDs exist in Stripe and are active
   - Products are configured correctly

## Success Criteria

✅ Backend deployed with live price IDs
✅ WordPress plugin updated with live price IDs
✅ Upgrade button opens Stripe Checkout
✅ Can complete test checkout
✅ No 500 errors in logs
