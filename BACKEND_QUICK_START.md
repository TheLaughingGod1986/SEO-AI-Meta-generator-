# Backend Quick Start Guide for Site-Wide Licensing

This guide helps you quickly implement the backend API changes needed for site-wide licensing.

---

## 🎯 Goal

Add site-wide licensing support to your backend **without breaking** existing per-user JWT authentication.

---

## 📦 What You Need to Build

### 1. Database Tables (3 new tables)

```sql
-- Table: site_licenses
CREATE TABLE site_licenses (
  id VARCHAR(255) PRIMARY KEY,
  api_key VARCHAR(255) UNIQUE NOT NULL,
  site_url VARCHAR(500) NOT NULL,
  site_name VARCHAR(255),
  admin_email VARCHAR(255) NOT NULL,
  admin_name VARCHAR(255),
  service VARCHAR(50) NOT NULL DEFAULT 'seo-ai-meta',
  plan VARCHAR(50) DEFAULT 'free',
  usage_limit INT DEFAULT 10,
  stripe_customer_id VARCHAR(255),
  stripe_subscription_id VARCHAR(255),
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_api_key (api_key),
  INDEX idx_site_url (site_url),
  INDEX idx_service (service)
);

-- Table: site_usage
CREATE TABLE site_usage (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_id VARCHAR(255) NOT NULL,
  service VARCHAR(50) NOT NULL,
  usage_count INT DEFAULT 0,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  reset_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (site_id) REFERENCES site_licenses(id) ON DELETE CASCADE,
  INDEX idx_site_service (site_id, service),
  INDEX idx_period (period_start, period_end)
);

-- Table: site_usage_log
CREATE TABLE site_usage_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_id VARCHAR(255) NOT NULL,
  service VARCHAR(50) NOT NULL,
  wp_user_id INT,
  post_id INT,
  model VARCHAR(50),
  metadata JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (site_id) REFERENCES site_licenses(id) ON DELETE CASCADE,
  INDEX idx_site_date (site_id, created_at),
  INDEX idx_wp_user (wp_user_id)
);
```

---

### 2. Authentication Middleware (Critical!)

Your backend needs to support **TWO authentication methods** simultaneously:

```javascript
// Example Express.js middleware
function authenticate(req, res, next) {
  const authHeader = req.headers.authorization;
  const apiKeyHeader = req.headers['x-api-key'];

  // Check for API Key authentication (NEW - for site-wide)
  if (apiKeyHeader || authHeader?.startsWith('Api-Key ')) {
    const apiKey = apiKeyHeader || authHeader.replace('Api-Key ', '');

    const site = await db.query(
      'SELECT * FROM site_licenses WHERE api_key = ? AND active = TRUE',
      [apiKey]
    );

    if (!site) {
      return res.status(401).json({
        success: false,
        error: 'Invalid API key',
        code: 'INVALID_API_KEY'
      });
    }

    req.site = site;
    req.authType = 'site';
    return next();
  }

  // Check for JWT authentication (EXISTING - for per-user)
  if (authHeader?.startsWith('Bearer ')) {
    const token = authHeader.replace('Bearer ', '');

    try {
      const decoded = jwt.verify(token, process.env.JWT_SECRET);
      req.user = decoded;
      req.authType = 'user';
      return next();
    } catch (err) {
      return res.status(401).json({
        success: false,
        error: 'Invalid token'
      });
    }
  }

  return res.status(401).json({
    success: false,
    error: 'No authentication provided'
  });
}
```

---

### 3. New Endpoints (8 endpoints)

#### A. Site Registration
```javascript
POST /auth/site/register

Request Body:
{
  "site_url": "https://example.com",
  "site_name": "My WordPress Site",
  "admin_email": "admin@example.com",
  "admin_name": "John Doe",
  "service": "seo-ai-meta"
}

Response (201):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "api_key": "STRIPE_API_KEY_HERE",
    "site_url": "https://example.com",
    "site_name": "My WordPress Site",
    "plan": "free",
    "usage_limit": 10,
    "usage_count": 0,
    "reset_date": "2025-12-01",
    "created_at": "2025-11-08T10:00:00Z"
  }
}

Implementation:
1. Generate unique site_id (e.g., site_ + random string)
2. Generate API key (e.g., STRIPE_API_KEY_HERE + random 32 chars)
3. Store hashed API key in database (for security)
4. Create Stripe customer (optional at this stage)
5. Set plan = 'free', usage_limit = 10
6. Return API key (only time it's shown!)
```

#### B. Verify API Key
```javascript
GET /auth/site/verify
Headers: Authorization: Api-Key {api_key}

Response (200):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "site_url": "https://example.com",
    "site_name": "My WordPress Site",
    "plan": "pro",
    "active": true,
    "created_at": "2025-11-08T10:00:00Z"
  }
}

Implementation:
1. Middleware validates API key
2. Return site info from req.site
```

