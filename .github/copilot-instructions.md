# Middle World Farms - Farm Delivery System AI Instructions

## Project Overview
Laravel 12 (PHP 8.2+) application for Community Supported Agriculture (CSA) delivery management with deep farmOS integration, multi-database architecture, and AI-powered crop planning.

## Critical Architecture Patterns

### Multi-Database Configuration
The system integrates **three databases simultaneously**:
- **Laravel (`mysql`)**: Primary app database (deliveries, users, crop plans)
- **WordPress (`wordpress`)**: WooCommerce orders/subscriptions via direct connection
- **farmOS (`farmos`)**: Farm data access when available

**Models specify connections explicitly:**
```php
// WordPress models ALWAYS set this
protected $connection = 'wordpress';
protected $table = 'posts'; // WooCommerce uses custom post types
```

Key models: `WooCommerceOrder`, `WordPressUser`, `WooCommerceOrderMeta` use `wordpress` connection.

### Service Layer Architecture
Services in `app/Services/` handle external integrations:
- **`FarmOSApi`**: OAuth2 authentication via `FarmOSAuthService` singleton
- **`DirectDatabaseService`**: WordPress REST API wrapper (NOT direct DB queries)
- **`WeatherService`**: Met Office DataHub integration
- **`SymbiosisAIService`**: AI crop planning and chat (`app/Services/AI/`)

**Pattern**: Services encapsulate auth, caching, and error handling. Never bypass services for external APIs.

### Artisan Command Patterns
Custom commands in `app/Console/Commands/` use descriptive signatures:
```bash
php artisan farmos:sync-varieties --force --push-to-farmos
php artisan subscription:manage {email} --action=info
php artisan varieties:populate-harvest-windows --limit=50
```

**Convention**: Namespace commands by integration (`farmos:`, `subscription:`, `varieties:`).

## Development Workflow

### Running the Application
```bash
# Development (all services):
composer dev  # Runs server, queue, logs, vite concurrently

# Individual processes:
php artisan serve
php artisan queue:listen --tries=1
php artisan pail --timeout=0
npm run dev
```

### Testing Commands
```bash
composer test              # Runs config:clear + phpunit
php artisan test           # Direct PHPUnit execution
```

### Database Migrations
```bash
php artisan migrate        # Laravel tables only
# WordPress/farmOS databases are external - DO NOT migrate them
```

## farmOS Integration Specifics

### OAuth2 Authentication Flow
1. `FarmOSAuthService::getInstance()` maintains singleton
2. Token cached for 3600s, auto-refreshes
3. All API calls route through `FarmOSApi` methods

### Succession Planning Feature
Located in admin dashboard (`/admin/farmos/succession-planning`):
- Backward-planning from harvest windows
- Drag-drop timeline interface (Chart.js Gantt)
- Generates farmOS quick form URLs for seeding/transplanting/harvest logs
- AI calculates optimal planting dates via `HolisticAICropService`

**Data Flow**: User input → JS validation → AI processing → Laravel backend → farmOS quick forms

#### Recent Timeline Fixes (October 2025)
**Critical Bug Fixes:**
- **Missing `getBedOccupancy` Method**: Added to `FarmOSApi` service to fetch bed and planting data from farmOS API
- **Pagination Support**: Implemented full pagination for large bed datasets (174+ beds)
- **Block Organization**: Added intelligent block parsing from bed names (e.g., "3/1" → Block 3)
- **Bed Filtering**: Removed block header entries (named "Block X") to show only actual beds
- **Timeline Display**: Fixed filtering logic to show beds even when block organization is incomplete

**UI Improvements:**
- **Removed Duplicate Date Headers**: Eliminated duplicate month labels between main timeline and block headers
- **Hedgerow Representation**: Added visual hedgerow indicators between blocks to match real-world farm layout
- **Enhanced Block Headers**: Added hedgerow icons and improved spacing/layout

**API Endpoint**: `GET /admin/farmos/succession-planning/bed-occupancy`
- Returns: `{success: true, data: {beds: [...], plantings: [...]}}`
- Beds include: `id`, `name`, `block`, `status`, `land_type`
- Supports date range filtering for occupancy visualization

**Timeline Structure**:
- Beds grouped by blocks (Block 1, Block 3, etc.)
- Individual bed rows with drag-drop zones
- Real-time occupancy from farmOS land assets
- Succession indicators for planned harvest dates
- Visual hedgerow separators between blocks

#### Succession Planning Page Dependencies

