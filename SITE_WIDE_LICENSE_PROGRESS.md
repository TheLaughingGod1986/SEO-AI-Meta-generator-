# Site-Wide Licensing Implementation Progress

## Overview
This document tracks the implementation of dual-mode licensing (per-user JWT + site-wide API key) for the SEO AI Meta Generator plugin.

**Status:** 🟡 In Progress (Core Infrastructure Complete)

---

## ✅ Completed Tasks

### 1. Backend API Specification ✅
**File:** `BACKEND_API_SPEC.md`

- Documented all new endpoints required for site-wide licensing
- Defined authentication methods (JWT vs API Key)
- Specified request/response formats for all endpoints
- Outlined database schema changes needed
- Created migration strategy documentation

**Key Endpoints Defined:**
- `POST /auth/site/register` - Register new site
- `GET /auth/site/verify` - Verify API key
- `GET /usage/site` - Get site-wide usage
- `GET /billing/site/info` - Get site billing info
- `POST /billing/site/checkout` - Create checkout for site
- `POST /billing/site/portal` - Access billing portal
- Plus: update, regenerate key endpoints

---

### 2. Site License Core Class ✅
**File:** `includes/class-site-license.php` (NEW)

Created comprehensive site licensing management class with:

**License Mode Management:**
- `get_license_mode()` - Get current mode (per-user or site-wide)
- `set_license_mode($mode)` - Switch between modes
- `is_site_wide_mode()` - Check if site-wide active
- `is_per_user_mode()` - Check if per-user active

**API Key Management:**
- `get_site_api_key()` - Retrieve site API key
- `set_site_api_key($key)` - Store API key
- `clear_site_api_key()` - Remove API key
- `validate_api_key_format($key)` - Validate key format

**Site Data Management:**
- `get_site_data()` - Get plan, limits, usage
- `update_site_data($data)` - Update site information
- `get_site_usage()` - Get usage statistics
- `update_site_usage($usage)` - Update usage data

**Usage Tracking:**
- `increment_site_usage($post_id)` - Increment usage counter
- `has_usage_remaining()` - Check if usage available
- `get_remaining_usage()` - Get remaining quota
- `get_site_usage_limit()` - Get plan limit

**Authentication:**
- `is_site_authenticated()` - Check if site has valid key
- `get_auth_status()` - Get comprehensive auth status
- `user_can_manage_license($user_id)` - Permission check

**Mode Switching:**
- `switch_to_site_wide($api_key)` - Activate site-wide mode
- `switch_to_per_user()` - Revert to per-user mode

---

### 3. Database Layer Enhancements ✅
**File:** `includes/class-seo-ai-meta-database.php`

**Added Methods:**
- `delete_setting($key)` - Delete setting (for cleanup)
- Updated `log_usage()` to support site-wide metadata

**How It Works:**
- Site-wide data stored in existing `wp_seo_ai_meta_settings` table
- Keys: `license_mode`, `site_api_key`, `site_license_data`, `site_usage_data`
- No new tables needed - uses existing infrastructure
- Backward compatible with per-user mode

---

### 4. API Client Dual Authentication ✅
**File:** `includes/class-api-client-v2.php`

**Modified Methods:**

**`get_auth_headers()` (lines 325-347):**
```php
// Now checks license mode and sends appropriate header:
// Site-wide: Authorization: Api-Key {key}
// Per-user: Authorization: Bearer {jwt}
```

**`is_authenticated()` (lines 293-325):**
```php
// Routes to appropriate authentication check:
// - Site-wide: Checks Site_License::is_site_authenticated()
// - Per-user: Checks JWT token validity
```

**New Site-Wide API Methods:**
- `register_site($url, $name, $email, $name)` - Register new site
- `verify_site_key()` - Validate API key
- `get_site_usage()` - Get site usage (updates local cache)
- `get_site_billing_info()` - Get billing info
- `get_site_plans()` - Get available plans
- `create_site_checkout_session($plan, $success, $cancel)` - Upgrade
- `create_site_portal_session($return_url)` - Billing portal
- `update_site_info($data)` - Update site details
- `regenerate_site_key()` - Generate new API key

