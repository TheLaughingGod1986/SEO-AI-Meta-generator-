# 🚀 Quick Deployment Checklist

## Pre-Deployment

- [x] ✅ Backend code updated with service support
- [x] ✅ Plugin API client updated to send service parameter
- [x] ✅ Database migration SQL file created
- [x] ✅ All JavaScript files syntax verified
- [x] ✅ All PHP files syntax verified

## Step 1: Database Migration ⚠️ **REQUIRED**

**Run this SQL on your production database:**

```sql
ALTER TABLE "users" ADD COLUMN IF NOT EXISTS "service" TEXT DEFAULT 'alttext-ai';
CREATE INDEX IF NOT EXISTS "users_service_idx" ON "users"("service");
ALTER TABLE "usage_logs" ADD COLUMN IF NOT EXISTS "service" TEXT DEFAULT 'alttext-ai';
CREATE INDEX IF NOT EXISTS "usage_logs_userId_service_idx" ON "usage_logs"("userId", "service");
UPDATE "users" SET "service" = 'alttext-ai' WHERE "service" IS NULL;
UPDATE "usage_logs" SET "service" = 'alttext-ai' WHERE "service" IS NULL;
```

**Or use Prisma:**
```bash
cd alttext-ai-backend-clone
npx prisma migrate deploy
npx prisma generate
```

## Step 2: Create Stripe Products ⚠️ **REQUIRED FOR CHECKOUT**

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/products)
2. Click "Add product"
3. Create **SEO AI Meta Pro**:
   - Name: `SEO AI Meta Pro`
   - Description: `100 AI-generated meta tags per month`
   - Price: £12.99, Monthly (recurring)
   - **Copy Price ID** (starts with `price_`)
4. Create **SEO AI Meta Agency**:
   - Name: `SEO AI Meta Agency`
   - Description: `1000 AI-generated meta tags per month`
   - Price: £49.99, Monthly (recurring)
   - **Copy Price ID** (starts with `price_`)
5. Update `routes/billing.js` line 36-39:
   ```javascript
   'seo-ai-meta': [
     "price_YOUR_PRO_ID_HERE",      // Replace with actual
     "price_YOUR_AGENCY_ID_HERE"    // Replace with actual
   ]
   ```

## Step 3: Deploy Backend

```bash
cd alttext-ai-backend-clone

# Option A: Use deployment script
./deploy-seo-ai-meta.sh

# Option B: Manual deployment
git add .
git commit -m "Add SEO AI Meta service support"
git push origin main
```

**Wait for Render to deploy** (check Render dashboard)

## Step 4: Test Backend Endpoints

```bash
cd alttext-ai-backend-clone
./test-seo-ai-meta-api.sh
```

**Or manually test:**
```bash
# Test plans
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"

# Test usage (after login)
curl "https://alttext-ai-backend.onrender.com/usage?service=seo-ai-meta" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Step 5: Test WordPress Plugin

1. **Go to WordPress Admin** → Posts → SEO AI Meta
2. **Click "Login"** button in header
3. **Register** with email/password
4. **Verify:**
   - [ ] Dashboard shows usage (should be 0/10 for free)
   - [ ] Plans show SEO AI Meta pricing
   - [ ] "Generate All" button works
   - [ ] Meta generation works in post editor

## Step 6: Verify Everything Works

- [ ] ✅ Registration works
- [ ] ✅ Login works  
- [ ] ✅ Usage tracking shows correct limits (10/100/1000)
- [ ] ✅ Plans endpoint returns SEO AI Meta plans
- [ ] ✅ Checkout redirects to Stripe (after creating products)
- [ ] ✅ AltText AI still works (backward compatibility)

## 🎯 Quick Test Commands

### Test Backend (from backend directory):
```bash
./test-seo-ai-meta-api.sh
```

### Test WordPress Plugin (from WordPress):
```bash
wp eval-file seo-ai-meta-generator/test-backend-connection.php
```

### Or test in WordPress admin:
1. Go to Posts → SEO AI Meta
2. Click Login
3. Register/Login
4. Check dashboard shows correct usage

## 📝 If Something Doesn't Work

### Backend not responding:
- Check Render logs
- Verify migration ran successfully
- Check DATABASE_URL is set correctly

### Wrong usage limits:
- Verify service parameter is being sent
- Check database has service field
- Verify migration ran

### Stripe checkout fails:
- Create Stripe products first
- Update Price IDs in routes/billing.js
- Redeploy backend

### Plugin can't connect:
- Check backend URL in plugin settings
- Verify backend is deployed
- Check browser console for errors

## ✅ Success Criteria

When everything works:
- ✅ Plugin connects to backend
- ✅ Registration/login works
- ✅ Usage shows 10/10 (free plan)
- ✅ Plans show SEO AI Meta pricing
- ✅ Meta generation works
- ✅ Dashboard displays correctly

## 🎉 You're Done!

Once all checks pass, your SEO AI Meta Generator is fully connected to the backend and ready for production use!

