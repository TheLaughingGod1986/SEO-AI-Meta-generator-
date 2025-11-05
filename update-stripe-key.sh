#!/bin/bash
# Script to update Stripe API key on Render backend service

echo "🔧 Updating Stripe API Key on Render"
echo "===================================="
echo ""

# Service name
SERVICE_NAME="alttext-ai-backend"

# Check if Stripe key is provided as argument
if [ -z "$1" ]; then
    echo "❌ Error: Stripe secret key not provided"
    echo ""
    echo "Usage:"
    echo "  ./update-stripe-key.sh sk_live_YOUR_KEY_HERE"
    echo ""
    echo "To get your Stripe key:"
    echo "  1. Go to https://dashboard.stripe.com/apikeys"
    echo "  2. Make sure you're in LIVE mode (not test)"
    echo "  3. Copy your Secret key (starts with sk_live_)"
    echo ""
    exit 1
fi

STRIPE_KEY="$1"

# Validate key format
if [[ ! "$STRIPE_KEY" =~ ^sk_live_ ]]; then
    echo "⚠️  Warning: Key doesn't start with 'sk_live_'"
    echo "   Make sure you're using the LIVE key, not the test key!"
    echo ""
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "Service: $SERVICE_NAME"
echo "Key: ${STRIPE_KEY:0:20}... (hidden for security)"
echo ""

# Get service ID
echo "Finding service ID..."
SERVICE_ID=$(render services list --output json 2>/dev/null | grep -A 20 "\"name\": \"$SERVICE_NAME\"" | grep "\"id\"" | head -1 | sed 's/.*"id": "\([^"]*\)".*/\1/')

if [ -z "$SERVICE_ID" ]; then
    echo "⚠️  Could not find service ID automatically"
    echo ""
    echo "Please update the key manually via Render Dashboard:"
    echo ""
    echo "1. Go to: https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g"
    echo "   (Direct link to your $SERVICE_NAME service)"
    echo "2. Go to 'Environment' tab"
    echo "3. Find 'STRIPE_SECRET_KEY' → Click 'Edit' or 'Update'"
    echo "4. Paste your key: ${STRIPE_KEY:0:20}..."
    echo "5. Click 'Save Changes'"
    echo ""
    echo "Your full Stripe Key (copy this):"
    echo "$STRIPE_KEY"
    echo ""
    exit 1
fi

echo "Found service ID: $SERVICE_ID"
echo ""

# Try using Render API with curl
echo "Attempting to update via Render API..."
echo ""

# Get API token from Render CLI config
RENDER_API_KEY=$(cat ~/.render/api_key 2>/dev/null || echo "")

if [ -z "$RENDER_API_KEY" ]; then
    echo "⚠️  Render API key not found in CLI config"
    echo ""
    echo "Please update the key manually via Render Dashboard:"
    echo ""
    echo "📋 Quick Steps:"
    echo "1. Open: https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g/environment"
    echo "2. Find 'STRIPE_SECRET_KEY' → Click 'Edit'"
    echo "3. Paste: $STRIPE_KEY"
    echo "4. Click 'Save Changes'"
    echo ""
    echo "Render will automatically restart the service with the new key."
    exit 1
fi

# Use Render API to update environment variable
RESPONSE=$(curl -s -X PUT "https://api.render.com/v1/services/$SERVICE_ID/env-vars" \
  -H "Authorization: Bearer $RENDER_API_KEY" \
  -H "Content-Type: application/json" \
  -d "{\"envVars\": [{\"key\": \"STRIPE_SECRET_KEY\", \"value\": \"$STRIPE_KEY\"}]}")

if echo "$RESPONSE" | grep -q "error\|Error"; then
    echo "⚠️  API update failed. Please use the dashboard method below."
    echo ""
    echo "📋 Update via Dashboard:"
    echo "1. Open: https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g/environment"
    echo "2. Find 'STRIPE_SECRET_KEY' → Click 'Edit'"
    echo "3. Paste: $STRIPE_KEY"
    echo "4. Click 'Save Changes'"
    exit 1
else
    echo "✅ Successfully updated STRIPE_SECRET_KEY via API"
    echo ""
    echo "Render will automatically restart the service with the new key."
    echo "Wait 1-2 minutes, then test the upgrade button again."
fi

