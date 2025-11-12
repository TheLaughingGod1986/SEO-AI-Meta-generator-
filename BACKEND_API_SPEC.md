# Backend API Specification for Site-Wide Licensing

## Overview

This document specifies the new backend API endpoints required to support site-wide licensing alongside the existing per-user JWT authentication.

**Backend Base URL:** `https://alttext-ai-backend.onrender.com`

---

## Authentication Methods

The backend must support **TWO authentication methods simultaneously**:

### Method 1: Per-User JWT (EXISTING)
```
Authorization: Bearer {jwt_token}
```
- Used for per-user licensing mode
- Existing endpoints continue to work as-is

### Method 2: Site API Key (NEW)
```
Authorization: Api-Key {site_api_key}
```
OR
```
X-API-Key: {site_api_key}
```
- Used for site-wide licensing mode
- New endpoints or modified existing endpoints

---

## New Endpoints Required

### 1. Site Registration

**Endpoint:** `POST /auth/site/register`

**Purpose:** Register a new site and receive an API key

**Request Body:**
```json
{
  "site_url": "https://example.com",
  "site_name": "My WordPress Site",
  "admin_email": "admin@example.com",
  "admin_name": "John Doe",
  "service": "seo-ai-meta"
}
```

**Response (Success - 201):**
```json
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
```

**Response (Error - 400):**
```json
{
  "success": false,
  "error": "Site already registered",
  "code": "SITE_EXISTS"
}
```

---

### 2. Verify Site API Key

**Endpoint:** `GET /auth/site/verify`

**Purpose:** Validate a site API key and get site information

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Response (Success - 200):**
```json
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
```

**Response (Error - 401):**
```json
{
  "success": false,
  "error": "Invalid API key",
  "code": "INVALID_API_KEY"
}
```

---

### 3. Get Site Usage

**Endpoint:** `GET /usage/site`

**Purpose:** Get site-wide usage statistics

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Query Parameters:**
- `service=seo-ai-meta` (required)

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "service": "seo-ai-meta",
    "usage_count": 47,
    "usage_limit": 100,
    "reset_date": "2025-12-01",
    "period_start": "2025-11-01",
    "period_end": "2025-11-30",
    "usage_history": [
      {
        "date": "2025-11-08",
        "count": 5,
        "wp_user_id": 1
      }
    ]
  }
}
```

---

### 4. Get Site Billing Info

**Endpoint:** `GET /billing/site/info`

**Purpose:** Get site billing and subscription information

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Query Parameters:**
- `service=seo-ai-meta` (required)

**Response (Success - 200):**
```json
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
```

---

### 5. Get Available Plans (Site-Wide)

**Endpoint:** `GET /billing/site/plans`

**Purpose:** Get available plans for site-wide licensing

**Headers:**
```
Authorization: Api-Key {site_api_key}
```
OR (for unauthenticated access)
```
No authentication required
```

**Query Parameters:**
- `service=seo-ai-meta` (required)

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": "site_free",
        "name": "Free",
        "price": 0,
        "interval": "month",
        "features": [
          "10 meta tags per month",
          "Basic AI model",
          "Community support"
        ],
        "usage_limit": 10,
        "stripe_price_id": null
      },
      {
        "id": "site_pro",
        "name": "Pro",
        "price": 29,
        "interval": "month",
        "features": [
          "100 meta tags per month",
          "Advanced AI models",
          "Priority support",
          "Bulk generation"
        ],
        "usage_limit": 100,
        "stripe_price_id": "price_xxxxx"
      },
      {
        "id": "site_agency",
        "name": "Agency",
        "price": 99,
        "interval": "month",
        "features": [
          "1000 meta tags per month",
          "All AI models",
          "White-label option",
          "Dedicated support"
        ],
        "usage_limit": 1000,
        "stripe_price_id": "price_yyyyy"
      }
    ]
  }
}
```

---

### 6. Create Site Checkout Session

**Endpoint:** `POST /billing/site/checkout`

**Purpose:** Create a Stripe checkout session for site upgrade

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Request Body:**
```json
{
  "plan": "site_pro",
  "service": "seo-ai-meta",
  "success_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta&success=1",
  "cancel_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta&cancel=1"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "session_id": "cs_test_xxxxx",
    "checkout_url": "https://checkout.stripe.com/pay/cs_test_xxxxx"
  }
}
```

---

### 7. Get Site Billing Portal

**Endpoint:** `POST /billing/site/portal`

**Purpose:** Get Stripe customer portal URL for site

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Request Body:**
```json
{
  "service": "seo-ai-meta",
  "return_url": "https://example.com/wp-admin/admin.php?page=seo-ai-meta"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "portal_url": "https://billing.stripe.com/session/xxxxx"
  }
}
```

---

### 8. Update Site Information

**Endpoint:** `PUT /auth/site/update`

**Purpose:** Update site information

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Request Body:**
```json
{
  "site_url": "https://newdomain.com",
  "site_name": "Updated Site Name",
  "admin_email": "newemail@example.com"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "data": {
    "site_id": "site_abc123",
    "site_url": "https://newdomain.com",
    "site_name": "Updated Site Name",
    "updated_at": "2025-11-08T12:00:00Z"
  }
}
```

---

### 9. Regenerate Site API Key

