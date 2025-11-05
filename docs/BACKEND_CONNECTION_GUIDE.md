# Backend Connection Guide - SEO AI Meta Generator

This guide shows how to connect the SEO AI Meta Generator plugin to the AltText AI backend.

## Quick Start

The plugin is already configured to use the AltText AI backend at:
- **Production**: `https://alttext-ai-backend.onrender.com`
- **Local**: `http://host.docker.internal:3001` (when `WP_LOCAL_DEV` is defined)

## Backend Updates Required

### 1. Database Schema Update

Add a `service` field to track which service (AltText AI vs SEO AI Meta) a user is using.

#### Option A: Add Service to User Model (Recommended)

Update `prisma/schema.prisma`:

```prisma
model User {
  id                Int         @id @default(autoincrement())
  email             String      @unique
  passwordHash      String
  plan              String      @default("free")
  service           String      @default("alttext-ai") // Add this line
  tokensRemaining   Int         @default(50)
  credits           Int         @default(0)
  // ... rest of fields
}

model UsageLog {
  id        Int      @id @default(autoincrement())
  userId    Int
  service   String   @default("alttext-ai") // Add this line
  used      Int      @default(1)
  // ... rest of fields
}
```

Then run:
```bash
npx prisma migrate dev --name add_service_support
npx prisma generate
```

#### Option B: Service-Specific Usage Tracking (Alternative)

Create a separate tracking table for SEO AI Meta:

```prisma
model SeoAiMetaUsage {
  id        Int      @id @default(autoincrement())
  userId    Int
  used      Int      @default(0)
  limit     Int      @default(10)
  plan      String   @default("free")
  resetDate DateTime @default(now())
  createdAt DateTime @default(now())
  updatedAt DateTime @updatedAt
  user      User     @relation(fields: [userId], references: [id])
  
  @@unique([userId])
  @@map("seo_ai_meta_usage")
}
```

### 2. Update Usage Routes

Edit `routes/usage.js` to support service parameter:

```javascript
router.get('/', authenticateToken, async (req, res) => {
  try {
    const service = req.query.service || 'alttext-ai'; // Default to alttext-ai
    
    const user = await prisma.user.findUnique({
      where: { id: req.user.id },
      select: {
        id: true,
        plan: true,
        tokensRemaining: true,
        credits: true,
        resetDate: true,
        createdAt: true
      }
    });

    if (!user) {
      return res.status(404).json({
        error: 'User not found',
        code: 'USER_NOT_FOUND'
      });
    }

    // Service-specific plan limits
    const planLimits = {
      'alttext-ai': {
        free: 50,
        pro: 1000,
        agency: 10000
      },
      'seo-ai-meta': {
        free: 10,
        pro: 100,
        agency: 1000
      }
    };

    const limits = planLimits[service] || planLimits['alttext-ai'];
    const limit = limits[user.plan] || limits.free;
    const used = limit - user.tokensRemaining;
    const remaining = user.tokensRemaining;

    res.json({
      success: true,
      usage: {
        used,
        limit,
        remaining,
        plan: user.plan,
        credits: user.credits,
        resetDate: user.resetDate,
        nextReset: getNextResetDate(),
        service: service // Include service in response
      }
    });

  } catch (error) {
    console.error('Get usage error:', error);
    res.status(500).json({
      error: 'Failed to get usage info',
      code: 'USAGE_ERROR'
    });
  }
});
```

### 3. Update Billing Routes

Edit `routes/billing.js` to support SEO AI Meta price IDs:

```javascript
router.post('/checkout', authenticateToken, async (req, res) => {
  try {
    const { priceId, successUrl, cancelUrl, service = 'alttext-ai' } = req.body;

    if (!priceId) {
      return res.status(400).json({
        error: 'Price ID is required',
        code: 'MISSING_PRICE_ID'
      });
    }

    // Service-specific valid price IDs
    const validPrices = {
      'alttext-ai': [
        "price_1SMrxaJl9Rm418cMM4iikjlJ", // AltText AI Pro
        "price_1SMrxaJl9Rm418cMnJTShXSY", // AltText AI Agency
        "price_1SMrxbJl9Rm418cM0gkzZQZt"  // AltText AI ...
      ],
      'seo-ai-meta': [
        "price_XXX_SEO_PRO",      // SEO AI Meta Pro - NEEDS TO BE CREATED
        "price_XXX_SEO_AGENCY"    // SEO AI Meta Agency - NEEDS TO BE CREATED
      ]
    };

    const servicePrices = validPrices[service] || validPrices['alttext-ai'];

    if (!servicePrices.includes(priceId)) {
      return res.status(400).json({
        error: 'Invalid price ID for this service',
        code: 'INVALID_PRICE_ID'
      });
    }

    const session = await createCheckoutSession(
      req.user.id,
      priceId,
      successUrl || `${process.env.FRONTEND_URL}/success`,
      cancelUrl || `${process.env.FRONTEND_URL}/cancel`,
      service // Pass service to checkout
    );

    res.json({
      success: true,
      sessionId: session.id,
      url: session.url
    });

  } catch (error) {
    console.error('Checkout error:', error);
    res.status(500).json({
      error: 'Failed to create checkout session',
      code: 'CHECKOUT_ERROR'
    });
  }
});
```

### 4. Update Auth Routes (Optional)

