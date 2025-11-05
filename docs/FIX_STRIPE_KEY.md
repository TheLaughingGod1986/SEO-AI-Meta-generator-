# 🔧 Fix Stripe API Key Expired Error

## Problem
The upgrade button is failing with error: `api_key_expired` (401 Unauthorized)

This is because the Stripe secret key on your backend (`alttext-ai-backend.onrender.com`) has expired.

## Solution: Update Stripe API Key on Render

### Step 1: Get New Stripe Secret Key

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. Make sure you're in **Live mode** (not Test mode)
3. Click **"Create secret key"** or find your existing secret key
4. Copy the **Secret key** (starts with `sk_live_...`)

⚠️ **Important**: Make sure you're using the **Live** key, not the test key!

### Step 2: Update on Render Dashboard

1. Go to [Render Dashboard](https://dashboard.render.com)
2. Find your service: **alttext-ai-backend** (or similar)
3. Click on the service
4. Go to **Environment** tab
5. Find the `STRIPE_SECRET_KEY` variable
6. Click **Edit** or **Update**
7. Paste your new Stripe secret key
8. Click **Save Changes**

Render will automatically restart the service with the new key.

### Step 3: Verify It's Working

Wait 1-2 minutes for Render to restart, then test:

```bash
# Test the backend health
curl "https://alttext-ai-backend.onrender.com/health"

# Test checkout endpoint (requires auth token)
# Or test directly in WordPress by clicking the upgrade button
```

### Alternative: Using Render CLI

If you prefer using the CLI:

```bash
# First, authenticate with Render (if not already)
render login

# List your services to find the exact service name
render services list

# Update the environment variable (replace SERVICE_NAME with your actual service name)
render services set-env SERVICE_NAME STRIPE_SECRET_KEY=sk_live_YOUR_NEW_KEY_HERE
```

## Quick Check: Which Key to Use

- ✅ **Live mode key**: Starts with `sk_live_...` (use this for production)
- ❌ **Test mode key**: Starts with `sk_test_...` (only for testing)

Your logs show `'x-stripe-routing-context-priority-tier': 'livemode-critical'`, so you need the **live** key.

## After Updating

Once the key is updated:
1. Wait 1-2 minutes for Render to restart
2. Try clicking the upgrade button again in WordPress
3. It should redirect to Stripe checkout successfully ✅

## Troubleshooting

If it still doesn't work:
1. Check Render logs to confirm the new key is loaded
2. Verify the key doesn't have extra spaces or quotes
3. Make sure you copied the full key (they're long!)
4. Check that you're using the Live key, not Test key

