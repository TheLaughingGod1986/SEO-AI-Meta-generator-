# 🔧 Stripe Setup Guide

## Problem
The Stripe checkout links aren't working because **SEO AI Meta Stripe products haven't been created yet**.

## Solution: Create Stripe Products

### Step 1: Create Products in Stripe Dashboard

1. **Go to Stripe Dashboard:**
   - https://dashboard.stripe.com/products
   - Click **"+ Add product"**

2. **Create Pro Plan:**
   - **Name:** `SEO AI Meta - Pro`
   - **Description:** `100 AI-generated meta tags per month`
   - **Pricing Model:** Recurring
   - **Price:** `£12.99`
   - **Billing Period:** Monthly
   - Click **"Save product"**
   - **Copy the Price ID** (starts with `price_`)

3. **Create Agency Plan:**
   - **Name:** `SEO AI Meta - Agency`
   - **Description:** `1000 AI-generated meta tags per month`
   - **Pricing Model:** Recurring
   - **Price:** `£49.99`
   - **Billing Period:** Monthly
   - Click **"Save product"**
   - **Copy the Price ID** (starts with `price_`)

---

### Step 2: Update Price IDs in WordPress

Once you have the Price IDs, update them in WordPress:

**Option A: Via WordPress Settings (Recommended)**
1. Go to **SEO AI Meta → Settings**
2. Enter the Price IDs in the settings fields
3. Save

**Option B: Via Code**
Edit `includes/class-seo-ai-meta-core.php` line 15-17:

```php
private const DEFAULT_CHECKOUT_PRICE_IDS = array(
    'pro'     => 'price_XXXXXXXXXXXXXX', // Your Pro Price ID
    'agency'  => 'price_YYYYYYYYYYYYYY', // Your Agency Price ID
);
```

**Option C: Via WordPress Options**
Add to `wp_options` table:
```sql
INSERT INTO wp_options (option_name, option_value) 
VALUES ('seo_ai_meta_price_ids', 'a:2:{s:3:"pro";s:19:"price_XXXXXXXXXXXXXX";s:6:"agency";s:19:"price_YYYYYYYYYYYYYY";}')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);
```

---

### Step 3: Test Checkout

1. Go to **SEO AI Meta → Dashboard**
2. Click **"Upgrade to Pro"** or **"Unlock Unlimited AI Power"**
3. Click a plan button
4. Should redirect to Stripe checkout ✅

---

## Current Status

✅ **Code updated** - Links now show "Coming Soon" until Price IDs are configured
✅ **Error handling** - Better error messages if checkout fails
✅ **Backend ready** - Backend supports SEO AI Meta checkout

⏳ **Waiting for:** Stripe products to be created and Price IDs to be added

---

## Quick Fix (Temporary)

If you want to test with AltText AI products temporarily (for testing only):

1. Edit `includes/class-seo-ai-meta-core.php`:
```php
private const DEFAULT_CHECKOUT_PRICE_IDS = array(
    'pro'     => 'price_1SMrxaJl9Rm418cMM4iikjlJ', // AltText AI Pro (TEST ONLY)
    'agency'  => 'price_1SMrxaJl9Rm418cMnJTShXSY', // AltText AI Agency (TEST ONLY)
);
```

**⚠️ Warning:** This will create subscriptions linked to AltText AI products, not SEO AI Meta. Only use for testing!

---

## After Setup

Once Price IDs are configured:
- ✅ Checkout links will work
- ✅ Users can subscribe to Pro/Agency plans
- ✅ Backend will track usage correctly
- ✅ Subscriptions will be linked to SEO AI Meta service



