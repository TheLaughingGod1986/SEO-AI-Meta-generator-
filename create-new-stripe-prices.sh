#!/bin/bash

# Script to create new Stripe prices for SEO AI Meta Generator
# New pricing: Pro £14.99 (500 posts), Agency £59.99 (5,000 posts)

echo "======================================"
echo "SEO AI Meta - Create New Stripe Prices"
echo "======================================"
echo ""

# Check if Stripe CLI is installed
if ! command -v stripe &> /dev/null; then
    echo "❌ Stripe CLI not found. Please install it first:"
    echo "   https://stripe.com/docs/stripe-cli"
    exit 1
fi

echo "✅ Stripe CLI found"
echo ""

# Product IDs (from existing products)
PRO_PRODUCT_ID="prod_TMqC8YcBjUJ1cT"
AGENCY_PRODUCT_ID="prod_TMqGCTJ57c18Dn"

echo "📦 Product IDs:"
echo "   Pro: $PRO_PRODUCT_ID"
echo "   Agency: $AGENCY_PRODUCT_ID"
echo ""

# Ask user if they want test or live mode
read -p "Create prices in [T]est or [L]ive mode? (t/l): " mode
mode=$(echo "$mode" | tr '[:upper:]' '[:lower:]')

if [[ "$mode" == "l" ]]; then
    echo ""
    echo "⚠️  LIVE MODE SELECTED"
    echo "   This will create real prices that customers can subscribe to."
    read -p "Are you sure? (yes/no): " confirm
    if [[ "$confirm" != "yes" ]]; then
        echo "Aborted."
        exit 0
    fi
    LIVE_FLAG="--live"
    MODE_NAME="LIVE"
else
    LIVE_FLAG=""
    MODE_NAME="TEST"
fi

echo ""
echo "🚀 Creating $MODE_NAME prices..."
echo ""

# Create Pro price (£14.99/month, 500 posts)
echo "1️⃣  Creating Pro plan price..."
PRO_PRICE_OUTPUT=$(stripe prices create \
  --product="$PRO_PRODUCT_ID" \
  --unit-amount=1499 \
  --currency=gbp \
  --recurring.interval=month \
  --nickname="SEO AI Meta Pro - £14.99/month (500 posts)" \
  -d "metadata[posts_per_month]=500" \
  $LIVE_FLAG 2>&1)

if echo "$PRO_PRICE_OUTPUT" | grep -q '"id"'; then
    PRO_PRICE_ID=$(echo "$PRO_PRICE_OUTPUT" | grep '"id"' | head -1 | sed 's/.*"id": "\([^"]*\)".*/\1/')
    echo "   ✅ Pro price created: $PRO_PRICE_ID"
else
    echo "   ❌ Failed to create Pro price"
    echo "$PRO_PRICE_OUTPUT"
    exit 1
fi

echo ""

# Create Agency price (£59.99/month, 5,000 posts)
echo "2️⃣  Creating Agency plan price..."
AGENCY_PRICE_OUTPUT=$(stripe prices create \
  --product="$AGENCY_PRODUCT_ID" \
  --unit-amount=5999 \
  --currency=gbp \
  --recurring.interval=month \
  --nickname="SEO AI Meta Agency - £59.99/month (5,000 posts)" \
  -d "metadata[posts_per_month]=5000" \
  $LIVE_FLAG 2>&1)

if echo "$AGENCY_PRICE_OUTPUT" | grep -q '"id"'; then
    AGENCY_PRICE_ID=$(echo "$AGENCY_PRICE_OUTPUT" | grep '"id"' | head -1 | sed 's/.*"id": "\([^"]*\)".*/\1/')
    echo "   ✅ Agency price created: $AGENCY_PRICE_ID"
else
    echo "   ❌ Failed to create Agency price"
    echo "$AGENCY_PRICE_OUTPUT"
    exit 1
fi

echo ""
echo "======================================"
echo "✅ SUCCESS! New prices created"
echo "======================================"
echo ""
echo "🔑 New Price IDs ($MODE_NAME):"
echo ""
echo "Pro:    $PRO_PRICE_ID"
echo "Agency: $AGENCY_PRICE_ID"
echo ""
echo "📝 Next steps:"
echo ""
echo "1. Update your WordPress plugin settings:"
echo "   Go to: Posts → SEO AI Meta Generator → Settings"
echo "   Update the price IDs in the Checkout section"
echo ""
echo "2. Or update the code directly in:"
echo "   includes/class-seo-ai-meta-core.php (line 16-17)"
echo ""
echo "   Replace:"
echo "   'pro'     => 'price_1SQ72OJl9Rm418cMruYB5Pgb'"
echo "   'agency'  => 'price_1SQ72KJl9Rm418cMB0CYh8xe'"
echo ""
echo "   With:"
echo "   'pro'     => '$PRO_PRICE_ID'"
echo "   'agency'  => '$AGENCY_PRICE_ID'"
echo ""
echo "3. (Optional) Archive old prices in Stripe Dashboard:"
echo "   https://dashboard.stripe.com/products"
echo ""

