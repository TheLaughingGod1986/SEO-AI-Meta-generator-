# GDPR Compliance Documentation

## Overview

SEO AI Meta Generator is designed with GDPR (General Data Protection Regulation) compliance in mind.

## Data Collection

### What Data We Store

1. **User Meta Data:**
   - `seo_ai_meta_plan` - User's subscription plan (free/pro/agency)
   - `seo_ai_meta_usage_count` - Number of posts generated this month
   - `seo_ai_meta_reset_date` - Date when usage resets

2. **Post Meta Data:**
   - `_seo_ai_meta_title` - Generated meta title
   - `_seo_ai_meta_description` - Generated meta description
   - `_seo_ai_meta_generated_at` - Generation timestamp
   - `_seo_ai_meta_model` - AI model used

3. **WordPress Options:**
   - `seo_ai_meta_settings` - Plugin settings (API keys, preferences)

4. **API Communication:**
   - JWT tokens for authentication
   - Usage statistics sent to backend API

### What Data We DON'T Store

- User passwords (handled by backend)
- Post content (processed in real-time, not stored externally)
- Personal information beyond email (collected by backend during registration)

## Data Location

All data is stored in your WordPress database:
- User meta: `wp_usermeta` table
- Post meta: `wp_postmeta` table
- Settings: `wp_options` table

**No data is stored outside your WordPress installation** without explicit user consent.

## API Communication

### OpenAI API

When generating meta tags, the plugin sends:
- Post title
- Post excerpt
- Content sample (first 200 words)
- Focus keywords (if available from SEO plugins)

This data is sent to OpenAI's API for processing. OpenAI's privacy policy applies to this data.

### Backend API (AltText AI Backend)

The plugin communicates with a backend service for:
- User authentication (JWT tokens)
- Usage tracking
- Subscription management (Stripe)
- Email notifications (Resend)

All communication is encrypted via HTTPS.

## User Rights

Users have the right to:

1. **Access their data:** All user meta can be viewed in WordPress admin
2. **Export their data:** WordPress export tools can export all post meta
3. **Delete their data:** Plugin uninstall removes all plugin-related data
4. **Opt-out of emails:** Users can disable email notifications in preferences

## Data Deletion

When the plugin is uninstalled:
- All user meta (`seo_ai_meta_*`) is deleted
- All post meta (`_seo_ai_meta_*`) is deleted
- All plugin settings are removed
- Transients are cleared

Users can manually delete their generated meta tags by removing post meta in WordPress admin.

## Consent

- Users consent to data processing when they activate the plugin
- API communication requires active use of generation features
- Email notifications can be opted out

## Third-Party Services

### OpenAI
- Privacy Policy: https://openai.com/privacy
- Data is processed according to OpenAI's terms

### Stripe
- Privacy Policy: https://stripe.com/privacy
- Payment data handled by Stripe (not stored by plugin)

### Resend
- Privacy Policy: https://resend.com/privacy
- Email delivery service

## Recommendations for Site Owners

1. **Privacy Policy:** Add information about AI processing in your privacy policy
2. **User Consent:** Consider adding explicit consent for AI-generated content
3. **Data Export:** Use WordPress export tools for GDPR data requests
4. **Data Retention:** Consider retention policies for generated meta tags

## Contact

For GDPR-related inquiries, contact the plugin author or submit an issue on the plugin's support forum.

