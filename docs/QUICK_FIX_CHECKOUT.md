# 🚀 Quick Fix: Deploy Checkout Fix to Render

## The Problem
Checkout returns 500 errors because the backend code changes aren't deployed yet.

## The Solution (2 Steps)

### Step 1: Deploy to Render

**Option A: Manual Deploy (Recommended)**
1. Open: https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g
2. Click **"Manual Deploy"** button (top right)
3. Select **"Clear build cache & deploy"**
4. Click **"Deploy"**
5. Wait 2-3 minutes for deployment

**Option B: Auto-Deploy (if git is fixed)**
The changes will auto-deploy once pushed to GitHub, but we have git conflicts right now.

### Step 2: Test After Deployment

1. Wait for deployment to complete (status shows "Live")
2. Go to WordPress Admin → SEO AI Meta → Dashboard
3. Click "Upgrade to Pro" or "Unlock Unlimited AI Power"
4. Click a plan button
5. Should redirect to Stripe checkout ✅

## What Was Fixed

The backend code now:
- ✅ Recognizes SEO AI Meta price IDs
- ✅ Handles both `priceId` and `price_id` parameters
- ✅ Provides better error messages
- ✅ Improved error logging

## If It Still Doesn't Work

1. Check Render logs for errors
2. Verify Stripe API key is set in Render environment variables
3. Check that price IDs are correct in Stripe dashboard