**Endpoint:** `POST /auth/site/regenerate-key`

**Purpose:** Generate a new API key for the site (invalidates old key)

**Headers:**
```
Authorization: Api-Key {current_site_api_key}
```

**Request Body:**
```json
{
  "confirm": true
}
```

**Response (Success - 200):**
```json
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
```

---

### 10. Log Site Usage (Alternative Endpoint)

**Endpoint:** `POST /usage/site/log`

**Purpose:** Log a usage event (alternative to incrementing on generation)

**Headers:**
```
Authorization: Api-Key {site_api_key}
```

**Request Body:**
```json
{
  "service": "seo-ai-meta",
  "post_id": 123,
  "wp_user_id": 1,
  "model": "gpt-4",
  "metadata": {
    "post_type": "post",
    "post_title": "Example Post"
  }
}
```

**Response (Success - 201):**
```json
{
  "success": true,
  "data": {
    "usage_count": 48,
    "remaining": 52
  }
}
```

---

## Database Schema Changes

### New Table: `site_licenses`

```sql
CREATE TABLE site_licenses (
  id VARCHAR(255) PRIMARY KEY,
  api_key VARCHAR(255) UNIQUE NOT NULL,
  site_url VARCHAR(500) NOT NULL,
  site_name VARCHAR(255),
  admin_email VARCHAR(255) NOT NULL,
  admin_name VARCHAR(255),
  service VARCHAR(50) NOT NULL,
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
```

### New Table: `site_usage`

```sql
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
```

### New Table: `site_usage_log`

```sql
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

## Authentication Middleware Logic

The backend middleware should:

1. Check for `Authorization` header
2. If header starts with `Bearer `, use JWT authentication (existing)
3. If header starts with `Api-Key ` or check `X-API-Key` header, use site API key authentication
4. Validate the key against `site_licenses` table
5. Attach site information to the request context
6. Route to appropriate handler (user-based or site-based)

**Example Middleware (Pseudocode):**

```javascript
function authenticate(req, res, next) {
  const authHeader = req.headers.authorization;
  const apiKeyHeader = req.headers['x-api-key'];

  // Check for API Key authentication
  if (apiKeyHeader || authHeader?.startsWith('Api-Key ')) {
    const apiKey = apiKeyHeader || authHeader.replace('Api-Key ', '');
    const site = await db.query('SELECT * FROM site_licenses WHERE api_key = ? AND active = TRUE', [apiKey]);

    if (!site) {
      return res.status(401).json({ success: false, error: 'Invalid API key' });
    }

    req.site = site;
    req.authType = 'site';
    return next();
  }

  // Check for JWT authentication (existing)
  if (authHeader?.startsWith('Bearer ')) {
    // Existing JWT logic...
    req.authType = 'user';
    return next();
  }

  return res.status(401).json({ success: false, error: 'No authentication provided' });
}
```

---

## Migration Strategy

### For Existing Users

**Option 1: Automatic Migration**
- Backend creates a site license for each existing user
- Generate API key based on user's email
- Send email with new site API key
- Maintain JWT support for 3 months

**Option 2: Manual Migration**
- Add "Switch to Site License" button in user dashboard
- User clicks, backend generates site license
- User copies API key to plugin
- JWT remains valid until user switches

**Option 3: Gradual Migration**
- Support both authentication methods indefinitely
- Let each user decide which model to use
- No forced migration

---

## Testing Checklist

- [ ] Site registration endpoint works
- [ ] API key authentication validates correctly
- [ ] Invalid API keys return 401
- [ ] Site usage tracking increments properly
- [ ] Site usage resets monthly
- [ ] Billing info returns site-specific data
- [ ] Checkout creates site-level subscription
- [ ] Portal URL returns site-specific portal
- [ ] Both JWT and API Key auth work simultaneously
- [ ] Rate limiting works for site-wide mode
- [ ] Multiple WordPress users can use same API key

---

## Security Considerations

1. **API Key Storage:** Store hashed API keys in database (like passwords)
2. **Rate Limiting:** Implement rate limiting per API key
3. **Key Rotation:** Support regenerating keys without losing subscription
4. **HTTPS Only:** Enforce HTTPS for all API requests
5. **IP Whitelisting:** Optional IP restrictions for API keys
6. **Audit Logging:** Log all API key usage for security auditing

---

## Next Steps

1. **Backend Team:** Implement new endpoints and authentication middleware
2. **Database Team:** Create new tables and migration scripts
3. **Plugin Team:** Integrate new API endpoints (this document)
4. **QA Team:** Test both authentication methods
5. **DevOps Team:** Deploy to staging environment
6. **Support Team:** Prepare migration documentation for users

---

## Questions & Clarifications

**Q: Can one site have both per-user and site-wide licensing?**
A: No, each site chooses one mode. The plugin will have a setting to switch between modes.

**Q: What happens to existing Stripe subscriptions?**
A: They can be migrated to site-level subscriptions using Stripe's subscription update API.

**Q: How are usage limits enforced?**
A: Backend checks usage count before allowing generation. Returns 429 if limit exceeded.

**Q: Can multiple sites use the same API key?**
A: No, each API key is unique to one site. Usage is tracked per API key.

**Q: How often should the plugin verify the API key?**
A: Cache site data locally, verify every 24 hours or when usage fails.
