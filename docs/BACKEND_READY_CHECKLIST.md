# ✅ Backend Ready Checklist

## 🔴 Critical: Database Migration

**The backend will NOT work properly until you run the database migration!**

### 📋 Migration Steps:

1. **Go to Render Dashboard:**
   - https://dashboard.render.com
   - Click your **PostgreSQL database**

2. **Open SQL Editor:**
   - Click **"Connect"** or **"Query"** tab
   - Click **"Connect"** or **"Open Query Editor"**

3. **Run This SQL:**
   ```sql
   ALTER TABLE "users" ADD COLUMN IF NOT EXISTS "service" TEXT NOT NULL DEFAULT 'alttext-ai';
   CREATE INDEX IF NOT EXISTS "users_service_idx" ON "users"("service");
   ALTER TABLE "usage_logs" ADD COLUMN IF NOT EXISTS "service" TEXT NOT NULL DEFAULT 'alttext-ai';
   CREATE INDEX IF NOT EXISTS "usage_logs_userId_service_idx" ON "usage_logs"("userId", "service");
   ```

4. **Click "Run" or "Execute"**

5. **Wait for success message** ✅

---

## ⏳ After Migration:

1. **Wait 2-5 minutes** for Render backend to fully deploy
2. **Test the connection** using the script below
3. **Check the WordPress plugin** dashboard

---

## 🧪 Test Connection:

Once migration is complete and backend is deployed, run:

```bash
cd "/Users/benjaminoats/Library/CloudStorage/SynologyDrive-File-sync/Coding/SEO AI Meta Generator/seo-ai-meta-generator"
./verify-deployment.sh
```

Or test manually:

```bash
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"
```

Should return JSON with SEO AI Meta plans (Free, Pro, Agency).

---

## ⚠️ Common Issues:

- **502 Bad Gateway:** Backend still deploying (wait 2-5 min)
- **Migration not run:** Backend will return errors for SEO AI Meta endpoints
- **Database connection failed:** Check Render database status

---

**Status:** ⏳ Waiting for migration to be run



