# SEO AI Meta Generator - Backend Requirements

## Overview

The SEO AI Meta Generator plugin uses the same backend API as AltText AI (`https://alttext-ai-backend.onrender.com`). The backend needs to support **multi-service functionality** to handle both AltText AI and SEO AI Meta Generator.

---

## Required API Endpoints

### 1. Authentication Endpoints

All authentication endpoints should work the same as AltText AI, but need to support service identification.

#### `POST /auth/register`
**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "service": "seo-ai-meta" // Optional, defaults to "alttext-ai"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "jwt_token_here",
    "user": {
      "id": "user_id",
      "email": "user@example.com"
    }
  }
}
```

#### `POST /auth/login` ✅ (Already implemented)
**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "service": "seo-ai-meta" // Optional
}
```

**Response:** Same as register

#### `GET /auth/me` ✅ (Already implemented)
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "user_id",
      "email": "user@example.com",
      "service": "seo-ai-meta"
    }
  }
}
```

---

### 2. Usage Tracking Endpoints

#### `GET /usage` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request Parameters:**
- `service` (optional): `"seo-ai-meta"` or `"alttext-ai"` (default: detect from token)

**Response:**
```json
{
  "success": true,
  "data": {
    "usage": {
      "used": 5,
      "limit": 10,
      "remaining": 5,
      "plan": "free",
      "resetDate": "2025-12-01",
      "resetTimestamp": 1730332800
    }
  }
}
```

**Backend Requirements:**
- Track usage separately per service (AltText AI vs SEO AI Meta)
- Monthly reset logic (first day of next month)
- Plan limits:
  - **Free**: 10 posts/month
  - **Pro**: 100 posts/month
  - **Agency**: 1000 posts/month

---

### 3. Billing Endpoints

#### `GET /billing/info` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request Parameters:**
- `service` (optional): `"seo-ai-meta"` or `"alttext-ai"`

**Response:**
```json
{
  "success": true,
  "data": {
    "billing": {
      "plan": "pro",
      "status": "active",
      "nextBillingDate": "2025-12-01",
      "stripeCustomerId": "cus_xxx",
      "stripeSubscriptionId": "sub_xxx"
    }
  }
}
```

#### `GET /billing/plans` ⚠️ **NEEDS UPDATE**
**Request Parameters:**
- `service` (optional): `"seo-ai-meta"` or `"alttext-ai"`

**Response:**
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": "free",
        "name": "Free",
        "price": 0,
        "limit": 10,
        "features": ["10 posts per month"]
      },
      {
        "id": "pro",
        "name": "Pro",
        "price": 12.99,
        "priceId": "price_xxx", // Stripe Price ID
        "limit": 100,
        "features": ["100 posts per month", "GPT-4-turbo", "Priority support"]
      },
      {
        "id": "agency",
        "name": "Agency",
        "price": 49.99,
        "priceId": "price_yyy", // Stripe Price ID
        "limit": 1000,
        "features": ["1000 posts per month", "GPT-4-turbo", "Priority support", "White-label"]
      }
    ]
  }
}
```

#### `POST /billing/checkout` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "priceId": "price_xxx", // Stripe Price ID
  "successUrl": "https://example.com/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=success",
  "cancelUrl": "https://example.com/wp-admin/edit.php?page=seo-ai-meta-generator&checkout=cancel",
  "service": "seo-ai-meta" // Optional
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://checkout.stripe.com/xxx"
  }
}
```

**Backend Requirements:**
- Create Stripe checkout session
- Link subscription to user account
- Store service identifier with subscription
- Handle webhook for subscription updates

#### `POST /billing/portal` ✅ (Should work, but verify service support)
**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "returnUrl": "https://example.com/wp-admin/edit.php?page=seo-ai-meta-generator",
  "service": "seo-ai-meta" // Optional
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://billing.stripe.com/xxx"
  }
}
```

---

### 4. Email Endpoints

