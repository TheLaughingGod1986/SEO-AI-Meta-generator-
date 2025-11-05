# 🔌 Backend & Stripe Connection Status

## ✅ Connection Test Results

### 1. Backend API Connection
**Status:** ✅ **WORKING**

- **Backend URL:** `https://alttext-ai-backend.onrender.com`
- **API Endpoint Test:** `/billing/plans?service=seo-ai-meta`
- **Response:** ✅ Backend is responding successfully
- **HTTP Status:** 200 OK

### 2. Stripe Price IDs Configuration
**Status:** ✅ **CONFIGURED**

The plugin has Stripe Price IDs configured in `includes/class-seo-ai-meta-core.php`:

- **Pro Plan Price ID:** `price_1SQ6a5Jl9Rm418cMx77q8KB9` (£12.99/month)
- **Agency Plan Price ID:** `price_1SQ6aTJl9Rm418cMQz47wCZ2` (£49.99/month)

These were created according to `STRIPE_PRODUCTS_CREATED.md`.

### 3. Backend API Client
**Status:** ✅ **CONFIGURED**

- API client is properly set up in `includes/class-api-client-v2.php`
- Uses JWT authentication
- Supports all required endpoints:
  - `/auth/register` - User registration
  - `/auth/login` - User login
  - `/auth/me` - Get user info
  - `/usage` - Get usage statistics
  - `/billing/info` - Get billing info
  - `/billing/plans` - Get available plans
  - `/billing/checkout` - Create Stripe checkout session
  - `/billing/portal` - Create customer portal session

### 4. Stripe Integration
**Status:** ⚠️ **NEEDS VERIFICATION**

The Stripe integration code is in place, but we need to verify:

1. **Backend Stripe Configuration:**
   - Backend must have Stripe API keys configured
   - Backend must accept the Price IDs configured in the plugin
   - Backend must create checkout sessions with `service=seo-ai-meta` metadata

2. **Test Checkout Session:**
   - Requires user authentication first
   - Can be tested by clicking "Upgrade" button in WordPress admin

## 🧪 How to Test Full Connection

### Option 1: Use WordPress Admin
1. Go to **SEO AI Meta → Dashboard** in WordPress admin
2. Click **"Login"** button (if not authenticated)
3. Enter your email and password
4. Click **"Upgrade to Pro"** or **"Unlock Unlimited AI Power"**
5. Click a plan button
6. Should redirect to Stripe checkout ✅

### Option 2: Run Test Script
From WordPress root directory:
```bash
wp eval-file seo-ai-meta-generator/test-connections.php
```

This will test:
- Backend connectivity
- Authentication status
- Stripe Price IDs
- Checkout session creation
- Billing endpoints

### Option 3: Manual API Test
```bash
# Test backend plans endpoint
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"

# Test checkout (requires authentication token)
curl -X POST "https://alttext-ai-backend.onrender.com/billing/checkout" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "priceId": "price_1SQ6a5Jl9Rm418cMx77q8KB9",
    "successUrl": "https://your-site.com/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=success",
    "cancelUrl": "https://your-site.com/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=cancel",
    "service": "seo-ai-meta"
  }'
```

## ⚠️ Potential Issues

### Issue 1: Backend Stripe Configuration
**Symptom:** Checkout fails with "Failed to create checkout session"

**Solution:**
- Verify backend has Stripe API keys set in environment variables
- Check backend logs for Stripe errors
- Ensure backend accepts the Price IDs configured in plugin

### Issue 2: Price ID Mismatch
**Symptom:** Backend rejects Price ID or returns error

**Solution:**
- Verify Price IDs exist in Stripe dashboard
- Check if Price IDs are in test mode (plugin Price IDs appear to be test mode)
- Ensure backend is configured to accept these Price IDs

### Issue 3: Authentication Required
**Symptom:** Checkout fails with "Authentication required"

**Solution:**
- User must log in via WordPress admin first
- Check if JWT token is stored: `get_option('seo_ai_meta_jwt_token')`
- Re-authenticate if token expired

## 📋 Configuration Summary

### Plugin Configuration
- ✅ Backend URL: `https://alttext-ai-backend.onrender.com`
- ✅ Stripe Price IDs: Configured in code
- ✅ API Client: Fully implemented
- ✅ Checkout Flow: Implemented

### Backend Requirements
- ✅ Backend is deployed and responding
- ⚠️ Backend Stripe keys: Need to verify
- ⚠️ Backend accepts SEO AI Meta Price IDs: Need to verify
- ⚠️ Backend webhooks configured: Need to verify

## 🎯 Next Steps

1. **Test Authentication:**
   - Log in via WordPress admin
   - Verify JWT token is stored

2. **Test Checkout:**
   - Click upgrade button
   - Verify redirect to Stripe checkout

3. **Verify Backend Stripe Setup:**
   - Check backend environment variables for Stripe keys
   - Test checkout session creation via backend API

4. **Test Webhooks:**
   - Complete a test purchase
   - Verify subscription is created in backend
   - Verify usage limits are updated

## 📝 Files to Check

- `includes/class-api-client-v2.php` - API client configuration
- `includes/class-seo-ai-meta-core.php` - Stripe Price IDs (lines 15-17)
- `STRIPE_PRODUCTS_CREATED.md` - Stripe product information
- `test-connections.php` - Comprehensive test script

---

**Last Updated:** Generated by connection test  
**Status:** Backend connected ✅ | Stripe configured ✅ | Needs live testing ⚠️


