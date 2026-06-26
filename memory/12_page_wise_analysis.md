# Page Wise Analysis

## Shared Layout

- Base authenticated layout: `resources/views/layouts-tw/app-tw.blade.php`
- Guest/auth layout: `resources/views/layouts/guest-tw.blade.php`
- Sidebar/header/footer partials: `resources/views/layouts-tw/*`
- Shared modals/partials: `resources/views/partials-tw/*`

## Pages

| Page/View | Route | Controller Method | Data Loaded | Filters | JS Used | Risk Areas | Performance |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Login | `/login` | `AuthenticatedSessionController@create` | none/login state | none | auth/session scripts | session expiry/multi-login | low |
| Dashboard | `/dashboard` | `DashbordController@index` | widgets, KPI data | date/year/widget config | chart/dashboard JS | stale KPI definitions | aggregate queries |
| PG Management Index | `/pg-management` | `PgManagementController@index` | PG rows | DataTables search/status | DataTables/custom CRUD JS | location/owner joins | indexes on status/name |
| PG Show | `/pg-management/{pg}` | `PgManagementController@show` | PG detail | none | view scripts | related counts | relation eager loading |
| Rooms | `/rooms` | `RoomController@index` | rooms, PG/category | PG/category/status | DataTables/cascade JS | capacity validation | joins |
| Room Categories | `/room-categories` | `RoomCategoryController@index` | categories | PG/status | DataTables | duplicate category names | indexes |
| Tenants | `/tenant` | `TenantController@index` | tenant list | PG/room/status/search | DataTables/select2/datepicker | multi-table user link | eager loading |
| Tenant Create/Edit | `/tenant/create`, `/tenant/{id}/edit` | create/edit | PG, room, states, cities | cascades | select2/flatpickr/file upload | proof upload and rollback | moderate |
| Tenant Show | `/tenant/{id}` | show | tenant and payments | none | page JS | missing relationships | eager loading |
| Payments | `/payments` | `PaymentController@index` | payments | tenant/PG/date/status | DataTables/datepicker | verified lock rule | date indexes |
| Complaints | `/complaints` | `ComplaintController@index` | complaints | PG/room/service/status | DataTables/cascade | state workflow missing | joins |
| Maintenance | `/maintenance` | `MaintenanceController@index` | maintenance records | status/date/PG | DataTables | work-order assumptions | joins |
| Services | `/services` | `ServiceController@index` | services | category/status | DataTables | category mismatch | low |
| Service Categories | `/service-categories` | `ServiceCategoryController@index` | categories | status/search | DataTables | duplicate names | low |
| Users | `/users` | `UserController@index` | users/roles | role/status/search | DataTables | role assignment | indexes |
| Roles | `/roles` | `RoleController@index` | roles/permissions | search | permission grid JS | permission drift | moderate |
| Menus | `/menumasters` | `MenuMasterController@index` | menu tree | parent/status | menu scripts | hierarchy corruption | order indexes |
| Settings | `/settings` | `SettingController@index` | settings | none | upload/theme JS | file handling | low |
| Email | module routes | `EmailController` | configs/templates | status/type | form JS | bad SMTP/template data | low |
| Master Data | country/state/city/currency/unit/year routes | respective controllers | rows | search/status | DataTables/cascades | dependency deletes | low/moderate |
