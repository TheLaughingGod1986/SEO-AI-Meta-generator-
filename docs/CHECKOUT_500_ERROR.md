# 🔴 Checkout 500 Error - Backend Stripe Issue

## Problem
Getting 500 error when trying to create checkout session. Error shows:
- `error_code: "failed_to_create_checkout_session"`
- `status_code: 500`
- `is_authenticated: true` (so auth is working)

## Root Cause
The backend is failing to create the Stripe checkout session. This could be:

1. **Stripe API Key Issue**
   - The key we updated might not have taken effect yet
   - The key might be invalid
   - Backend might need to be restarted

2. **Price IDs Not in Backend's Valid List**
   - Backend has a `validPrices` array that needs to include:
     - `price_1SQ6a5Jl9Rm418cMx77q8KB9` (SEO AI Meta Pro)
     - `price_1SQ6aTJl9Rm418cMQz47wCZ2` (SEO AI Meta Agency)

3. **Backend Code Issue**
   - The backend Stripe integration might have a bug
   - Error handling might be swallowing the real error

## Solution

### Step 1: Check Backend Logs on Render
Go to Render Dashboard → alttext-ai-backend → Logs
Look for Stripe-related errors when checkout is attempted

### Step 2: Verify Price IDs in Backend Code
The backend needs to have these price IDs in its `routes/billing.js` file:

```javascript
const validPrices = {
  'alttext-ai': [
    // ... existing AltText AI prices
  ],
  'seo-ai-meta': [
    "price_1SQ6a5Jl9Rm418cMx77q8KB9", // SEO AI Meta Pro
    "price_1SQ6aTJl9Rm418cMQz47wCZ2"  // SEO AI Meta Agency
  ]
};
```

### Step 3: Test Stripe Key Directly
You can test if the Stripe key works by running this in the backend:

```bash
# In backend directory
node -e "const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY); stripe.prices.retrieve('price_1SQ6a5Jl9Rm418cMx77q8KB9').then(console.log).catch(console.error);"
```

### Step 4: Check Backend Environment Variables
On Render Dashboard, verify:
- `STRIPE_SECRET_KEY` is set correctly
- No extra spaces or quotes
- It's the LIVE key (starts with `sk_live_`)

## Quick Fix

If the price IDs aren't in the backend's valid list, you need to:
1. Go to the backend repository
2. Update `routes/billing.js` to add the SEO AI Meta price IDs
3. Commit and push (Render will auto-deploy)

## Test After Fix

Once fixed, try the checkout again. The error should change from 500 to either:
- Success (redirects to Stripe)
- 400 (invalid price ID - if IDs still not in list)
- 401 (Stripe key issue - if key is wrong)


