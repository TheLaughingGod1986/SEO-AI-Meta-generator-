# Changelog

All notable changes to SEO AI Meta Generator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release of SEO AI Meta Generator
- AI-powered meta title and description generation using GPT-4
- Support for GPT-4o-mini (Free plan) and GPT-4-turbo (Pro/Agency plans)
- Meta box in post editor with one-click generation
- Character counters for title (50-60 chars) and description (150-160 chars)
- Bulk generation page for processing multiple posts
- Usage tracking with monthly limits (Free: 10, Pro: 100, Agency: 1000)
- Dashboard with usage statistics and progress bars
- Settings page for API configuration and preferences
- Stripe subscription integration (Free/Pro/Agency plans)
- Upgrade modal with plan comparison
- Account management tab with billing portal integration
- Email notifications via Resend API:
  - Welcome email on first generation
  - Usage warning at 80% threshold
  - Monthly summary (optional)
- WordPress Plugin Boilerplate structure
- Internationalization support (.pot file)
- Test scripts for usage tracking, generation, and billing
- GDPR compliance documentation
- Comprehensive documentation (README, TESTING, DEPLOYMENT guides)

### Security
- Nonce verification on all AJAX requests
- Capability checks (`manage_seo_ai_meta`)
- Input sanitization for all user inputs
- Output escaping for all displayed data
- Secure API communication via HTTPS/JWT

### Technical
- Compatible with WordPress 5.8+
- Requires PHP 7.4+
- Uses WordPress Plugin Boilerplate architecture
- Follows WordPress coding standards
- No external dependencies (except API calls)

[1.0.0]: https://github.com/benjaminoats/seo-ai-meta-generator/releases/tag/1.0.0

