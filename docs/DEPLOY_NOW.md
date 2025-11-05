# 🚀 Deploy Now - Step-by-Step Guide

## ✅ What's Already Done

1. ✅ Backend code updated with service support
2. ✅ Plugin API client updated
3. ✅ All files syntax verified
4. ✅ Migration SQL created
5. ✅ Test scripts created

## 🎯 Deploy in 3 Steps

### Step 1: Run Database Migration (2 minutes)

**Connect to your production database and run:**

```sql
-- Add service column to users
ALTER TABLE "users" ADD COLUMN IF NOT EXISTS "service" TEXT DEFAULT 'alttext-ai';
CREATE INDEX IF NOT EXISTS "users_service_idx" ON "users"("service");

-- Add service column to usage_logs
ALTER TABLE "usage_logs" ADD COLUMN IF NOT EXISTS "service" TEXT DEFAULT 'alttext-ai';
CREATE INDEX IF NOT EXISTS "usage_logs_userId_service_idx" ON "usage_logs"("userId", "service");

-- Update existing records (if any)
UPDATE "users" SET "service" = 'alttext-ai' WHERE "service" IS NULL;
UPDATE "usage_logs" SET "service" = 'alttext-ai' WHERE "service" IS NULL;
```

**How to run:**
- **Render**: Go to Database → Connect → Run SQL
- **Local**: Use psql or your database client
- **Production**: Use your database management tool

### Step 2: Deploy Backend (automatic)

```bash
cd "/Users/benjaminoats/Library/CloudStorage/SynologyDrive-File-sync/Coding/wp-alt-text-ai/alttext-ai-backend-clone"

# Review changes
git status

# Commit and push
git add .
git commit -m "Add SEO AI Meta service support"
git push origin main

# Wait for Render to deploy (check dashboard)
```

**Render will automatically:**
- Build the application
- Run Prisma generate
- Deploy to production
- Restart services

### Step 3: Test Connection (5 minutes)

**Option A: Test Backend API**
```bash
cd "/Users/benjaminoats/Library/CloudStorage/SynologyDrive-File-sync/Coding/wp-alt-text-ai/alttext-ai-backend-clone"
./test-seo-ai-meta-api.sh
```

**Option B: Test in WordPress**
1. Go to WordPress Admin → Posts → SEO AI Meta
2. Click "Login" button
3. Register with email/password
4. Verify:
   - Dashboard shows usage (0/10 for free)
   - Plans show SEO AI Meta pricing
   - Everything works!

## 🎉 Done!

Once these 3 steps are complete, your SEO AI Meta Generator is fully connected to the backend!

## 📝 Optional: Stripe Products (Later)

You can create Stripe products later. The plugin works in free mode (10 posts/month) without them.

When ready:
1. Create products in Stripe Dashboard
2. Update Price IDs in `routes/billing.js`
3. Redeploy backend

## 🔍 Quick Verification

After deployment, test:

```bash
# Test plans endpoint
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"

# Should return SEO AI Meta plans with limits: 10/100/1000
```

## 🆘 Troubleshooting

**If backend doesn't deploy:**
- Check Render logs
- Verify DATABASE_URL is set
- Check git push was successful

**If plugin can't connect:**
- Verify backend URL: `https://alttext-ai-backend.onrender.com`
- Check browser console for errors
- Verify backend is deployed and running

**If migration fails:**
- Check database permissions
- Verify you're using the correct database
- Try running SQL statements one at a time

## ✅ Success Checklist

After deployment, verify:
- [ ] Backend health endpoint works
- [ ] `/billing/plans?service=seo-ai-meta` returns SEO AI Meta plans
- [ ] WordPress plugin login works
- [ ] Usage shows correct limits (10/100/1000)
- [ ] Dashboard displays correctly

---

**Ready? Run Step 1, then Step 2, then Step 3!**

