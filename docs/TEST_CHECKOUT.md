# 🧪 Test Checkout Flow

## Quick Test Checklist

### ✅ Step 1: Check Stripe Products
- [ ] Price IDs are configured in code
  - Pro: `price_1SQ6a5Jl9Rm418cMx77q8KB9`
  - Agency: `price_1SQ6aTJl9Rm418cMQz47wCZ2`

### ✅ Step 2: Check Backend
- [ ] Backend is deployed and responding
- [ ] Database migration has been run
- [ ] Backend supports `service=seo-ai-meta` parameter

### ✅ Step 3: Check WordPress Plugin
- [ ] Go to **WordPress Admin → Posts → SEO AI Meta**
- [ ] Check if "Login" button shows in header
- [ ] Check if upgrade buttons show (not "Coming Soon")

### ✅ Step 4: Test Authentication
- [ ] Click "Login" button (if shown)
- [ ] Log in with your backend account
- [ ] Should see "Logged in" status in header

### ✅ Step 5: Test Checkout
- [ ] Click "Upgrade to Pro" or "Unlock Unlimited AI Power"
- [ ] Click "Get Started with Pro" or "Upgrade to Agency"
- [ ] **Expected:** Redirects to Stripe checkout page
- [ ] **OR:** Shows error message at top of dashboard

## What Should Happen

### ✅ Success Flow:
1. Click upgrade button
2. If not logged in → See error: "Please log in to your account first"
3. If logged in → Redirects to Stripe checkout
4. Complete Stripe payment
5. Redirects back to dashboard with success message

### ❌ If It's Not Working:

**Check these in order:**

1. **Are you logged in?**
   - Look for "Login" button in header
   - Click it and log in first

2. **Is backend responding?**
   - Check: `curl "https://alttext-ai-backend.onrender.com/health"`
   - Should return 200 OK

3. **Are there error messages?**
   - Check top of dashboard for red error notices
   - Check browser console (F12) for JavaScript errors
   - Check WordPress debug log: `wp-content/debug.log`

4. **Is the page just reloading?**
   - Check browser console for errors
   - Enable WP_DEBUG and check logs
   - The handler might not be triggering

## Quick Debug Commands

```bash
# Test backend health
curl "https://alttext-ai-backend.onrender.com/health"

# Test plans endpoint (should return SEO AI Meta plans)
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"
```

## Expected Behavior

When you click an upgrade button:

1. **If NOT logged in:**
   - Page reloads
   - Red error message appears: "Please log in to your account first. Click the 'Login' button in the header."

2. **If logged in:**
   - Page redirects to Stripe checkout
   - You see Stripe payment form
   - Can complete test payment

---

**Status:** ⏳ Testing required - Please try clicking upgrade button and report what happens


