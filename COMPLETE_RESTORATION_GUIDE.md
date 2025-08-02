# Complete Laravel farmOS Admin Restoration Guide

## CRITICAL: Never lose this work again!

This document contains EVERYTHING needed to restore the Laravel admin project for admin.middleworldfarms.org with full farmOS integration.

## Current Working State (As of 2025)

✅ **CONFIRMED WORKING:**
- Laravel admin dashboard with live farmOS integration
- OAuth2 authentication to farmOS API
- Live land assets (11 assets) displayed on map
- Crop planning data integration
- All farmOS API endpoints accessible
- No fallback/demo data - all live farmOS data

## Required Files and Their Purpose

### Core Integration Files
```
app/Services/FarmOSApiService.php          - Main farmOS API service with OAuth2
app/Http/Controllers/Admin/FarmOSDataController.php - Controller for farmOS routes
resources/views/admin/dashboard.blade.php  - Dashboard with map integration
resources/views/admin/farmos/planting-chart.blade.php - Planting chart view
routes/web.php                             - Contains farmOS routes
.env                                       - Contains all API credentials
```

### Test/Validation Scripts
```
test_farmos_service_complete.php   - Tests full service integration
test_oauth2_direct.php             - Tests OAuth2 token acquisition
debug_oauth2_token.php             - Debug OAuth2 issues
simple_oauth_test.php              - Simple OAuth2 test
```

### Documentation Files
```
farmOS-OAuth2-Laravel-Integration-Guide.md - OAuth2 setup guide
setup-farmos-credentials.md               - Credential setup
API-ENHANCEMENT-COMPLETE.md               - API enhancement status
diagnose-farmos.sh                        - Diagnostic script
```

## farmOS Configuration Requirements

### 1. farmOS User Requirements
- Username: `admin` (or your preferred admin user)
- Password: Strong password (documented in .env)
- **CRITICAL:** User must have these roles:
  - `Basic user`
  - `Farm Manager` (for asset access)
  - `Farm Worker` (for basic operations)

### 2. farmOS Simple OAuth Client Configuration
Access: Administration → Configuration → Web services → Simple OAuth

**Client Settings:**
```
Client ID: NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY
Client Secret: [32-character secret from .env]
Grant Types: ✅ Client Credentials
Scopes: farmos_restws_access
User: admin (the farmOS user created above)
Redirect URI: Not required for client credentials
```

**CRITICAL:** The "User" field must point to your admin user, not blank!

## Laravel .env Configuration

Add these exact lines to your .env file:

```env
# farmOS API Configuration
FARMOS_URL=https://middleworldfarms.farmos.net
FARMOS_USERNAME=admin
FARMOS_PASSWORD=[your_strong_password]

# farmOS OAuth2 Configuration
FARMOS_OAUTH_CLIENT_ID=NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY
FARMOS_OAUTH_CLIENT_SECRET=[32_character_secret]
FARMOS_OAUTH_SCOPE=farmos_restws_access
```

## Step-by-Step Restoration Process

### Phase 1: Verify farmOS Configuration

1. **Check farmOS User:**
   ```bash
   curl -u admin:password "https://middleworldfarms.farmos.net/api/user/user"
   ```

2. **Test OAuth2 Client:**
   ```bash
   cd /opt/sites/admin.middleworldfarms.org
   php test_oauth2_direct.php
   ```

3. **Verify Land Assets Access:**
   ```bash
   # Should return 11 land assets
   curl -H "Authorization: Bearer [token]" "https://middleworldfarms.farmos.net/api/asset/land"
   ```

### Phase 2: Restore Laravel Files

1. **Restore Core Service File:**
   - File: `app/Services/FarmOSApiService.php`
   - Key Features: OAuth2 primary auth, basic auth fallback, proper error handling
   - **CRITICAL:** No Carbon dependencies - uses date() and time() functions

2. **Restore Controller:**
   - File: `app/Http/Controllers/Admin/FarmOSDataController.php`
   - Routes: geometry data, crop planning, map data

3. **Restore Views:**
   - `resources/views/admin/dashboard.blade.php` - Map integration
   - `resources/views/admin/farmos/planting-chart.blade.php` - Planting chart

4. **Restore Routes:**
   - Add farmOS routes to `routes/web.php`

### Phase 3: Test Integration

1. **Test Service Layer:**
   ```bash
   php test_farmos_service_complete.php
   ```

2. **Test Direct OAuth2:**
   ```bash
   php test_oauth2_direct.php
   ```

3. **Test Dashboard:**
   - Visit: `/admin/dashboard`
   - Verify map shows 11 land assets
   - Check console for any errors

## Backup and Version Control

### Git Commit Commands
```bash
git add .
git commit -m "COMPLETE: farmOS OAuth2 integration with live data - ALL FILES WORKING"
git tag -a "farmos-integration-complete" -m "Complete working farmOS integration"
git push origin main --tags
```

### File Backup Commands
```bash
# Create dated backup
tar -czf "farmos-integration-backup-$(date +%Y%m%d).tar.gz" \
  app/Services/FarmOSApiService.php \
  app/Http/Controllers/Admin/FarmOSDataController.php \
  resources/views/admin/dashboard.blade.php \
  resources/views/admin/farmos/ \
  .env \
  test_*.php \
  *.md
```

## Troubleshooting

### Common Issues and Solutions

1. **"Access denied" for land assets:**
   - Check farmOS user roles (needs Farm Manager)
   - Verify OAuth2 client "User" field is set

2. **OAuth2 token acquisition fails:**
   - Check client ID/secret in .env
   - Verify farmOS Simple OAuth client configuration
   - Check allowed origins in farmOS client settings

3. **Dashboard shows fallback data:**
   - Check browser console for API errors
   - Test `test_farmos_service_complete.php`
   - Verify .env configuration

4. **Carbon/DateTime errors:**
   - All Carbon usage has been removed
   - Service uses standard PHP date/time functions

## Success Verification Checklist

- [ ] OAuth2 token acquisition works (`test_oauth2_direct.php`)
- [ ] Service layer works (`test_farmos_service_complete.php`)
- [ ] Dashboard shows 11 land assets on map
- [ ] Planting chart displays crop data
- [ ] No fallback/demo data visible
- [ ] All files committed to git
- [ ] .env backed up securely
- [ ] farmOS user and OAuth client documented

## Critical Contact Information

- farmOS Instance: https://middleworldfarms.farmos.net
- Admin Login: Use credentials from .env
- OAuth2 Settings: Admin → Configuration → Web services → Simple OAuth

## Last Updated
Date: 2025 (after complete restoration)
Status: ✅ FULLY WORKING - LIVE FARMOS DATA ONLY
