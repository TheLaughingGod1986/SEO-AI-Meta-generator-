#!/bin/bash
# Quick verification script for backend deployment

API_URL="${API_URL:-https://alttext-ai-backend.onrender.com}"

echo "🔍 Verifying Backend Deployment"
echo "================================"
echo "API URL: $API_URL"
echo ""

# Test 1: Health check
echo "1️⃣ Testing health endpoint..."
HEALTH=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/health")
if [ "$HEALTH" = "200" ]; then
    echo "   ✅ Backend is live!"
else
    echo "   ❌ Backend not responding (HTTP $HEALTH)"
    echo "   ⏳ Deployment may still be in progress"
fi
echo ""

# Test 2: Plans endpoint (SEO AI Meta)
echo "2️⃣ Testing SEO AI Meta plans endpoint..."
PLANS_RESPONSE=$(curl -s "$API_URL/billing/plans?service=seo-ai-meta")
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/billing/plans?service=seo-ai-meta")

if [ "$HTTP_CODE" = "200" ]; then
    echo "   ✅ Plans endpoint working!"
    echo ""
    echo "   Plans returned:"
    echo "$PLANS_RESPONSE" | jq -r '.plans[] | "      - \(.name): \(.posts) posts/month (£\(.price))"' 2>/dev/null || echo "$PLANS_RESPONSE"
    
    # Check for SEO AI Meta specific limits
    FREE_LIMIT=$(echo "$PLANS_RESPONSE" | jq -r '.plans[] | select(.id=="free") | .posts' 2>/dev/null)
    if [ "$FREE_LIMIT" = "10" ]; then
        echo "   ✅ Free plan limit correct (10 posts)"
    else
        echo "   ⚠️  Free plan limit: $FREE_LIMIT (expected 10)"
    fi
else
    echo "   ❌ Plans endpoint failed (HTTP $HTTP_CODE)"
    echo "   Response: $PLANS_RESPONSE"
fi
echo ""

# Test 3: Usage endpoint (requires auth, just check endpoint exists)
echo "3️⃣ Testing usage endpoint structure..."
USAGE_HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/usage?service=seo-ai-meta")
if [ "$USAGE_HTTP" = "401" ] || [ "$USAGE_HTTP" = "200" ]; then
    echo "   ✅ Usage endpoint exists (auth required)"
else
    echo "   ⚠️  Usage endpoint returned HTTP $USAGE_HTTP"
fi
echo ""

# Summary
echo "📊 Summary:"
if [ "$HEALTH" = "200" ] && [ "$HTTP_CODE" = "200" ]; then
    echo "   ✅ Backend is deployed and working!"
    echo "   ✅ SEO AI Meta service is supported"
    echo ""
    echo "   🎯 Next: Test WordPress plugin"
else
    echo "   ⏳ Backend may still be deploying..."
    echo "   ⏳ Wait 2-5 minutes and try again"
fi

