# Render Environment Variables - LIVE MODE

Update these environment variables on Render for the `alttext-ai-backend` service:

## Service URL
https://dashboard.render.com/web/srv-d3r1hjggjchc73bnp39g

## Environment Variables to Update

### Stripe Price IDs (LIVE MODE)

**STRIPE_PRICE_PRO**
```
price_1SQ72OJl9Rm418cMruYB5Pgb
```
- Product: SEO AI Meta Pro
- Price: £12.99/month
- Mode: LIVE

**STRIPE_PRICE_AGENCY**
```
price_1SQ72KJl9Rm418cMB0CYh8xe
```
- Product: SEO AI Meta Agency
- Price: £49.99/month
- Mode: LIVE

### Stripe API Keys

**STRIPE_SECRET_KEY**
Should be your LIVE secret key (starts with `sk_live_`)
```
sk_live_... (your live secret key from Stripe dashboard)
```

**STRIPE_PUBLISHABLE_KEY** (if used)
Should be your LIVE publishable key (starts with `pk_live_`)
```
pk_live_51RiPpcJl9Rm418cM5XjN5ifLheeWAERJWhy9vKRxoicCCe6dLdRhTuYsiO50RYh9lBdcATtgsXF1D47dYw2IK7AC00rhxuU5sy
```

## After Updating

1. Click "Save Changes"
2. Render will automatically redeploy the backend (~2-3 minutes)
3. Wait for deployment to complete
4. Test the upgrade button in your WordPress plugin

## Verification

You can verify the price IDs are correct by running:
```bash
stripe prices retrieve price_1SQ72OJl9Rm418cMruYB5Pgb --live
stripe prices retrieve price_1SQ72KJl9Rm418cMB0CYh8xe --live
```

Both should return `"livemode": true`

## Old Test Mode Price IDs (DO NOT USE)

These were the old test mode IDs that caused the checkout errors:
- ~~price_1SQ6a5Jl9Rm418cMx77q8KB9~~ (test - old Pro)
- ~~price_1SQ6aTJl9Rm418cMQz47wCZ2~~ (test - old Agency)
