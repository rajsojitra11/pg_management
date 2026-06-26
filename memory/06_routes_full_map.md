# Routes Full Map

## Route Source

`php artisan route:list --except-vendor` reports 302 routes in this project.

Full route definitions are owned by:

- `routes/web.php`
- `routes/api.php`
- `Modules/*/routes/web.php`
- `Modules/*/routes/api.php`

## Root Routes

| Method | URL | Handler | Middleware | Role Restriction | Returns |
| --- | --- | --- | --- | --- | --- |
| ANY | `/` | Redirect to `/login` | web | None | Redirect |
| GET | `/css/erp-config.css` | closure | web | None | CSS response |
| GET | `/db-info` | closure | web | None | JSON |
| GET | `/clear-cache` | closure | web | None | Redirect/string |
| GET | `/lookup/countries` | `LookupController@countries` | auth | Authenticated | JSON |
| GET | `/lookup/states` | `LookupController@states` | auth | Authenticated | JSON |
| GET | `/lookup/cities` | `LookupController@cities` | auth | Authenticated | JSON |
| GET | `/lookup/currencies` | `LookupController@currencies` | auth | Authenticated | JSON |
| GET | `/lookup/units` | `LookupController@units` | auth | Authenticated | JSON |
| GET | `/lookup/years` | `LookupController@years` | auth | Authenticated | JSON |
| GET | `/lookup/active-users` | `LookupController@activeUsers` | auth | Authenticated | JSON |
| GET | `/lookup/pg-list` | `LookupController@pgList` | auth | Authenticated | JSON |
| GET | `/lookup/rooms-by-pg` | `LookupController@roomsByPg` | auth | Authenticated | JSON |
| GET | `/lookup/tenant-list` | `LookupController@tenantList` | auth | Authenticated | JSON |
| GET | `/lookup/complaints-by-pg` | `LookupController@complaintsByPg` | auth | Authenticated | JSON |
| GET | `/api/impersonate/users` | `ImpersonateController@users` | auth + role | `Super_Admin` | JSON |
| POST | `/api/session/extend` | `SessionController@extend` | auth | Authenticated | JSON |
| GET | `/api/session/status` | `SessionController@status` | auth | Authenticated | JSON |
| POST | `/api/session/heartbeat` | `SessionController@heartbeat` | auth | Authenticated | JSON |
| GET | `/api/session/warning` | `SessionController@checkWarning` | auth | Authenticated | JSON |
| GET | `/api/session/config` | `SessionController@config` | auth | Authenticated | JSON |

## Module Web Route Pattern

Most modules use resource routes:

| Module | Web URL Base | Controller | Middleware | Returns |
| --- | --- | --- | --- | --- |
| Login | `/login`, `/logout` | `AuthenticatedSessionController` | guest/auth | View/redirect |
| Dashbord | `/dashboard` | `DashbordController` | web, auth, verified | View/JSON |
| PgManagement | `/pg-management` | `PgManagementController` | auth, verified | View/JSON |
| Room | `/room-categories`, `/rooms` | `RoomCategoryController`, `RoomController` | auth, verified | View/JSON |
| Tenant | `/tenant` | `TenantController` | auth, verified | View/JSON |
| Payment | `/payments` | `PaymentController` | auth, verified | View/JSON |
| Complaint | `/complaints` | `ComplaintController` | auth, verified | View/JSON |
| Maintenance | `/maintenance` | `MaintenanceController` | auth, verified | View/JSON |
| Service | `/service-categories`, `/services` | `ServiceCategoryController`, `ServiceController` | auth, verified | View/JSON |
| Subscription | `/subscriptions` | `SubscriptionController` | auth, verified | View/JSON |
| User | `/users`, `/profile` | `UserController` | auth, verified | View/JSON |
| Role | `/roles` | `RoleController` | auth, verified | View/JSON |
| MenuMaster | `/menumasters` | `MenuMasterController` | auth, verified | View/JSON |
| Setting | `/settings` | `SettingController` | auth, verified | View/JSON |
| Country/State/City | `/countries`, `/states`, `/cities` | respective controllers | auth, verified | View/JSON |
| Currency/Unit/Year | `/currencies`, `/units`, `/years` | respective controllers | auth, verified | View/JSON |

## API Route Pattern

API routes are mounted under `api/v1/*` and map to module resource controllers.
Current API modules include cities, countries, currencies, dashboard, env
variables, login, menu masters, payments, PG management, roles, settings,
states, subscriptions, tenants, units, users, and years.
