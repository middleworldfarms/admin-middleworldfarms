# MWF Admin Panel Project Status
*Last Updated: June 25, 2025*

## ⚠️ CRITICAL CONFIGURATION - DO NOT CHANGE ⚠️
- **PHP VERSION: 8.2.28** - Set in Plesk, DO NOT CHANGE
- **FREQUENCY DETECTION: Use WooCommerce order item meta on the parent order**
- **Frequency meta keys:**
    - `frequency` (e.g. "Fortnightly box", "Weekly box")
    - `payment-option` (e.g. "Fortnightly", "Weekly")
- These fields are stored in the WooCommerce order item meta (not the main order meta or subscription meta) at the time of checkout.
- ⚠️ **IMPORTANT:** Do NOT use `_mwf_fortnightly` or `_mwf_fortnightly_week` for frequency detection—they are not set by WooCommerce checkout and may be missing or incorrect. Previous logic using these fields (added by Cluade) caused confusion and should be removed.
- If you need to determine a customer's delivery frequency, always check the order item meta of the parent order for these fields.

## 🎉 MAJOR SUCCESS - USER SWITCHING FULLY WORKING ✅
**Date: June 25, 2025**

### **THE PROBLEM:**
User switching feature was failing with "can't find user with that email" errors, despite WordPress API seemingly working.

### **ROOT CAUSE DISCOVERED:**
Missing `SELF_SERVE_SHOP_INTEGRATION_KEY` environment variable was causing API authentication to fail.

### **THE SOLUTION:**
1. **Fixed Integration Key** - Added missing `SELF_SERVE_SHOP_INTEGRATION_KEY=Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h` to `.env`
2. **Removed Duplicate Event Listeners** - Fixed double tab opening by removing duplicate JavaScript handlers
3. **Improved Button State Management** - Added reset function to prevent stuck loading states
4. **Enhanced Error Handling** - Better user feedback and session cleanup

### **TECHNICAL DETAILS:**
- **Laravel Config**: `config/services.php` was looking for `SELF_SERVE_SHOP_INTEGRATION_KEY` env var
- **Available Keys**: Had `MWF_API_KEY` and `MWF_INTEGRATION_KEY` but not the required one
- **API Endpoint**: `https://middleworldfarms.org/wp-json/mwf/v1/users/search` now working perfectly
- **Authentication**: Uses `X-WC-API-Key` header with proper integration key
- **Frontend**: Single-tab user switching with proper session management

### **CURRENT STATUS:**
✅ **User search working** (finds 3 users for "john")  
✅ **User switching working** (single tab, proper session)  
✅ **WordPress API integration working** (proper authentication)  
✅ **Button states managed properly** (no stuck loading states)  
✅ **Error handling robust** (clear user feedback)  

### **FILES MODIFIED:**
- `.env` - Added missing integration key
- `resources/views/admin/deliveries/fixed.blade.php` - Removed duplicate handlers, improved UX
- No backend changes needed (Laravel code was correct all along!)

**🎯 PROJECT GOAL ACHIEVED: User switching is now FULLY FUNCTIONAL and PRODUCTION READY!**

---

## COMPLETED PROJECT GOALS ✅

### **PRIMARY GOAL ACHIEVED: User Switching Feature**
The main project goal has been successfully completed:
- ✅ **User switching buttons added to delivery schedule tables**
- ✅ **Positioned under customer names for fast admin workflow**
- ✅ **Single-tab switching with proper session management**
- ✅ **Robust error handling and user feedback**

### **CURRENT WORKING STATE**
1. **Delivery Schedule Page**: `https://admin.middleworldfarms.org/admin/deliveries`
   - Shows 4 deliveries and 15 collections with real data
   - User switching buttons functional under each customer name
   - Week A/B navigation working
   - Print functionality working for both deliveries and collections
   - API connection test passes

### **Key Working Files:**
- `resources/views/admin/deliveries/fixed.blade.php` (main admin UI with user switching)
- `app/Http/Controllers/Admin/UserSwitchingController.php` (backend user switching logic)
- `app/Services/WpApiService.php` (WordPress/WooCommerce API integration)
- `app/Services/DeliveryScheduleService.php` (delivery data service)
- `routes/web.php` (all required routes configured)

## FINAL API STATUS - ALL WORKING ✅

### **MWF Custom API** ✅ WORKING
- **Endpoint**: `https://middleworldfarms.org/wp-json/mwf/v1/users/search`
- **Authentication**: X-WC-API-Key header with `SELF_SERVE_SHOP_INTEGRATION_KEY`
- **Status**: 200 OK - Returns user data for all roles
- **Usage**: Primary endpoint for user search and switching

### **WordPress User Switching** ✅ WORKING  
- **Method**: Direct login URL generation via MWF plugin
- **Features**: Auto-logout, session cleanup, admin warning banner
- **Integration**: Seamless with Laravel admin panel

## TECHNICAL ARCHITECTURE SUMMARY

The user switching feature uses a multi-layered approach:

1. **Frontend (Laravel Blade)**: User search interface and switch buttons
2. **Laravel Backend**: UserSwitchingController handles search and switch requests
3. **WordPress Integration**: WpApiService communicates with MWF plugin API
4. **WordPress Plugin**: MWF plugin provides user search and login URL generation
5. **Session Management**: Auto-logout and session cleanup on WordPress side

**Result**: Admins can search for any user by email and switch to their account in a new tab while maintaining their admin session in the original tab.

---
*This file will be updated after each significant change to prevent losing track.*
