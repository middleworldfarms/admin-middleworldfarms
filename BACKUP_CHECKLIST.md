# CRITICAL BACKUP CHECKLIST

## Files to NEVER LOSE

### Core Integration Files (CRITICAL)
- [ ] `app/Services/FarmOSApiService.php` - Main farmOS service with OAuth2
- [ ] `app/Http/Controllers/Admin/FarmOSDataController.php` - farmOS controller
- [ ] `resources/views/admin/dashboard.blade.php` - Dashboard with map
- [ ] `resources/views/admin/farmos/planting-chart.blade.php` - Planting chart
- [ ] Routes in `routes/web.php` (farmOS section)

### Configuration Files (CRITICAL)
- [ ] `.env` - Contains ALL API credentials and OAuth2 settings
- [ ] `config/farmos.php` - farmOS configuration (if exists)

### Test/Validation Scripts (IMPORTANT)
- [ ] `test_farmos_service_complete.php` - Complete service test
- [ ] `test_oauth2_direct.php` - OAuth2 direct test
- [ ] `debug_oauth2_token.php` - OAuth2 debugging
- [ ] `simple_oauth_test.php` - Simple OAuth test

### Documentation (CRITICAL)
- [ ] `COMPLETE_RESTORATION_GUIDE.md` - This complete guide
- [ ] `deploy-farmos-integration.sh` - Deployment script
- [ ] `farmOS-OAuth2-Laravel-Integration-Guide.md` - OAuth2 setup
- [ ] `API-ENHANCEMENT-COMPLETE.md` - Enhancement status

## farmOS Server Configuration (DOCUMENT THESE!)

### farmOS User Account
- [ ] Username: `admin`
- [ ] Password: [documented in .env]
- [ ] Roles: Basic user, Farm Manager, Farm Worker
- [ ] Permissions: Asset access, API access

### farmOS Simple OAuth Client
- [ ] Client ID: `NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY`
- [ ] Client Secret: [32-character secret in .env]
- [ ] Grant Types: Client Credentials ✅
- [ ] Scopes: `farmos_restws_access`
- [ ] User: `admin` (CRITICAL - must not be blank!)
- [ ] Redirect URI: Not required for client credentials

## Quick Recovery Commands

### 1. Test Current State
```bash
cd /opt/sites/admin.middleworldfarms.org
php test_oauth2_direct.php
php test_farmos_service_complete.php
```

### 2. Create Backup
```bash
tar -czf "farmos-backup-$(date +%Y%m%d-%H%M).tar.gz" \
  app/Services/FarmOSApiService.php \
  app/Http/Controllers/Admin/FarmOSDataController.php \
  resources/views/admin/dashboard.blade.php \
  resources/views/admin/farmos/ \
  routes/web.php \
  .env \
  test_*.php \
  *.md \
  deploy-farmos-integration.sh
```

### 3. Git Commit Everything
```bash
git add .
git commit -m "BACKUP: Complete farmOS integration - $(date)"
git tag "farmos-backup-$(date +%Y%m%d-%H%M)"
git push origin main --tags
```

### 4. Deploy/Restore
```bash
./deploy-farmos-integration.sh
```

## Success Indicators

When everything is working correctly, you should see:

### OAuth2 Test Results
```
✅ SUCCESS: OAuth2 token acquired!
✅ SUCCESS: Found 11 land assets with OAuth2!
```

### Service Test Results
```
✅ Authentication successful: YES
🎉 OAuth2 authentication is working!
All 11 land assets should now be visible on the admin dashboard map!
```

### Dashboard Browser Test
- Map displays 11 land assets
- No fallback/demo data visible
- Console shows no API errors
- Land assets are clickable with property details

## Emergency Recovery Contacts

If farmOS server settings are lost:

1. **farmOS Admin Access:** https://middleworldfarms.farmos.net/user/login
2. **Simple OAuth Settings:** Admin → Configuration → Web services → Simple OAuth
3. **User Management:** Admin → People
4. **API Endpoints:** https://middleworldfarms.farmos.net/api

## NEVER DO THESE THINGS

- [ ] Don't delete the OAuth2 client in farmOS without backing up credentials
- [ ] Don't change farmOS user roles without testing API access
- [ ] Don't clear .env file without backup
- [ ] Don't modify FarmOSApiService.php without understanding OAuth2 flow
- [ ] Don't use Carbon date functions (compatibility issues)

## Last Working State Verified

- Date: 2025 (current session)
- OAuth2: ✅ Working
- Land Assets: ✅ 11 assets accessible
- Dashboard: ✅ Live data only
- Tests: ✅ All passing

## Repository Status

Current working files are in: `/opt/sites/admin.middleworldfarms.org`

**COMMIT EVERYTHING NOW!**
