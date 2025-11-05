# Installation Guide

## Quick Start

### 1. Install Plugin

**Option A: WordPress Admin (Recommended)**
1. Download `seo-ai-meta-generator.zip`
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin"
4. Choose the zip file
5. Click "Install Now"
6. Activate the plugin

**Option B: Manual Installation**
1. Extract `seo-ai-meta-generator.zip`
2. Upload the `seo-ai-meta-generator` folder to `/wp-content/plugins/`
3. Activate via WordPress Admin > Plugins

### 2. Initial Setup

1. Navigate to **Posts > SEO AI Meta**
2. Go to **Settings** tab
3. (Optional) Enter your OpenAI API key
   - Leave empty to use Render credentials
   - Or get your key from: https://platform.openai.com/api-keys
4. Configure default settings:
   - Default model (gpt-4o-mini or gpt-4-turbo)
   - Title max length (default: 60)
   - Description max length (default: 160)
5. Click **Save Changes**

### 3. Generate Your First Meta Tag

1. Edit any post in WordPress
2. Scroll to **SEO AI Meta Generator** meta box
3. Click **Generate Meta**
4. Review generated title and description
5. Edit if needed (character counts update automatically)
6. Save your post

### 4. Bulk Generate (Optional)

1. Go to **Posts > Bulk Generate Meta**
2. Select posts without meta tags
3. Click **Generate Meta for Selected Posts**
4. Monitor progress in real-time

## Configuration Options

### OpenAI API Key

**Option 1: Render Credentials (Recommended)**
- No configuration needed
- Plugin uses credentials from Render environment
- Set `OPENAI_API_KEY` in Render dashboard

**Option 2: WordPress Settings**
- Enter API key in Settings tab
- Stored securely in WordPress database
- Overrides Render credentials if set

### Subscription Plans

**Free Plan** (10 posts/month)
- Automatic on activation
- GPT-4o-mini model
- Basic features

**Pro Plan** (£12.99/month)
- 100 posts/month
- GPT-4-turbo model
- Bulk generation
- Priority support

**Agency Plan** (£49.99/month)
- 1000 posts/month
- GPT-4-turbo model
- All features included

### Upgrade Flow

1. Click **Upgrade** button when limit reached
2. Select plan in upgrade modal
3. Complete checkout via Stripe
4. Subscription activates automatically
5. Limits reset monthly

## Troubleshooting

### Plugin Won't Activate

**Error: "Requires PHP 7.4 or higher"**
- Update your PHP version
- Contact your hosting provider

**Error: "Plugin activation failed"**
- Check WordPress error log
- Verify file permissions
- Re-upload plugin files

### Meta Tags Not Generating

**Error: "OpenAI API key is not configured"**
- Enter API key in Settings
- Or verify Render credentials are set
- Check API key is valid

**Error: "Network error"**
- Check internet connection
- Verify OpenAI API is accessible
- Check firewall settings

**Error: "Limit reached"**
- Upgrade your plan
- Wait for monthly reset
- Check usage in dashboard

### Generated Meta Looks Wrong

**Issue: Meta doesn't match content**
- Ensure post has clear title and content
- Try regenerating
- Edit manually if needed

**Issue: Too long/short**
- Check character limits in Settings
- Regenerate with different settings
- Edit manually

### Settings Not Saving

**Issue: Changes don't persist**
- Check file permissions
- Clear browser cache
- Verify database connection

## Support

For additional help:
- Check FAQ in `readme.txt`
- Review `TESTING.md` for known issues
- Submit support request on WordPress.org forum

## Next Steps

1. Generate meta for your existing posts
2. Monitor usage in dashboard
3. Upgrade plan if needed
4. Enjoy improved SEO rankings!

