# Controllers Methods Map

## Root Controllers

| Controller | Public Methods | Side Effects |
| --- | --- | --- |
| `App\Http\Controllers\LookupController` | lookup methods for countries, states, cities, currencies, units, years, users, PGs, rooms, tenants, complaints | Read-only JSON queries |
| `App\Http\Controllers\SessionController` | `extend`, `status`, `heartbeat`, `checkWarning`, `config` | Session timestamp/status updates |
| `App\Http\Controllers\ImpersonateController` | `users` | Reads users for impersonation |

## Module Controller Pattern

Most CRUD controllers expose:

- `index`
- `create` where route allows it
- `store`
- `show`
- `edit`
- `update`
- `destroy`

Validation is performed with module FormRequest classes named
`Store*Request`, `Update*Request`, and `Delete*Request`.

## Important Controllers

| Controller | File Path | Key Methods | DB Writes | Notes |
| --- | --- | --- | --- | --- |
| `PgManagementController` | `Modules/PgManagement/app/Http/Controllers/PgManagementController.php` | `index`, `show`, `store`, `update`, `destroy` | PG create/update/delete | DataTables index |
| `RoomCategoryController` | `Modules/Room/app/Http/Controllers/RoomCategoryController.php` | CRUD | Room category writes | DataTables style |
| `RoomController` | `Modules/Room/app/Http/Controllers/RoomController.php` | CRUD, `categoriesByPg` | Room writes | PG-dependent category lookup |
| `TenantController` | `Modules/Tenant/app/Http/Controllers/TenantController.php` | CRUD, `payments` | User, UserProfile, Tenant writes | Tenant create has multi-table side effects |
| `PaymentController` | `Modules/Payment/app/Http/Controllers/PaymentController.php` | CRUD, `toggleVerified` | Payment writes | Verification status updates |
| `ComplaintController` | `Modules/Complaint/app/Http/Controllers/ComplaintController.php` | CRUD, `servicesByCategory`, `nextComplaintNo` | Complaint writes | Complaint numbering |
| `MaintenanceController` | `Modules/Maintenance/app/Http/Controllers/MaintenanceController.php` | CRUD | Maintenance writes | PG operations |
| `DashbordController` | `Modules/Dashbord/app/Http/Controllers/DashbordController.php` | `index`, KPI/chart/table endpoints, config saves | Dashboard config writes | Uses dashboard services |
| `UserController` | `Modules/User/app/Http/Controllers/UserController.php` | CRUD, profile, password, assignment, theme/layout, status | User/profile/preference writes | User preference and session effects |
| `RoleController` | `Modules/Role/app/Http/Controllers/RoleController.php` | CRUD | Role/permission writes | Spatie permission integration |
| `MenuMasterController` | `Modules/MenuMaster/app/Http/Controllers/MenuMasterController.php` | CRUD, duplicate, move, normalize, rebuild, export, statistics | Menu writes | Tree/hierarchy operations |
| `EnvVariableController` | `Modules/EnvVariable/app/Http/Controllers/EnvVariableController.php` | CRUD, sync/cache/composer helpers | Env variable writes | Can affect app config |
| `EmailController` | `Modules/Email/app/Http/Controllers/EmailController.php` | CRUD | Email config/template writes | Email setup |

## Event Dispatch

No broad explicit event-dispatch map was found in controllers. Logging side
effects are primarily model trait based through `HasActivityLogging`.
