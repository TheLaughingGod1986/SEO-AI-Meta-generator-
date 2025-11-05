# Testing Guide for SEO AI Meta Generator

## Pre-Deployment Checklist

### 1. Plugin Activation
- [ ] Plugin activates without errors on PHP 7.4+
- [ ] Plugin activates without errors on PHP 8.0+
- [ ] Plugin activates without errors on PHP 8.1+
- [ ] Plugin activates without errors on PHP 8.2+
- [ ] Plugin activates without errors on PHP 8.3+
- [ ] No PHP warnings or notices in error log

### 2. WordPress Compatibility
- [ ] Test on WordPress 5.8
- [ ] Test on WordPress 6.0
- [ ] Test on WordPress 6.5
- [ ] Test on WordPress 6.8
- [ ] Plugin deactivates cleanly
- [ ] No data loss on deactivation

### 3. Core Functionality
- [ ] Meta box appears in post editor
- [ ] Generate button works
- [ ] Generated meta tags are saved
- [ ] Character counters work correctly
- [ ] Meta tags output in wp_head
- [ ] Bulk generate page loads
- [ ] Bulk generation processes posts correctly
- [ ] Progress bars update during bulk generation

### 4. OpenAI Integration
- [ ] Generation works with OpenAI API key
- [ ] Generation works with Render credentials
- [ ] Error handling for invalid API key
- [ ] Error handling for rate limits
- [ ] GPT-4o-mini works (Free plan)
- [ ] GPT-4-turbo works (Pro/Agency plans)
- [ ] Generated titles are 50-60 characters
- [ ] Generated descriptions are 150-160 characters

### 5. Usage Tracking
- [ ] Usage increments correctly
- [ ] Limits are enforced
- [ ] Monthly reset works
- [ ] Usage stats display correctly
- [ ] Progress bars show accurate percentages
- [ ] Upgrade prompts appear at 80% usage

### 6. Billing Integration
- [ ] Stripe checkout flow works
- [ ] Price IDs are correct
- [ ] Subscription info loads
- [ ] Billing portal opens correctly
- [ ] Plan upgrades work
- [ ] Free plan limits are enforced

### 7. User Interface
- [ ] Dashboard loads without errors
- [ ] All tabs work (Dashboard, Settings, Account)
- [ ] Settings save correctly
- [ ] Upgrade modal displays
- [ ] Upgrade modal closes correctly
- [ ] Responsive design works on mobile
- [ ] All buttons are functional

### 8. Security
- [ ] Nonces are verified
- [ ] Capabilities are checked
- [ ] Input sanitization works
- [ ] Output escaping works
- [ ] AJAX requests are secure
- [ ] API keys are not exposed

### 9. GDPR Compliance
- [ ] No data stored externally without consent
- [ ] User data deletion works on uninstall
- [ ] Privacy policy info available
- [ ] Data export works
- [ ] Email opt-out works (if implemented)

### 10. Error Handling
- [ ] Network errors handled gracefully
- [ ] API errors show user-friendly messages
- [ ] Invalid post IDs handled
- [ ] Missing data handled
- [ ] Timeout errors handled

## Testing Commands

### Run PHPCS
```bash
phpcs --standard=WordPress seo-ai-meta-generator/
```

### Run Plugin Check
Use the WordPress Plugin Check plugin or WP-CLI:
```bash
wp plugin check seo-ai-meta-generator
```

### Test Functions (in WordPress admin)
Add to functions.php temporarily:
```php
// Test usage tracking
if ( defined( 'WP_CLI' ) || ( is_admin() && current_user_can( 'manage_options' ) ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'seo-ai-meta-generator/tests/test-usage-tracking.php';
    test_seo_ai_meta_usage_tracking();
}
```

## Known Limitations

1. **OpenAI API Dependency:** Plugin requires working OpenAI API connection
2. **Backend API Dependency:** Subscription features require backend API
3. **PHP 7.4+:** Requires PHP 7.4 or higher
4. **WordPress 5.8+:** Requires WordPress 5.8 or higher

## Browser Compatibility

Tested on:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance Testing

- [ ] Dashboard loads in < 2 seconds
- [ ] Meta generation completes in < 5 seconds
- [ ] Bulk generation processes 10 posts in < 30 seconds
- [ ] No memory leaks during bulk processing
- [ ] Database queries are optimized

## Accessibility

- [ ] Keyboard navigation works
- [ ] Screen readers can access all content
- [ ] Color contrast meets WCAG standards
- [ ] Focus indicators are visible
- [ ] ARIA labels are present

