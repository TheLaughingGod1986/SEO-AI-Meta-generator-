# 🗄️ Setup Separate Database for SEO AI Meta on Render

## Overview
Create a separate PostgreSQL database on Render for the SEO AI Meta Generator plugin, separate from AltText AI.

## Step 1: Create New Database on Render

1. **Go to Render Dashboard:**
   - https://dashboard.render.com
   - Click **"New +"** → **"PostgreSQL"**

2. **Configure Database:**
   - **Name:** `seo-ai-meta-db`
   - **Database:** `seo_ai_meta_db`
   - **User:** `seo_ai_meta_db_user`
   - **Region:** Same as your backend (e.g., `oregon`)
   - **Plan:** `Free` (or your preferred plan)
   - Click **"Create Database"**

3. **Wait for Database to Start:**
   - Takes about 1-2 minutes
   - Status will show "Available" when ready

4. **Copy Connection Details:**
   - Click on the database
   - Go to **"Connections"** tab
   - Copy the **"Internal Database URL"** (for backend service)
   - Copy the **"External Connection String"** (for external tools)

## Step 2: Update Backend Environment Variables

The backend needs to be configured to use different databases based on the service.

### Option A: Use Same Database (Recommended for now)
If you want to keep using the same backend but just separate the data logically, you can:
- Keep using the existing database
- The `service` field in the database already separates data
- Just update the backend to accept SEO AI Meta price IDs

### Option B: Use Separate Database (Full Separation)
If you want complete separation, update the backend to:
1. Check the `service` parameter
2. Use different database connections based on service
3. Update `DATABASE_URL` environment variable or use service-specific env vars

**Backend Code Update Needed:**
```javascript
// In backend, use service-specific database URLs
const getDatabaseUrl = (service) => {
  if (service === 'seo-ai-meta') {
    return process.env.SEO_AI_META_DATABASE_URL || process.env.DATABASE_URL;
  }
  return process.env.DATABASE_URL; // AltText AI
};
```

## Step 3: Add Database URL to Backend Environment

1. Go to Render Dashboard → `alttext-ai-backend` service
2. Go to **"Environment"** tab
3. Add new environment variable:
   - **Key:** `SEO_AI_META_DATABASE_URL`
   - **Value:** The internal database URL from Step 1
4. Click **"Save Changes"**

## Step 4: Run Database Migrations

If using a separate database, you'll need to run Prisma migrations:

```bash
# In backend repository
# Set the database URL for SEO AI Meta
export DATABASE_URL="your-seo-ai-meta-database-url"

# Run migrations
npx prisma migrate deploy

# Generate Prisma client
npx prisma generate
```

Or via Render:
1. Go to backend service → **"Shell"** tab
2. Run the migration commands

## Step 5: Update Backend to Use Service-Specific Database

You'll need to update the backend code to:
1. Check the `service` parameter in requests
2. Use the appropriate database connection
3. Update Prisma client initialization

**Example Backend Update:**
```javascript
// In backend prisma client setup
const prisma = new PrismaClient({
  datasources: {
    db: {
      url: process.env.SEO_AI_META_DATABASE_URL || process.env.DATABASE_URL
    }
  }
});
```

## Recommendation

**For now, I recommend:**
1. Keep using the same database (simpler)
2. The `service` field already separates the data
3. Just make sure the backend accepts the SEO AI Meta price IDs

**Later, if needed:**
- You can migrate to a separate database
- Export/import data as needed
- Update backend to use service-specific databases

## Current Status

The backend already supports multiple services via the `service` parameter. The main issue right now is:
1. ✅ Database schema supports `service` field
2. ❌ Backend needs to accept SEO AI Meta price IDs in `validPrices` array
3. ❌ Stripe key might need verification

Would you like me to help you:
- A) Create the separate database and update backend code?
- B) Just fix the price ID issue in the existing setup?
- C) Check the backend repository to see what needs updating?


