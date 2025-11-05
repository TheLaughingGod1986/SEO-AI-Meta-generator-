# 🔧 Fix Checkout - Step by Step

## The Simplest Way: Update on GitHub

Since git has conflicts, update the file directly on GitHub:

### Step 1: Open GitHub File
1. Go to: https://github.com/TheLaughingGod1986/alttext-ai-backend/blob/main/routes/billing.js
2. Click the **pencil icon** (Edit this file) at the top right

### Step 2: Make These Changes

**Find this line (around line 19):**
```javascript
const { priceId, successUrl, cancelUrl, service = 'alttext-ai' } = req.body;
```

**Replace with:**
```javascript
const { priceId, price_id, successUrl, cancelUrl, service = 'alttext-ai' } = req.body;

// Use price_id if provided, otherwise priceId (for backward compatibility)
const actualPriceId = price_id || priceId;
```

**Find this line (around line 21):**
```javascript
if (!priceId) {
```

**Replace with:**
```javascript
if (!actualPriceId) {
```

**Find this line (around line 46):**
```javascript
if (!pricesToCheck.includes(priceId)) {
```

**Replace with:**
```javascript
if (!pricesToCheck.includes(actualPriceId)) {
  return res.status(400).json({
    error: `Invalid price ID for ${service} service`,
    code: 'INVALID_PRICE_ID',
    provided: actualPriceId,
    valid: servicePrices
  });
}
```

**Find this line (around line 58):**
```javascript
const session = await createCheckoutSession(
  req.user.id,
  priceId,
```

**Replace with:**
```javascript
const session = await createCheckoutSession(
  req.user.id,
  actualPriceId,
```

**Find the catch block (around line 72):**
```javascript
} catch (error) {
  console.error('Checkout error:', error);
  res.status(500).json({
    error: 'Failed to create checkout session',
    code: 'CHECKOUT_ERROR'
  });
}
```

**Replace with:**
```javascript
} catch (error) {
  console.error('Checkout error:', error);
  console.error('Error details:', {
    message: error.message,
    stack: error.stack,
    type: error.type,
    code: error.code
  });
  res.status(500).json({
    error: 'Failed to create checkout session',
    code: 'FAILED_TO_CREATE_CHECKOUT_SESSION',
    message: error.message || 'Unknown error'
  });
}
```

### Step 3: Commit
1. Scroll down to "Commit changes"
2. Message: `Fix checkout: handle price_id parameter for SEO AI Meta`
3. Click **"Commit changes"**

### Step 4: Wait for Auto-Deploy
- Render will automatically deploy (takes 2-3 minutes)
- Check Render dashboard to see deployment status

### Step 5: Test
- Go to WordPress Admin → SEO AI Meta
- Click upgrade button
- Should redirect to Stripe checkout ✅

## Alternative: Manual Deploy After GitHub Update
If auto-deploy doesn't trigger:
1. Go to Render dashboard
2. Click "Manual Deploy"
3. Select "Clear build cache & deploy"

