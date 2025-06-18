# Direct Database Migration Tasks

This document lists all locations where `DirectDatabaseService` is injected or used and outlines the steps required to migrate to `WpApiService`.

## Controllers to Update

1. **DeliveryController**
   - Path: `app/Http/Controllers/Admin/DeliveryController.php`
   - Injects `DirectDatabaseService` in constructor
   - Methods using direct DB calls:
     - `index()`
     - `apiTest()`
     - `updateCustomerWeek()`
     - `diagnosticSubscriptions()`
     - `testActiveFilter()`
     - `debugWeekAssignment()`
     - `debugDeliverySchedule()`
     - `debugSpecificCustomers()`
     - `debugPageDisplay()`
     - `simpleCount()`
     - `debugCustomerStatuses()`
     - `compareWeekLogic()`
   - **Tasks**:
     - Change constructor to inject `WpApiService`.
     - Replace all `$directDb->...` calls with corresponding `$wpApi->...` methods or implement new API methods.

2. **LoginController**
   - Path: `app/Http/Controllers/Auth/LoginController.php`
   - Injects `DirectDatabaseService` in constructor
   - **Tasks**:
     - Inject `WpApiService` instead of `DirectDatabaseService`.
     - Remove any direct DB login logic and use API calls or Laravel auth.

3. **DashboardController**
   - Path: `app/Http/Controllers/Admin/DashboardController.php`
   - Injects `DirectDatabaseService` in constructor
   - **Tasks**:
     - Change constructor to inject `WpApiService`.
     - Replace direct DB calls (e.g. recent user counts) with API-based equivalents.

4. **UserSwitchingController**
   - Path: `app/Http/Controllers/Admin/UserSwitchingController.php`
   - Already migrated to `WpApiService`
   - **Tasks**:
     - Remove any leftover `DirectDatabaseService` references after other controllers are updated.

## Migration Steps

- Update each controller constructor to accept `WpApiService`.
- Replace all instances of `$directDb->method()` with `$wpApi->method()`.
- Add or extend methods in `WpApiService` to cover any missing functionality (e.g. delivery schedule, week logic).
- Run tests and verify endpoints still return expected data.
- Once all replacements are complete, delete `DirectDatabaseService.php` and remove the WordPress DB connection in `config/database.php`.