#### C. Get Site Usage
```javascript
GET /usage/site?service=seo-ai-meta
Headers: Authorization: Api-Key {api_key}

Response (200):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "service": "seo-ai-meta",
    "usage_count": 47,
    "usage_limit": 100,
    "reset_date": "2025-12-01",
    "period_start": "2025-11-01",
    "period_end": "2025-11-30"
  }
}

Implementation:
1. Get site from req.site
2. Query site_usage table for current period
3. If no usage record, create one
4. Check if reset needed (current date >= reset_date)
5. If reset needed: set usage_count = 0, update reset_date
6. Return usage data
```

#### D. Get Site Billing Info
```javascript
GET /billing/site/info?service=seo-ai-meta
Headers: Authorization: Api-Key {api_key}

Response (200):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "plan": "pro",
    "plan_name": "Pro Plan",
    "price": "$29/month",
    "stripe_subscription_id": "sub_xxxxx",
    "stripe_customer_id": "cus_xxxxx",
    "status": "active",
    "current_period_start": "2025-11-01",
    "current_period_end": "2025-12-01",
    "cancel_at_period_end": false,
    "usage_limit": 100,
    "features": [
      "100 meta tags per month",
      "Advanced AI models",
      "Priority support"
    ]
  }
}

Implementation:
1. Get site from req.site
2. If has stripe_subscription_id, fetch from Stripe
3. Return billing info
```

#### E. Create Site Checkout
```javascript
POST /billing/site/checkout
Headers: Authorization: Api-Key {api_key}

Request Body:
{
  "plan": "site_pro",
  "service": "seo-ai-meta",
  "success_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta&success=1",
  "cancel_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta&cancel=1"
}

Response (200):
{
  "success": true,
  "data": {
    "session_id": "cs_test_xxxxx",
    "checkout_url": "https://checkout.stripe.com/pay/cs_test_xxxxx"
  }
}

Implementation:
1. Get site from req.site
2. Get or create Stripe customer
3. Map plan to Stripe price ID
4. Create Stripe checkout session
5. Store site_id in metadata
6. Return checkout URL
```

#### F. Create Billing Portal
```javascript
POST /billing/site/portal
Headers: Authorization: Api-Key {api_key}

Request Body:
{
  "return_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta",
  "service": "seo-ai-meta"
}

Response (200):
{
  "success": true,
  "data": {
    "portal_url": "https://billing.stripe.com/session/xxxxx"
  }
}

Implementation:
1. Get site from req.site
2. Get Stripe customer ID
3. Create billing portal session
4. Return portal URL
```

#### G. Update Site Info
```javascript
PUT /auth/site/update
Headers: Authorization: Api-Key {api_key}

Request Body:
{
  "site_url": "https://newdomain.com",
  "site_name": "Updated Site Name",
  "admin_email": "newemail@example.com"
}

Response (200):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "site_url": "https://newdomain.com",
    "site_name": "Updated Site Name",
    "updated_at": "2025-11-08T12:00:00Z"
  }
}

Implementation:
1. Get site from req.site
2. Validate input
3. Update site_licenses table
4. Return updated data
```

#### H. Regenerate API Key
```javascript
POST /auth/site/regenerate-key
Headers: Authorization: Api-Key {current_api_key}

Request Body:
{
  "confirm": true
}

Response (200):
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "api_key": "STRIPE_API_KEY_HERE",
    "old_key_invalidated": true,
    "generated_at": "2025-11-08T12:00:00Z"
  },
  "message": "New API key generated. Update your plugin immediately."
}

Implementation:
1. Get site from req.site
2. Generate new API key
3. Hash and store new key
4. Invalidate old key
5. Return NEW key (only time it's shown!)
6. Consider: Keep old key valid for 24h grace period
```

---

### 4. Stripe Price IDs

You'll need to create new Stripe products/prices for site-wide plans:

```javascript
// Existing per-user prices (KEEP THESE)
price_user_free: null
price_user_pro: price_xxxxx ($29/month)
price_user_agency: price_yyyyy ($99/month)

// NEW site-wide prices (CREATE THESE)
price_site_free: null
price_site_pro: price_aaaaa ($29/month) // Or different pricing?
price_site_agency: price_bbbbb ($99/month)
```

**Decision:** Do site-wide licenses cost the same as per-user? Or more?

---

## 🔄 Stripe Webhook Updates

Update your Stripe webhook handler to support site-based subscriptions:

