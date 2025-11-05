# SEO AI Meta Generator - Architecture Documentation

## Backend Architecture

### Current Setup
The plugin uses a **shared backend** with AltText AI at `https://alttext-ai-backend.onrender.com`.

### Service Differentiation
- Both services use the same backend infrastructure
- Services are differentiated via `service` parameter in API calls
- SEO AI Meta uses `service=seo-ai-meta`
- AltText AI uses `service=alttext-ai` (default)

### Why Shared Backend?
- **Operational simplicity**: Single infrastructure to maintain
- **Unified authentication**: Users can use one account for both services
- **Cost efficiency**: Lower operational costs (one server, one database)
- **Future flexibility**: Easy to offer bundle plans across services
- **Easier deployment**: Single deployment pipeline

### Configuration

#### Environment Variables (wp-config.php)
```php
// Override backend URL
define( 'SEO_AI_META_API_URL', 'https://custom-backend.com' );

// Set API timeout (seconds)
define( 'SEO_AI_META_API_TIMEOUT', 30 );

// Disable offline mode (require backend)
define( 'SEO_AI_META_OFFLINE_MODE', false );
```

### Graceful Degradation
The plugin can work without backend connection if:
- OpenAI API key is configured in settings
- Offline mode is enabled (default: true)

When backend is unavailable:
- ✅ Meta generation still works (uses OpenAI directly)
- ✅ Usage tracking falls back to local WordPress storage
- ❌ Subscription features (checkout, portal) are unavailable
- ❌ Cross-site usage sync is unavailable

### Health Checks
- Backend health is checked before critical operations
- Status is displayed in admin UI
- Automatic error detection and user feedback
- Future enhancement: Automatic retry with exponential backoff

### Error Handling

#### Authentication Errors
- Token expiration is detected automatically
- Users are prompted to re-login
- Clear error messages guide user actions

#### Network Errors
- Connection failures show specific messages
- Health check failures prevent unnecessary requests
- Graceful fallback to offline mode when possible

#### Backend Errors
- 500+ errors show "temporarily unavailable" message
- 400 errors show validation messages from backend
- 401/403 errors trigger token refresh or re-login prompt

### Future Considerations

#### Splitting to Separate Backend
If needed in the future, splitting is straightforward:
1. Change `SEO_AI_META_API_URL` constant
2. Update authentication endpoints if needed
3. Migrate user data if separate databases
4. No code changes required in plugin

#### Service Abstraction
The current architecture supports:
- Different backends per service (via config)
- Shared authentication (via JWT tokens)
- Independent scaling (via load balancing)
- Service-specific features (via service parameter)

### Code Organization

#### API Client (`includes/class-api-client-v2.php`)
- Handles all backend communication
- Manages authentication tokens
- Provides health check methods
- Handles error responses

#### Core Class (`includes/class-seo-ai-meta-core.php`)
- Orchestrates business logic
- Manages checkout flow
- Handles subscription management
- Provides status information

#### Database Layer (`includes/class-seo-ai-meta-database.php`)
- Custom tables for plugin data
- Backward compatibility with WordPress meta
- Migration support
- Data persistence

### Best Practices Implemented

1. ✅ **Health Checks**: Backend availability checked before operations
2. ✅ **Graceful Degradation**: Plugin works offline when possible
3. ✅ **Configuration**: Environment-based configuration support
4. ✅ **Error Messages**: Clear, actionable error messages
5. ✅ **Status Indicators**: UI shows backend status to users
6. ✅ **Documentation**: Architecture decisions documented

### Monitoring

#### Backend Status
- Health endpoint: `/health`
- Checked before checkout operations
- Status displayed in admin UI
- Cached for performance (5 minutes)

#### Error Logging
- All API errors logged when `WP_DEBUG` is enabled
- Includes endpoint, status code, and response
- Helps with debugging and monitoring

### Security

#### Authentication
- JWT tokens stored securely in custom database tables
- Tokens cleared on authentication failures
- Automatic token refresh (future enhancement)

#### API Security
- HTTPS enforced for production
- SSL verification enabled
- Timeout limits prevent hanging requests

### Performance

#### Caching
- Backend health checks cached (5 minutes)
- Price IDs cached per request
- Usage data cached locally

#### Optimization
- Configurable timeouts
- Efficient health checks
- Minimal API calls

## Summary

The plugin uses a **shared backend architecture** with AltText AI, which provides:
- Operational simplicity
- Unified user experience
- Cost efficiency
- Future flexibility

The architecture is designed to:
- Support graceful degradation
- Provide clear error feedback
- Allow easy future splitting if needed
- Follow WordPress and PHP best practices



