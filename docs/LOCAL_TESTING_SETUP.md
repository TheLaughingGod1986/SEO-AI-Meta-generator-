# Local Testing Setup Guide

## Quick Setup Options

### Option 1: Using Local by Flywheel (Recommended)

1. **Install Local by Flywheel:**
   - Download from: https://localwp.com/
   - Install and launch

2. **Create New Site:**
   - Click "Create a new site"
   - Name: "SEO AI Meta Test"
   - Choose PHP 7.4 or 8.0+
   - Choose WordPress 6.8
   - Click "Add Site"

3. **Install Plugin:**
   - Click "Open Site Shell" or navigate to site folder
   - Go to: `app/public/wp-content/plugins/`
   - Copy `seo-ai-meta-generator` folder here
   - Or upload zip via WordPress Admin > Plugins > Add New

4. **Activate Plugin:**
   - Go to WordPress Admin > Plugins
   - Activate "SEO AI Meta Generator"

5. **Configure:**
   - Set up database if needed
   - Add OpenAI API key in Settings (or use Render credentials)

### Option 2: Using Docker (Quick Setup)

Create `docker-compose.yml` in project root:

```yaml
version: '3.8'

services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wpuser
      WORDPRESS_DB_PASSWORD: wppass
      WORDPRESS_DB_NAME: wpdb
    volumes:
      - wordpress_data:/var/www/html
      - ./seo-ai-meta-generator:/var/www/html/wp-content/plugins/seo-ai-meta-generator
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wpdb
      MYSQL_USER: wpuser
      MYSQL_PASSWORD: wppass
      MYSQL_ROOT_PASSWORD: rootpass
    volumes:
      - db_data:/var/lib/mysql

volumes:
  wordpress_data:
  db_data:
```

**Start:**
```bash
docker-compose up -d
```

**Access:**
- WordPress: http://localhost:8080
- Install WordPress via web interface
- Plugin is already in `/wp-content/plugins/`

### Option 3: Manual WordPress Installation

1. **Download WordPress:**
   ```bash
   curl -O https://wordpress.org/latest.zip
   unzip latest.zip
   cd wordpress
   ```

2. **Set up Database:**
   - Create MySQL database: `seo_ai_meta_test`
   - User: `wpuser`, Password: `wppass`

3. **Configure wp-config.php:**
   ```php
   define('DB_NAME', 'seo_ai_meta_test');
   define('DB_USER', 'wpuser');
   define('DB_PASSWORD', 'wppass');
   define('DB_HOST', 'localhost');
   ```

4. **Copy Plugin:**
   ```bash
   cp -r ../seo-ai-meta-generator wp-content/plugins/
   ```

5. **Start PHP Server:**
   ```bash
   php -S localhost:8000
   ```

6. **Access:**
   - Go to http://localhost:8000
   - Complete WordPress installation
   - Activate plugin

## Testing Checklist

Once WordPress is running:

### 1. Activation Test
- [ ] Plugin activates without errors
- [ ] No PHP warnings/errors
- [ ] Admin menu appears: Posts > SEO AI Meta

### 2. Dashboard Test
- [ ] Dashboard loads
- [ ] Usage stats display (0/10 for free plan)
- [ ] Progress bar shows 0%
- [ ] Quick actions work

### 3. Meta Box Test
- [ ] Edit any post
- [ ] Meta box appears below editor
- [ ] Generate button is visible
- [ ] Character counters show 0/60 and 0/160

### 4. Generation Test
- [ ] Click "Generate Meta"
- [ ] AJAX request completes
- [ ] Meta tags populate fields
- [ ] Character counts update
- [ ] Success message appears

### 5. Storage Test
- [ ] Save post
- [ ] View page source (frontend)
- [ ] Meta tags appear in `<head>`
- [ ] Check postmeta in database:
  ```sql
  SELECT * FROM wp_postmeta WHERE meta_key LIKE '_seo_ai_meta_%';
  ```

### 6. Bulk Generate Test
- [ ] Go to Posts > Bulk Generate Meta
- [ ] Posts list loads
- [ ] Select posts
- [ ] Click "Generate Meta for Selected Posts"
- [ ] Progress bar updates
- [ ] Posts process successfully

### 7. Settings Test
- [ ] Go to Settings tab
- [ ] Enter OpenAI API key (optional)
- [ ] Change model selection
- [ ] Adjust character limits
- [ ] Save settings
- [ ] Settings persist

### 8. Usage Tracking Test
- [ ] Generate meta for 2-3 posts
- [ ] Check dashboard
- [ ] Usage count increases
- [ ] Progress bar updates
- [ ] Remaining count decreases

### 9. Limit Test
- [ ] Generate 10 posts (free limit)
- [ ] 11th generation should show limit error
- [ ] Upgrade prompt appears
- [ ] Upgrade modal opens

### 10. Frontend Test
- [ ] View post on frontend
- [ ] Inspect page source
- [ ] Verify meta tags in `<head>`:
  ```html
  <meta name="title" content="..." />
  <meta name="description" content="..." />
  ```

## Database Verification

Check plugin data in database:

```sql
-- User meta
SELECT * FROM wp_usermeta WHERE meta_key LIKE 'seo_ai_meta_%';

-- Post meta
SELECT * FROM wp_postmeta WHERE meta_key LIKE '_seo_ai_meta_%';

-- Options
SELECT * FROM wp_options WHERE option_name LIKE 'seo_ai_meta_%';
```

## Common Issues

### Issue: Plugin won't activate
**Solution:** Check PHP version (needs 7.4+)
```bash
php --version
```

### Issue: Meta not generating
**Solution:** 
- Check OpenAI API key in Settings
- Verify Render credentials if using
- Check WordPress debug log

### Issue: AJAX errors
**Solution:**
- Check browser console
- Verify nonce is being passed
- Check file permissions

### Issue: Styles not loading
**Solution:**
- Clear browser cache
- Check file paths in Network tab
- Verify assets folder exists

## Test Results Log

Create a test log:

```
Date: [DATE]
WordPress Version: [VERSION]
PHP Version: [VERSION]

Tests Completed:
- [ ] Activation
- [ ] Dashboard
- [ ] Meta box
- [ ] Generation
- [ ] Bulk processing
- [ ] Settings
- [ ] Usage tracking
- [ ] Frontend output

Issues Found:
- [List any issues]

Notes:
- [Additional notes]
```

## Next Steps After Testing

1. Fix any issues found
2. Document test results
3. Update version if needed
4. Create final zip for deployment
5. Submit to WordPress.org or deploy

