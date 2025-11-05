# Deployment Guide for SEO AI Meta Generator

## Pre-Deployment Checklist

1. [ ] All tests pass (see TESTING.md)
2. [ ] PHPCS compliance verified
3. [ ] Readme.txt is complete
4. [ ] Screenshots are ready (if submitting to WordPress.org)
5. [ ] Version number updated
6. [ ] Changelog updated
7. [ ] Translation files generated

## WordPress.org Submission

### Required Files

1. **readme.txt** - WordPress.org format (already created)
2. **Screenshots** - Place in `/assets/screenshots/`:
   - `screenshot-1.png` - Dashboard view (772x387px minimum)
   - `screenshot-2.png` - Meta box in post editor
   - `screenshot-3.png` - Bulk generate page
   - `screenshot-4.png` - Settings page
   - `screenshot-5.png` - Upgrade modal
   - `screenshot-6.png` - Account management

3. **Banner Image** - `banner-772x250.png`
4. **Icon Image** - `icon-256x256.png` (128x128 minimum)

### Submission Steps

1. Create zip file excluding:
   - `.git/`
   - `node_modules/`
   - `.env` files
   - `tests/` (optional)
   - `.phpcs.xml` (optional, but recommended to include)

2. Upload to WordPress.org SVN:
   ```bash
   svn checkout https://plugins.svn.wordpress.org/seo-ai-meta-generator/trunk
   # Copy plugin files
   svn add .
   svn commit -m "Initial release 1.0.0"
   ```

3. Tag release:
   ```bash
   svn copy trunk tags/1.0.0
   svn commit -m "Tag version 1.0.0"
   ```

## Self-Hosting Deployment

1. **Create Distribution Zip:**
   ```bash
   cd seo-ai-meta-generator
   zip -r ../seo-ai-meta-generator-1.0.0.zip . \
     -x "*.git*" \
     -x "*node_modules*" \
     -x "*.env*" \
     -x "*.DS_Store*" \
     -x "*tests/*"
   ```

2. **Verify Zip Contents:**
   - Main plugin file present
   - All directories included
   - No unnecessary files

## Version Bumping

Update version in:
1. `seo-ai-meta-generator.php` (header and constant)
2. `readme.txt` (Stable tag)
3. `README.md` (changelog)

## Release Notes Template

```
## Version 1.0.0 - Initial Release

### Added
- AI-powered meta title and description generation
- GPT-4o-mini and GPT-4-turbo support
- Bulk generation for multiple posts
- Usage tracking with monthly limits
- Stripe subscription integration (Free/Pro/Agency)
- Dashboard with usage statistics
- Settings page for configuration
- Account management with billing portal
- Upgrade modal with plan selection
- Email notifications (welcome, usage warnings)

### Security
- Nonce verification on all AJAX requests
- Capability checks for all admin functions
- Input sanitization and output escaping
- Secure API communication via HTTPS

### Compatibility
- WordPress 5.8+
- PHP 7.4+
- Compatible with all major SEO plugins
```

## Post-Deployment

1. [ ] Monitor error logs for issues
2. [ ] Check support forum for user feedback
3. [ ] Monitor usage statistics
4. [ ] Prepare hotfix if critical bugs found
5. [ ] Update documentation based on user questions

## Rollback Plan

If critical issues are found:

1. Tag current version as broken
2. Fix issues in development
3. Release hotfix version
4. Update readme.txt with known issues