```javascript
async function handleStripeWebhook(event) {
  switch (event.type) {
    case 'checkout.session.completed':
      const session = event.data.object;

      // Check if this is a site-wide subscription
      if (session.metadata.site_id) {
        // Update site_licenses table
        await db.query(
          'UPDATE site_licenses SET plan = ?, stripe_customer_id = ?, stripe_subscription_id = ?, usage_limit = ? WHERE id = ?',
          [session.metadata.plan, session.customer, session.subscription, getPlanLimit(session.metadata.plan), session.metadata.site_id]
        );

        // Create usage record for new period
        await createSiteUsageRecord(session.metadata.site_id);
      } else {
        // Existing per-user handling
        // ...
      }
      break;

    case 'customer.subscription.updated':
      // Handle plan changes for both user and site
      break;

    case 'customer.subscription.deleted':
      // Handle cancellations for both user and site
      break;
  }
}
```

---

## 🧪 Testing Strategy

### Phase 1: Local Testing (No Stripe)
1. Create site with `POST /auth/site/register`
2. Verify key with `GET /auth/site/verify`
3. Check usage with `GET /usage/site`
4. Mock Stripe responses for checkout

### Phase 2: Stripe Testing
1. Use Stripe test mode
2. Create test checkout sessions
3. Trigger test webhooks
4. Verify subscription updates

### Phase 3: Plugin Integration
1. Plugin registers site
2. Plugin uses API key for requests
3. Usage increments properly
4. Checkout flow works

---

## 🚦 Implementation Checklist

### Database
- [ ] Create `site_licenses` table
- [ ] Create `site_usage` table
- [ ] Create `site_usage_log` table
- [ ] Add indexes for performance

### Authentication
- [ ] Implement API key authentication middleware
- [ ] Keep JWT authentication working
- [ ] Route to appropriate handler based on auth type

### Endpoints
- [ ] `POST /auth/site/register`
- [ ] `GET /auth/site/verify`
- [ ] `GET /usage/site`
- [ ] `GET /billing/site/info`
- [ ] `POST /billing/site/checkout`
- [ ] `POST /billing/site/portal`
- [ ] `PUT /auth/site/update`
- [ ] `POST /auth/site/regenerate-key`

### Stripe
- [ ] Create site-wide Stripe products/prices
- [ ] Update webhook handler for site subscriptions
- [ ] Test checkout flow
- [ ] Test billing portal

### Security
- [ ] Hash API keys in database (like passwords)
- [ ] Add rate limiting per API key
- [ ] Validate site_url format
- [ ] Sanitize inputs

---

## 📊 API Key Format

**Recommendation:**
```
Format: STRIPE_API_KEY_HERE + 32 random characters
Format: sk_test_ + 32 random characters  // For testing
```

**Example:**
```
STRIPE_API_KEY_HERE (replace with your actual Stripe API key)
```

**Generation:**
```javascript
function generateAPIKey(env = 'live') {
  const prefix = env === 'live' ? 'STRIPE_API_KEY_HERE' : 'sk_test_';
  const random = crypto.randomBytes(16).toString('hex');
  return prefix + random;
}
```

**Storage:**
```javascript
// NEVER store plain text API keys!
const hashedKey = await bcrypt.hash(apiKey, 10);
await db.query('INSERT INTO site_licenses (api_key, ...) VALUES (?, ...)', [hashedKey, ...]);
```

**Verification:**
```javascript
const site = await db.query('SELECT * FROM site_licenses WHERE site_id = ?', [siteId]);
const isValid = await bcrypt.compare(providedKey, site.api_key);
```

---

## 🎬 Quick Start Commands

### 1. Set Up Database
```bash
mysql -u root -p your_database < backend/migrations/create_site_tables.sql
```

### 2. Test Registration
```bash
curl -X POST http://localhost:3001/auth/site/register \
  -H "Content-Type: application/json" \
  -d '{
    "site_url": "https://test.local",
    "site_name": "Test Site",
    "admin_email": "test@test.local",
    "admin_name": "Test Admin",
    "service": "seo-ai-meta"
  }'
```

### 3. Test API Key
```bash
curl -X GET http://localhost:3001/auth/site/verify \
  -H "Authorization: Api-Key STRIPE_API_KEY_HERE"
```

### 4. Test Usage
```bash
curl -X GET "http://localhost:3001/usage/site?service=seo-ai-meta" \
  -H "Authorization: Api-Key STRIPE_API_KEY_HERE"
```

---

## 📞 Questions?

**Common Questions:**

**Q: Do I need to break existing users?**
A: No! Keep JWT authentication working. Site-wide is additive.

**Q: Should site-wide cost more than per-user?**
A: Your decision. Could be same price or premium.

**Q: What if someone abuses site-wide with 100 WP users?**
A: Usage tracking is site-wide. All users share the limit.

**Q: How do I migrate existing users to site-wide?**
A: Don't force it. Let them opt-in via plugin settings.

**Q: Should I hash API keys?**
A: YES! Treat them like passwords. Hash with bcrypt.

---

## 🔗 More Details

See [BACKEND_API_SPEC.md](BACKEND_API_SPEC.md) for complete API documentation.

---

**Last Updated:** 2025-11-08
**Status:** Ready for backend implementation