**Main View File:**
- `resources/views/admin/farmos/succession-planning.blade.php` (6970 lines)
  - Extends `layouts.app`
  - Contains crop selection forms, harvest window inputs, bed dimensions
  - Timeline visualization with drag-and-drop functionality
  - AI chat interface for crop planning consultation
  - Export functionality (CSV download)
  - Extensive inline JavaScript for UI interactions and API calls

**JavaScript Dependencies:**
- External: SortableJS (CDN) for drag-and-drop functionality
- Local: `public/js/succession-planner.js` - SuccessionPlanner class managing UI state and event handlers

**Controller:**
- `app/Http/Controllers/Admin/SuccessionPlanningController.php` (2969 lines)
  - Methods: `index()`, `calculate()`, `generate()`, `createLogs()`, `chat()`, `getVariety()`, `getAIStatus()`, etc.
  - Integrates with `FarmOSApi`, `SymbiosisAIService`, `FarmOSQuickFormService`

**Routes (under `/admin/farmos/succession-planning/`):**
- `GET /` - Main interface (`index`)
- `POST /calculate` - Generate succession plan
- `POST /generate` - Create detailed plan
- `POST /create-logs` - Submit farmOS logs
- `POST /chat` - AI consultation
- `GET /varieties/{id}` - Variety details
- `GET /bed-occupancy` - **NEW**: Timeline bed occupancy data (returns beds + plantings)
- `GET /ai-status` - AI service status
- `POST /wake-ai` - Initialize AI service

**AI Integration:**
- Python service: `ai_service/app/main.py`
- Endpoint: `POST /api/v1/succession-planning/holistic`
- Provides sacred geometry, cosmic timing, biodynamic calendar alignment

**Services Used:**
- `FarmOSApi`: Crop/variety data, geometry assets
- `SymbiosisAIService`: AI chat and crop intelligence
- `FarmOSQuickFormService`: Generate quick form URLs for farmOS logging

**Models:**
- `PlantVariety`: Local variety database (synced from farmOS)
- Multi-connection access for farmOS data when needed

## WooCommerce Integration

### Order/Subscription Access
Access via `DirectDatabaseService` methods (REST API, not raw SQL):
```php
$service->searchUsers($query)      // Search customers
$service->generateUserSwitchUrl()  // Admin impersonation
```

**Critical**: WooCommerce stores orders as `post_type='shop_order'` in `posts` table. Use scopes:
```php
WooCommerceOrder::query() // Auto-scoped to shop_order/shop_subscription
```

## Configuration Conventions

### Environment Variables
- `FARMOS_*`: farmOS OAuth2 credentials
- `WP_DB_*`: WordPress database connection (separate from main DB)
- `FARMOS_DB_*`: farmOS database (optional direct access)

### Route Structure
- `/admin/*`: Protected admin routes (`admin.auth` middleware)
- `/api/conversations/*`: Secured API endpoints (same auth)
- Prefixes: `admin.` for named routes

## Key Files Reference

### Core Services
- `app/Services/FarmOSApi.php`: farmOS API client
  - **NEW**: `getBedOccupancy($startDate, $endDate)` - Fetches beds and plantings with pagination
  - OAuth2 authentication via `FarmOSAuthService` singleton
  - Methods: `getAvailableCropTypes()`, `getGeometryAssets()`, `getHarvestLogs()`
- `app/Services/DirectDatabaseService.php`: WordPress integration
- `app/Services/AI/SymbiosisAIService.php`: AI crop intelligence

### Models
- Multi-connection: `app/Models/WooCommerceOrder.php` (example)
- Laravel-only: `app/Models/PlantVariety.php`, `app/Models/CropPlan.php`

### Configuration
- `config/database.php`: Three-database setup (lines 75-120)
- `.env.example`: Template with all required keys (274 lines)

## Common Pitfalls

1. **Never query WordPress/farmOS DBs directly** - use services
2. **Check connection property** when creating new models
3. **OAuth tokens expire** - always use `FarmOSAuthService` singleton
4. **Queue workers required** - delivery automation depends on `queue:listen`
5. **Artisan namespaces** - follow existing patterns (`farmos:`, `subscription:`)
6. **JavaScript function duplication** - Remove duplicate function definitions (e.g., `submitAllQuickForms` was defined twice)
7. **FarmOS bed data** - Beds are land assets, not activities; use proper pagination for large datasets

## Testing Practices

- Feature tests in `tests/Feature/`
- Current: `SuccessionPlanningTest` (basic structure)
- Use RefreshDatabase trait for Laravel DB, mock external services

## Documentation Links

- Main README: Project setup and features
- SUCCESSION_PLANNER_README.md: Complete workflow for succession planning
- CONTRIBUTING.md: Development setup (Docker/traditional)