Edit `auth/routes.js` to optionally store service:

```javascript
// In register route
router.post('/register', async (req, res) => {
  try {
    const { email, password, service = 'alttext-ai' } = req.body;
    
    // ... existing validation ...
    
    const user = await prisma.user.create({
      data: {
        email,
        passwordHash: hashedPassword,
        service: service, // Store service
        plan: 'free',
        tokensRemaining: service === 'seo-ai-meta' ? 10 : 50
      }
    });
    
    // ... rest of registration ...
  }
});

// In login route
router.post('/login', async (req, res) => {
  try {
    const { email, password, service } = req.body;
    
    // ... existing login logic ...
    
    // Service is optional - can login with any service
    // The service parameter is mainly for tracking/analytics
  }
});
```

### 5. Update Stripe Checkout Function

Edit `stripe/checkout.js`:

```javascript
async function createCheckoutSession(userId, priceId, successUrl, cancelUrl, service = 'alttext-ai') {
  const session = await stripe.checkout.sessions.create({
    customer: customerId,
    payment_method_types: ['card'],
    line_items: [
      {
        price: priceId,
        quantity: 1,
      },
    ],
    mode: 'subscription',
    success_url: successUrl,
    cancel_url: cancelUrl,
    metadata: {
      userId: userId.toString(),
      service: service, // Store service in metadata
    },
    subscription_data: {
      metadata: {
        service: service,
      },
    },
  });

  return session;
}
```

### 6. Update Webhook Handler

Edit `stripe/webhooks.js` to handle service-specific subscriptions:

```javascript
// In webhook handler
if (event.type === 'checkout.session.completed') {
  const session = event.data.object;
  const userId = parseInt(session.metadata.userId);
  const service = session.metadata.service || 'alttext-ai';
  
  // Update user plan based on service
  const subscription = await stripe.subscriptions.retrieve(session.subscription);
  
  await prisma.user.update({
    where: { id: userId },
    data: {
      plan: determinePlanFromPriceId(session.line_items.data[0].price.id),
      service: service, // Update service if needed
      tokensRemaining: service === 'seo-ai-meta' ? 100 : 1000 // Service-specific limits
    }
  });
}
```

## Stripe Setup Required

### Create SEO AI Meta Products

1. Go to Stripe Dashboard → Products
2. Create two products:

   **Product 1: SEO AI Meta Pro**
   - Name: "SEO AI Meta Pro"
   - Description: "100 posts per month with GPT-4-turbo"
   - Price: £12.99/month (recurring)
   - Note the Price ID (starts with `price_`)

   **Product 2: SEO AI Meta Agency**
   - Name: "SEO AI Meta Agency"
   - Description: "1000 posts per month with GPT-4-turbo"
   - Price: £49.99/month (recurring)
   - Note the Price ID (starts with `price_`)

3. Update the `validPrices` array in `routes/billing.js` with the new Price IDs

## Environment Variables

No additional environment variables needed - the backend will use the same:
- `DATABASE_URL`
- `STRIPE_SECRET_KEY`
- `JWT_SECRET`
- `OPENAI_API_KEY`

## Testing the Connection

### 1. Test Authentication

```bash
# Register a user
curl -X POST https://alttext-ai-backend.onrender.com/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "service": "seo-ai-meta"
  }'

# Login
curl -X POST https://alttext-ai-backend.onrender.com/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "service": "seo-ai-meta"
  }'
```

### 2. Test Usage Endpoint

```bash
curl -X GET https://alttext-ai-backend.onrender.com/usage?service=seo-ai-meta \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 3. Test Billing Endpoint

```bash
curl -X GET https://alttext-ai-backend.onrender.com/billing/info \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Deployment Steps

1. **Update Database Schema**
   ```bash
   cd alttext-ai-backend-clone
   npx prisma migrate dev --name add_service_support
   npx prisma generate
   ```

2. **Update Routes**
   - Edit `routes/usage.js`
   - Edit `routes/billing.js`
   - Edit `auth/routes.js` (optional)
   - Edit `stripe/checkout.js`
   - Edit `stripe/webhooks.js`

3. **Create Stripe Products**
   - Create SEO AI Meta Pro and Agency products
   - Update Price IDs in `routes/billing.js`

4. **Deploy to Render**
   ```bash
   # Push changes to your repository
   git add .
   git commit -m "Add SEO AI Meta service support"
   git push origin main
   
   # Render will auto-deploy
   ```

5. **Test in WordPress Plugin**
   - Go to Posts > SEO AI Meta
   - Click "Login" button
   - Test registration/login
   - Verify usage tracking works
   - Test checkout flow

## Backward Compatibility

All changes are backward compatible:
- Default `service` is `'alttext-ai'` if not provided
- Existing AltText AI users continue to work
- New SEO AI Meta users get service-specific limits

## Support

If you encounter issues:
1. Check backend logs on Render
2. Verify database migrations ran successfully
3. Test API endpoints directly with curl
4. Check WordPress plugin debug logs

## Next Steps

After backend is updated:
1. ✅ Plugin can authenticate users
2. ✅ Usage tracking works per service
3. ✅ Billing/subscriptions work
4. ✅ Stripe webhooks handle both services
5. ✅ Email notifications work (if configured)

The plugin is ready to connect once the backend is updated!

