# Debug Logging System - User Guide

## Overview

Your SEO AI Meta Generator plugin now includes a comprehensive debug logging system that allows you to track and troubleshoot issues without needing access to WordPress debug logs or server error logs.

## Features

### 1. Debug Logs Tab
A new "Debug Logs" tab has been added to your plugin dashboard with:
- **Real-time log viewing** - See all plugin activity as it happens
- **Statistics dashboard** - Quick overview of errors, warnings, and info messages
- **Filtering** - Filter logs by level (ERROR, WARNING, INFO, DEBUG) or search by keyword
- **Export** - Download logs as JSON or CSV for sharing or deeper analysis
- **Auto-cleanup** - Keeps only the most recent 1000 log entries automatically

### 2. Log Levels

Logs are categorized into four levels:

| Level | Color | Description | Examples |
|-------|-------|-------------|----------|
| **ERROR** | Red | Critical failures that prevent functionality | API failures, authentication errors, checkout failures |
| **WARNING** | Orange | Issues that don't break functionality but need attention | Session expired, invalid parameters |
| **INFO** | Blue | Important operational information | Checkout started, session created, user logged in |
| **DEBUG** | Gray | Detailed technical information for troubleshooting | API requests, parameter values, response codes |

### 3. What's Being Logged

The system automatically logs:

#### Checkout Flow
- When checkout process starts
- Authentication checks
- Price ID validation
- Checkout session creation attempts
- Success or failure details
- Error codes and messages

#### API Requests
- Every API call to the backend
- Request method and endpoint
- Whether authentication token is present
- Response status codes
- Error details if requests fail

#### Authentication
- Login attempts
- Token validation
- Session expiration
- Logout events

## How to Use

### Viewing Logs

1. Go to your WordPress admin dashboard
2. Navigate to **SEO AI Meta Generator**
3. Click the **Debug Logs** tab
4. View the logs table with timestamp, level, message, and context

### Filtering Logs

**Filter by Level:**
1. Use the dropdown to select: All Levels, Errors Only, Warnings Only, Info Only, or Debug Only
2. Click "Apply Filters"

**Search Logs:**
1. Type keywords in the search box (e.g., "checkout", "authentication", "error")
2. Click "Apply Filters" or press Enter
3. Click "Clear" to reset all filters

### Exporting Logs

**Export as JSON:**
- Click "Export JSON" to download a structured JSON file
- Best for programmatic analysis or sharing with developers

**Export as CSV:**
- Click "Export CSV" to download a spreadsheet-friendly file
- Best for viewing in Excel or Google Sheets

### Clearing Logs

- Click "Clear Logs" button
- Confirm the action
- All logs will be deleted (a new log entry will be created noting this action)

## Troubleshooting the Checkout Issue

### Step 1: Clear Current Logs
Before testing, clear all logs to start fresh.

### Step 2: Attempt Checkout
1. Click "Go Pro for More AI Power" button
2. Try to proceed with checkout

### Step 3: Check the Logs
Go to Debug Logs tab and look for:

#### If Button Does Nothing:
- Check browser console for JavaScript errors (F12 > Console tab)
- No logs will appear if the JavaScript fails

#### If You See Authentication Error:
Look for logs like:
```
WARNING: Checkout attempted without authentication
WARNING: API authentication failed - status_code: 401
```
**Solution:** Click the "Login/Register" button in the header

#### If You See Server Error:
Look for logs like:
```
ERROR: API server error - status_code: 500
ERROR: Backend service error during checkout
```
**Solution:** Backend service is down - wait a few minutes and try again

#### If You See Invalid Price ID:
Look for logs like:
```
ERROR: Invalid price ID for checkout
```
**Solution:** Contact support with the log details

### Step 4: Export and Share
If you can't solve the issue:
1. Click "Export JSON" to download logs
2. Share the file with support or developers
3. The logs contain all the context needed to diagnose the issue

## Understanding Log Context

Each log entry can have a "Context" section with additional details:

**Example - Checkout Started:**
```json
{
  "plan": "pro",
  "price_id": "price_1SQ6a5Jl9Rm418cMx77q8KB9"
}
```

**Example - Authentication Failed:**
```json
{
  "endpoint": "/billing/checkout",
  "status_code": 401,
  "error_message": "Access token required"
}
```

**Example - API Request:**
```json
{
  "endpoint": "/billing/checkout",
  "method": "POST",
  "has_auth": true,
  "data_keys": ["priceId", "successUrl", "cancelUrl", "service"]
}
```

## Privacy & Security

- **No sensitive data is logged** - Passwords and API keys are automatically redacted
- **IP addresses are logged** - For security tracking
- **Logs are stored in WordPress database** - Not publicly accessible
- **Automatic cleanup** - Old logs are deleted automatically
- **User IDs are logged** - To track which user performed actions

## Technical Details

### Files Added/Modified

**New Files:**
- `/includes/class-seo-ai-meta-logger.php` - Logger class

**Modified Files:**
- `/admin/partials/seo-ai-meta-admin-display.php` - Added Debug Logs tab and UI
- `/includes/class-seo-ai-meta-core.php` - Added logging to checkout flow
- `/includes/class-api-client-v2.php` - Added logging to API requests

### Database Storage

Logs are stored in the WordPress options table:
- Option name: `seo_ai_meta_debug_logs`
- Maximum entries: 1000 (oldest deleted automatically)
- Format: Array of log objects

### Performance

- **Minimal impact** - Logging adds ~0.1ms per operation
- **Automatic cleanup** - Prevents database bloat
- **Optional** - Can be disabled by removing log files

## FAQ

**Q: Will this slow down my site?**
A: No, the logging system has minimal performance impact and only runs in admin area.

**Q: How long are logs kept?**
A: The most recent 1000 log entries are kept. Older entries are automatically deleted.

**Q: Can I disable logging?**
A: Yes, simply rename the logger file or remove the require_once statements.

**Q: Are logs visible to non-admin users?**
A: No, only users with the 'manage_seo_ai_meta' capability can view logs.

**Q: What if I see no logs?**
A: The system only logs when actions occur. Try performing an action like attempting checkout.

## Next Steps for Your Checkout Issue

Based on the error you're experiencing, here's what to check:

1. **Go to Debug Logs tab** in your plugin
2. **Click "Clear Logs"** to start fresh
3. **Click "Go Pro for More AI Power"** button
4. **Check the logs** immediately after

You should see entries like:
- `INFO: Checkout process started`
- `DEBUG: Authentication status checked`
- Either success or error messages

Share the exported JSON with me if you need help interpreting the results!
