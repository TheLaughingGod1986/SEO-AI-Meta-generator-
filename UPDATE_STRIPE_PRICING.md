# 🔄 Update Stripe Pricing Guide

## Summary of Changes
- **Pro Plan:** £12.99/month (100 posts) → £14.99/month (500 posts)
- **Agency Plan:** £49.99/month (1,000 posts) → £59.99/month (5,000 posts)

## Important: Stripe Pricing Rules
⚠️ **You CANNOT edit existing Stripe prices.** You must create new prices and update the price IDs in your plugin.

---

## Step 1: Create New Prices in Stripe Dashboard

### Option A: Via Stripe Dashboard (Recommended)

1. **Go to Stripe Products:**
   - Visit: https://dashboard.stripe.com/products
   
2. **Update Pro Plan:**
   - Find product: **"SEO AI Meta Pro"** or **"SEO AI Meta - Pro"**
   - Click on the product
   - Click **"+ Add another price"**
   - **Price:** `£14.99` or `1499` (in pence)
   - **Billing Period:** Monthly (recurring)
   - **Description:** `500 AI-generated meta tags per month`
   - Click **"Add price"**
   - ✅ **Copy the new Price ID** (starts with `price_`)
   
3. **Update Agency Plan:**
   - Find product: **"SEO AI Meta Agency"** or **"SEO AI Meta - Agency"**
   - Click on the product
   - Click **"+ Add another price"**
   - **Price:** `£59.99` or `5999` (in pence)
   - **Billing Period:** Monthly (recurring)
   - **Description:** `5,000 AI-generated meta tags per month`
   - Click **"Add price"**
   - ✅ **Copy the new Price ID** (starts with `price_`)

### Option B: Via Stripe CLI (Advanced)

```bash
# Create new Pro price (£14.99/month)
stripe prices create \
  --product prod_XXXXX \
  --unit-amount 1499 \
  --currency gbp \
  --recurring[interval]=month \
  --nickname="SEO AI Meta Pro - £14.99/month (500 posts)"

# Create new Agency price (£59.99/month)
stripe prices create \
  --product prod_YYYYY \
  --unit-amount 5999 \
  --currency gbp \
  --recurring[interval]=month \
  --nickname="SEO AI Meta Agency - £59.99/month (5,000 posts)"
```

---

## Step 2: Update Price IDs in Plugin

Once you have the **new Price IDs**, update them in your plugin:

### Current Price IDs (to be replaced):
```
Pro:    price_1SQ72OJl9Rm418cMruYB5Pgb  (£12.99/month)
Agency: price_1SQ72KJl9Rm418cMB0CYh8xe  (£49.99/month)
```

### Method 1: Via WordPress Settings (Easiest)

1. Go to WordPress admin: **Posts → SEO AI Meta Generator → Settings**
2. Scroll to **"Checkout Price IDs"** section
3. Update:
   - **Pro Plan Price ID:** `price_[YOUR_NEW_PRO_PRICE_ID]`
   - **Agency Plan Price ID:** `price_[YOUR_NEW_AGENCY_PRICE_ID]`
4. Click **"Save Changes"**

### Method 2: Via Code (if needed)

Edit `includes/class-seo-ai-meta-core.php` around line 16-17:

```php
private const DEFAULT_CHECKOUT_PRICE_IDS = array(
    'pro'     => 'price_[NEW_PRO_PRICE_ID]',     // SEO AI Meta Pro - £14.99/month
    'agency'  => 'price_[NEW_AGENCY_PRICE_ID]',  // SEO AI Meta Agency - £59.99/month
);
```

---

## Step 3: Archive Old Prices (Optional)

To prevent confusion, archive the old prices in Stripe:

1. Go to each product in Stripe Dashboard
2. Find the old prices (£12.99 and £49.99)
3. Click the "..." menu next to each old price
4. Select **"Archive"**

⚠️ **Note:** Existing subscribers will continue on their old prices unless you migrate them.

---

## Step 4: Test the Update

1. **Clear WordPress cache** (if using caching plugin)
2. Go to **Posts → SEO AI Meta Generator**
3. Click **"Upgrade"** or view pricing modal
4. Verify new prices are displayed:
   - Pro: £14.99/month (500/month)
   - Agency: £59.99/month (5,000/month)
5. Test checkout flow (use Stripe test mode if available)

---

## Step 5: Migrate Existing Subscribers (Optional)

If you have existing subscribers, you can:

### Option A: Let them stay on old pricing
- Old subscribers keep £12.99/£49.99 pricing
- New subscribers get £14.99/£59.99 pricing
- **Grandfathered pricing** approach

### Option B: Update existing subscriptions
Via Stripe Dashboard:
1. Go to **Customers** → Select customer
2. Click on their subscription
3. Click **"Update subscription"**
4. Change price to new price ID
5. Choose effective date (immediately or next billing cycle)

### Option C: Bulk migration via API
```bash
# List all subscriptions with old price
stripe subscriptions list --price price_1SQ72OJl9Rm418cMruYB5Pgb

# Update each subscription (requires subscription ID)
stripe subscriptions update sub_XXXXX \
  --items[0][price]=price_[NEW_PRO_PRICE_ID] \
  --proration_behavior=none
```

---

## Summary of Plugin Changes Already Made

✅ **Upgrade Modal** - Updated to show £14.99 and £59.99
✅ **Admin Display** - Updated pricing text
✅ **Core Class** - Updated limits to 500 and 5,000
✅ **Readme** - Updated all pricing documentation
✅ **Comments** - Updated price comments in code

⏳ **Remaining:** Update Price IDs in Stripe and plugin settings

---

## Need Help?

- **Stripe Documentation:** https://stripe.com/docs/billing/prices-guide
- **View your products:** https://dashboard.stripe.com/products
- **Test mode toggle:** Top left of Stripe Dashboard

---

## Quick Reference

| Plan   | Old Price | New Price | Old Limit | New Limit | Action Required |
|--------|-----------|-----------|-----------|-----------|-----------------|
| Pro    | £12.99    | £14.99    | 100/month | 500/month | Create new price in Stripe |
| Agency | £49.99    | £59.99    | 1,000/month | 5,000/month | Create new price in Stripe |

---

**Next Steps:**
1. ✅ Plugin code updated
2. ⏳ Create new prices in Stripe
3. ⏳ Update price IDs in plugin settings
4. ⏳ Test checkout flow
5. ⏳ (Optional) Migrate existing customers

