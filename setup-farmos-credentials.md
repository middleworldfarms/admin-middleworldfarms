# FarmOS Credentials Setup Guide

## Issue
Your farmOS instance at `https://farmos.middleworldfarms.org` is returning 403 Forbidden, and the credentials are missing from your `.env` file.

## Steps to Fix

### 1. Access FarmOS Admin Panel
First, you need to access your farmOS instance. The 403 error suggests access restrictions.

**Try these URLs:**
- `https://farmos.middleworldfarms.org/user/login`
- `http://farmos.middleworldfarms.org/user/login` (if HTTPS is blocked)
- Check if there's an IP whitelist or .htaccess restriction

### 2. Log into FarmOS as Admin
Use your farmOS admin credentials to log in.

### 3. Create OAuth Application
Navigate to: **Administration > Configuration > Web Services > OAuth**
Or direct URL: `https://farmos.middleworldfarms.org/admin/config/services/oauth`

Create a new OAuth application with:
- **Name**: "Middle World Farms Admin Dashboard"
- **Client ID**: `farm` (already set in .env)
- **Grant Types**: Select "Authorization Code" and "Password"
- **Scopes**: Select all available scopes for full API access

This will generate a **Client Secret** that you need.

### 4. Create API User (Recommended)
Create a dedicated user for API access:
- **Username**: `api_user` (or your preferred name)
- **Email**: `api@middleworldfarms.org`
- **Roles**: Give it "Farm Manager" or equivalent permissions
- **Password**: Generate a strong password

### 5. Update .env File
Update your `.env` file with the credentials:

```properties
FARMOS_USERNAME=api_user
FARMOS_PASSWORD=your_api_user_password
FARMOS_CLIENT_SECRET=your_generated_client_secret
```

### 6. Test Connection
Run the test command:
```bash
php artisan test:farmos-service
```

## Alternative: Direct Database Access
If OAuth setup is complex, we could potentially access farmOS data directly via its database (similar to how you access WordPress), but OAuth API is the recommended approach.

## Troubleshooting 403 Error
The 403 error might be due to:
1. **IP restrictions** in farmOS or server config
2. **Apache .htaccess** blocking external access
3. **Firewall rules**
4. **farmOS maintenance mode**

Check your server logs and farmOS configuration for access restrictions.
