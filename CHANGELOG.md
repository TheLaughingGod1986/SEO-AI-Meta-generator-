# Changelog

All notable changes to SEO AI Meta Generator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-XX

### Added
- **SEO Score Indicator** - Real-time SEO scoring (0-100) with visual feedback
- **SEO Preview** - Google-style search result preview with live updates
- **Copy to Clipboard** - One-click copy buttons for title and description
- **Keyboard Shortcuts** - Ctrl+G/Cmd+G to quickly generate meta tags
- **Undo Last Generation** - Restore previous meta tags with one click
- **Regenerate Button** - Quick access to regenerate existing meta tags
- **Bulk Optimize** - New tab to optimize existing meta tags in bulk
- **Meta Templates** - Variable system ({{title}}, {{date}}, {{category}}, etc.)
- **Export/Import** - CSV export and import functionality
- **Duplicate Detection** - Warns when duplicate meta titles are found
- **Improved Checkout UX** - Auto-redirect back to dashboard after opening Stripe

### Improved
- Checkout redirect page now automatically returns to dashboard after 2 seconds
- Better error handling and user feedback
- Live SEO score updates as user types
- Enhanced meta box UI with copy buttons and undo functionality
- Code cleanup: organized test files and documentation

### Changed
- Debug logging now only shows when WP_DEBUG is enabled
- Test files moved to `tests/temp/` folder
- Documentation organized into `docs/` folder

### Technical
- Added `class-meta-template-processor.php` for template handling
- Enhanced SEO validator with score calculation
- Improved duplicate detection with post links
- Better checkout redirect UX with spinner and auto-return

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

