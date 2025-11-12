# 🔧 Fix Checkout 500 Error

## Problem
Getting 500 error when clicking upgrade button. Error shows:
- `error_code: "failed_to_create_checkout_session"`
- `status_code: 500`
- Backend is failing to create Stripe checkout session

## Root Cause
The backend's `routes/billing.js` file has a `validPrices` array that validates price IDs before creating checkout sessions. The SEO AI Meta price IDs are not in this list, so the backend rejects them.

## Solution: Update Backend Code

### Step 1: Find Backend Repository
The backend is at: `/Users/benjaminoats/Library/CloudStorage/SynologyDrive-File-sync/Coding/wp-alt-text-ai/alttext-ai-backend-clone`

### Step 2: Update `routes/billing.js`

Find the `validPrices` object and add the SEO AI Meta price IDs:

```javascript
const validPrices = {
  'alttext-ai': [
    // ... existing AltText AI prices
    "price_1SMrxaJl9Rm418cMM4iikjlJ", // AltText AI Pro
    "price_1SMrxaJl9Rm418cMnJTShXSY", // AltText AI Agency
    // ... other AltText AI prices
  ],
  'seo-ai-meta': [
    "price_1SQ6a5Jl9Rm418cMx77q8KB9", // SEO AI Meta Pro - £12.99/month
    "price_1SQ6aTJl9Rm418cMQz47wCZ2", // SEO AI Meta Agency - £49.99/month
  ]
};
```

### Step 3: Verify Checkout Route Logic

Make sure the checkout route checks the `service` parameter:

```javascript
router.post('/checkout', authenticateToken, async (req, res) => {
  try {
    const { priceId, price_id, successUrl, cancelUrl, service = 'alttext-ai' } = req.body;
    
    // Use price_id if provided, otherwise priceId
    const actualPriceId = price_id || priceId;
    
    if (!actualPriceId) {
      return res.status(400).json({
        error: 'Price ID is required',
        code: 'MISSING_PRICE_ID'
      });
    }

    // Get valid prices for this service
    const servicePrices = validPrices[service] || validPrices['alttext-ai'];

    if (!servicePrices.includes(actualPriceId)) {
      return res.status(400).json({
        error: `Invalid price ID for ${service} service`,
        code: 'INVALID_PRICE_ID',
        provided: actualPriceId,
        valid: servicePrices
      });
    }

    // Create checkout session
    const session = await createCheckoutSession(
      req.user.id,
      actualPriceId,
      successUrl || `${process.env.FRONTEND_URL}/success`,
      cancelUrl || `${process.env.FRONTEND_URL}/cancel`,
      service // Pass service to Stripe metadata
    );

    res.json({
      success: true,
      sessionId: session.id,
      url: session.url
    });

  } catch (error) {
    console.error('Checkout error:', error);
    res.status(500).json({
      error: 'Failed to create checkout session',
      code: 'FAILED_TO_CREATE_CHECKOUT_SESSION',
      message: error.message
    });
  }
});
```

### Step 4: Verify Stripe Key

Make sure the Stripe key is working. Check Render environment variables:
- `STRIPE_SECRET_KEY` should be set to: `sk_live_YOUR_KEY_HERE` (replace with your actual Stripe secret key)

### Step 5: Deploy

1. Commit the changes:
   ```bash
   cd alttext-ai-backend-clone
   git add routes/billing.js
   git commit -m "Add SEO AI Meta price IDs to validPrices array"
   git push origin main
   ```

2. Render will auto-deploy (wait 2-5 minutes)

3. Test the checkout again

## Quick Test

After deploying, test with:
```bash
curl -X POST "https://alttext-ai-backend.onrender.com/billing/checkout" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "priceId": "price_1SQ6a5Jl9Rm418cMx77q8KB9",
    "service": "seo-ai-meta",
    "successUrl": "https://test.com/success",
    "cancelUrl": "https://test.com/cancel"
  }'
```

Should return a checkout URL, not a 500 error.

## Price IDs to Add

- **Pro:** `price_1SQ6a5Jl9Rm418cMx77q8KB9` (£12.99/month)
- **Agency:** `price_1SQ6aTJl9Rm418cMQz47wCZ2` (£49.99/month)

These are the actual Stripe Price IDs you created earlier.


