# SEO AI Meta Generator

AI-powered SEO meta title and description generator for WordPress. Uses GPT-4 to create optimized meta tags that boost search engine rankings.

## Features

- 🤖 **AI-Powered Generation** - GPT-4o-mini (Free) and GPT-4-turbo (Pro/Agency)
- 📊 **SEO Optimized** - Automatically optimizes for search engines
- 🔄 **Bulk Processing** - Generate meta tags for multiple posts at once
- 📈 **Usage Tracking** - Monitor monthly quota with visual progress bars
- 💳 **Subscription Plans** - Free (10), Pro (100), Agency (1000) posts/month
- 🔒 **Secure** - Stripe integration for payments, JWT authentication

## Installation

1. Upload the plugin to `/wp-content/plugins/seo-ai-meta-generator/`
2. Activate through the WordPress Plugins screen
3. Navigate to Posts > SEO AI Meta to access the dashboard
4. Configure settings (optional: add OpenAI API key)
5. Start generating meta tags!

## Requirements

- WordPress 5.8+
- PHP 7.4+
- OpenAI API key (optional - can use Render credentials)

## Usage

### Individual Post Generation

1. Edit any post in WordPress
2. Find the "SEO AI Meta Generator" meta box
3. Click "Generate Meta"
4. Review and edit the generated meta tags
5. Save your post

### Bulk Generation

1. Go to Posts > Bulk Generate Meta
2. Select posts without meta tags
3. Click "Generate Meta for Selected Posts"
4. Monitor progress in real-time

## Development

### File Structure

```
seo-ai-meta-generator/
├── admin/              # Admin interface
├── includes/           # Core classes
├── public/             # Frontend output
├── assets/             # CSS/JS files
├── templates/          # UI templates
├── languages/          # Translation files
└── tests/              # Test scripts
```

### Code Standards

Run PHPCS:

```bash
phpcs --standard=WordPress seo-ai-meta-generator/
```

### Testing

Use the test scripts in the `tests/` directory:

```php
test_seo_ai_meta_usage_tracking();
test_seo_ai_meta_generation($post_id);
test_seo_ai_meta_billing();
```

## Changelog

### 1.0.0
- Initial release
- AI-powered meta generation
- Bulk processing
- Stripe integration
- Usage tracking

## License

GPLv2 or later

## Credits

Built with:
- OpenAI GPT-4 API
- Stripe
- WordPress Plugin Boilerplate

## Support

For support, please visit: https://wordpress.org/support/plugin/seo-ai-meta-generator