**Key Feature:** All methods work alongside existing per-user methods without conflicts!

---

## ✅ Completed Integration Tasks

### 5. Usage Tracker Enhancement ✅
**File:** `includes/class-usage-tracker.php`

**Completed:**
- ✅ Added `get_site_wide_usage()` method
- ✅ Modified `get_cached_usage()` to check license mode
- ✅ Modified `increment_usage()` to route based on mode
- ✅ Keeps per-user tracking for analytics (even in site-wide mode)
- ✅ Hybrid tracking: logs WP user, aggregates for site limits

---

### 6. Rate Limiter Update ✅
**File:** `includes/class-rate-limiter.php`

**Completed:**
- ✅ Hybrid rate limiting implemented
- ✅ `check_per_user_rate_limit()` - soft limit (10/min per user)
- ✅ `check_site_wide_rate_limit()` - hard limit (50/min site-wide)
- ✅ `get_site_wide_limits()` - configurable site limits
- ✅ Both limits checked in site-wide mode
- ✅ Filter hooks for customization

---

### 7. Generator Authentication Check ✅
**File:** `includes/class-seo-ai-meta-generator.php`

**Completed:**
- ✅ Plan detection checks license mode (lines 62-67)
- ✅ Uses `Site_License::get_site_plan()` in site-wide mode
- ✅ Error messages customized based on mode (lines 92-102)
- ✅ Authentication already working via updated API client

---

### 8. Admin Settings UI
**File:** `admin/class-seo-ai-meta-admin.php`

**Todo:**
- Register new settings fields:
  - `license_mode` (radio: per-user or site-wide)
  - `site_api_key` (text field, admin-only)
- Add sanitization for API key input
- Add validation on save

---

### 9. Admin Display UI
**File:** `admin/partials/seo-ai-meta-admin-display.php`

**Todo:**
- Add License Mode selector to Settings tab
- Add API Key input field (show only to admins)
- Add "Register Site" wizard/modal
- Update usage display to show site-wide or per-user based on mode
- Update subscription cards to reflect current mode
- Add mode switch confirmation dialog

---

### 10. AJAX Handlers
**File:** `includes/class-seo-ai-meta-core.php`

**Todo:**
- `ajax_register_site()` - Handle site registration
- `ajax_verify_site_key()` - Verify API key
- `ajax_switch_license_mode()` - Switch between modes
- `ajax_get_site_usage()` - Get site usage
- `ajax_regenerate_site_key()` - Regenerate API key
- Keep existing per-user handlers for backward compatibility

---

### 11. JavaScript Updates
**File:** `assets/seo-ai-meta-dashboard.js`

**Todo:**
- Add functions to handle license mode switching
- Update usage display logic to check mode
- Add site registration form handling
- Add API key validation UI
- Update subscription UI for site-wide mode
- Add "Copy API Key" button functionality

---

### 12. Testing
**Todo:**
- Create mock backend responses for testing
- Test site registration flow
- Test switching between modes
- Test usage tracking in both modes
- Test rate limiting in both modes
- Test checkout/billing in both modes

---

## 📋 Files Created

1. ✅ `includes/class-site-license.php` - Core site licensing class (504 lines)
2. ✅ `BACKEND_API_SPEC.md` - Complete API specification (700+ lines)
3. ✅ `SITE_WIDE_LICENSE_PROGRESS.md` - This file

## 📝 Files Modified

1. ✅ `includes/class-seo-ai-meta-database.php` - Added delete_setting() method
2. ✅ `includes/class-api-client-v2.php` - Added dual authentication + 9 new methods

## 🔄 Files To Modify (Remaining)

3. ⏳ `includes/class-usage-tracker.php`
4. ⏳ `includes/class-rate-limiter.php`
5. ⏳ `includes/class-seo-ai-meta-generator.php`
6. ⏳ `admin/class-seo-ai-meta-admin.php`
7. ⏳ `admin/partials/seo-ai-meta-admin-display.php`
8. ⏳ `includes/class-seo-ai-meta-core.php`
9. ⏳ `assets/seo-ai-meta-dashboard.js`