#### `POST /email/welcome` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "email": "user@example.com",
  "service": "seo-ai-meta",
  "template": "seo-ai-meta-welcome"
}
```

#### `POST /email/usage-warning` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "email": "user@example.com",
  "service": "seo-ai-meta",
  "usage": {
    "used": 8,
    "limit": 10,
    "percentage": 80
  },
  "template": "seo-ai-meta-usage-warning"
}
```

#### `POST /email/monthly-summary` ⚠️ **NEEDS UPDATE**
**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "email": "user@example.com",
  "service": "seo-ai-meta",
  "summary": {
    "postsGenerated": 25,
    "timeSaved": "2.5 hours"
  },
  "template": "seo-ai-meta-monthly-summary"
}
```

---

## Backend Implementation Checklist

### Database Schema Updates

- [ ] Add `service` field to users table (or service_users mapping table)
- [ ] Add `service` field to subscriptions table
- [ ] Add `service` field to usage tracking table
- [ ] Create separate usage tracking for `seo-ai-meta` service

### Service-Specific Configuration

- [ ] Add SEO AI Meta pricing plans to database/config:
  - Free: £0/month, 10 posts
  - Pro: £12.99/month, 100 posts (Stripe Price ID needed)
  - Agency: £49.99/month, 1000 posts (Stripe Price ID needed)
- [ ] Store Stripe Price IDs for SEO AI Meta plans
- [ ] Configure service-specific email templates

### API Updates Required

- [ ] Update `/usage` endpoint to support `service` parameter
- [ ] Update `/billing/info` to filter by service
- [ ] Update `/billing/plans` to return service-specific plans
- [ ] Update `/billing/checkout` to link subscription to service
- [ ] Update email endpoints to support service parameter
- [ ] Update authentication to store/identify service

### Stripe Integration

- [ ] Create Stripe Products for SEO AI Meta:
  - SEO AI Meta Pro (£12.99/month)
  - SEO AI Meta Agency (£49.99/month)
- [ ] Create Stripe Price IDs for each plan
- [ ] Update webhook handler to support service identification
- [ ] Update subscription creation to include service metadata

### Webhook Handling

- [ ] Update Stripe webhook handler to:
  - Identify service from subscription metadata
  - Update usage limits based on service
  - Handle subscription cancellations/updates per service

### Testing

- [ ] Test registration/login with `service: "seo-ai-meta"`
- [ ] Test usage tracking for SEO AI Meta separately
- [ ] Test billing endpoints with service parameter
- [ ] Test Stripe checkout flow for SEO AI Meta plans
- [ ] Test customer portal access for SEO AI Meta subscriptions
- [ ] Test email sending for SEO AI Meta service

---

## Stripe Price IDs Needed

You'll need to create Stripe products and get the Price IDs:

1. **SEO AI Meta Pro Plan**
   - Product: "SEO AI Meta Pro"
   - Price: £12.99/month (recurring)
   - Price ID: `price_xxx` (store this in backend config)

2. **SEO AI Meta Agency Plan**
   - Product: "SEO AI Meta Agency"
   - Price: £49.99/month (recurring)
   - Price ID: `price_yyy` (store this in backend config)

---

## Migration Path

If the backend already supports AltText AI:

1. **Phase 1: Add Service Support**
   - Add `service` field to all relevant tables
   - Update API endpoints to accept `service` parameter
   - Default to `alttext-ai` for backward compatibility

2. **Phase 2: Add SEO AI Meta Plans**
   - Create Stripe products and prices
   - Add SEO AI Meta plans to database/config

3. **Phase 3: Update Usage Tracking**
   - Modify usage tracking to be service-specific
   - Ensure monthly resets work per service

4. **Phase 4: Testing**
   - Test all endpoints with `service: "seo-ai-meta"`
   - Verify Stripe integration works
   - Test email sending

---

## Notes

- The plugin can work without authentication (free tier with local usage tracking)
- Authentication is optional but recommended for subscription management
- The backend should gracefully handle missing service parameter (default to existing behavior)
- Consider rate limiting per service to prevent abuse

---

## Quick Reference: Expected Response Formats

All endpoints should return:
```json
{
  "success": true|false,
  "data": { ... },
  "error": "error message" // if success is false
}
```

Status codes:
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Server Error