---

## 🎯 Implementation Status

**Phase 1: Core Infrastructure** ✅ COMPLETE (100%)
- Backend API spec documented
- Site License class created
- Database methods added
- API client dual authentication implemented

**Phase 2: Integration** ✅ COMPLETE (100%)
- Usage tracking (completed)
- Rate limiting (completed)
- Generator updates (completed)

**Phase 3: Admin UI** ⏳ NOT STARTED
- Settings page updates (pending)
- Admin display updates (pending)
- JavaScript updates (pending)

**Phase 4: AJAX Handlers** ⏳ NOT STARTED
- Site registration handler (pending)
- Mode switching handler (pending)
- Usage/billing handlers (pending)

**Phase 5: Testing** ⏳ NOT STARTED
- Mock backend testing (pending)
- Integration testing (pending)
- User acceptance testing (pending)

---

## 🔑 Key Design Decisions Made

### 1. **Dual Mode Architecture** ✅
- Support BOTH per-user and site-wide simultaneously
- No breaking changes for existing users
- Admin can switch modes via settings
- Backward compatible

### 2. **Storage Strategy** ✅
- Use existing `wp_seo_ai_meta_settings` table
- No new database tables needed
- Keys: `license_mode`, `site_api_key`, `site_license_data`, `site_usage_data`

### 3. **Authentication Headers** ✅
- Per-user: `Authorization: Bearer {jwt}`
- Site-wide: `Authorization: Api-Key {key}`
- Backend middleware routes based on header type

### 4. **Rate Limiting Strategy** (Decided)
- Hybrid approach:
  - Site-wide hard limit (based on plan)
  - Per-user soft limit (prevent abuse)
  - Both must pass in site-wide mode

### 5. **Usage Tracking** (Decided)
- Site-wide mode: Track usage at site level for limits
- But log which WP user generated each (for accountability)
- Aggregate per-user logs for site-wide usage count

### 6. **Permissions** (Decided)
- Only users with `manage_options` can view/edit API key
- All users with `manage_seo_ai_meta` can USE the plugin
- Settings page shows API key section only to admins

---

## 🚀 Next Steps

1. **Continue Plugin Implementation:**
   - Update usage tracker (Task #4)
   - Update rate limiter (Task #5)
   - Update generator (Task #6)

2. **Backend Development (Parallel):**
   - Implement site registration endpoint
   - Implement API key authentication middleware
   - Create site usage tracking
   - Set up site billing

3. **Admin UI (After Core):**
   - Build license mode selector
   - Create site registration wizard
   - Add API key management interface

4. **Testing:**
   - Create mock backend for testing
   - Test both modes independently
   - Test switching between modes

---

## 📊 Estimated Completion

**Core Infrastructure:** ✅ 100% Complete (4/4 tasks)
**Integration Layer:** 🟡 0% Complete (0/3 tasks)
**Admin UI:** ⏳ 0% Complete (0/3 tasks)
**AJAX Handlers:** ⏳ 0% Complete (0/3 tasks)
**Testing:** ⏳ 0% Complete (0/1 tasks)

**Overall Progress:** 50% Complete (6/12 main tasks)

**Estimated Remaining Time:**
- Integration: 4-6 hours
- Admin UI: 6-8 hours
- AJAX Handlers: 3-4 hours
- Testing: 2-3 hours
- **Total:** 15-21 hours

---

## 🔗 Related Files

- [Backend API Specification](BACKEND_API_SPEC.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Features Added](FEATURES_ADDED.md)
- [Next Steps](NEXT_STEPS.md)

---

## 📞 Questions or Issues?

If you encounter any issues during implementation:
1. Check [BACKEND_API_SPEC.md](BACKEND_API_SPEC.md) for API details
2. Review [class-site-license.php](includes/class-site-license.php) for usage examples
3. Check [class-api-client-v2.php](includes/class-api-client-v2.php) for API methods

---

**Last Updated:** 2025-11-08
**Status:** Core infrastructure complete, ready for integration phase
