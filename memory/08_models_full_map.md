# Models Full Map

## Model Inventory

| Model | File | Fillable/Casts Source | Relationships Source | Observers |
| --- | --- | --- | --- | --- |
| `App\Models\User` | `app/Models/User.php` | model file | model file | none found |
| `App\Models\SystemEvent` | `app/Models/SystemEvent.php` | model file | model file | none found |
| `PgManagement` | `Modules/PgManagement/app/Models/PgManagement.php` | model file | owner/audit users | none found |
| `RoomCategory` | `Modules/Room/app/Models/RoomCategory.php` | model file | PG/services as defined | none found |
| `Room` | `Modules/Room/app/Models/Room.php` | model file | PG, category, tenants | none found |
| `Tenant` | `Modules/Tenant/app/Models/Tenant.php` | model file | user, payments, PG, room, address state/city | none found |
| `Payment` | `Modules/Payment/app/Models/Payment.php` | model file | tenant, PG, room, audit users | none found |
| `Complaint` | `Modules/Complaint/app/Models/Complaint.php` | model file | PG, room, service category, service, user | none found |
| `Maintenance` | `Modules/Maintenance/app/Models/Maintenance.php` | model file | model file | none found |
| `ServiceCategory` | `Modules/Service/app/Models/ServiceCategory.php` | model file | services | none found |
| `Service` | `Modules/Service/app/Models/Service.php` | model file | category | none found |
| `Subscription` | `Modules/Subscription/app/Models/Subscription.php` | model file | audit users | none found |
| `Country` | `Modules/Country/app/Models/Country.php` | model file | audit users | none found |
| `State` | `Modules/State/app/Models/State.php` | model file | country, logs, audit users | none found |
| `City` | `Modules/City/app/Models/City.php` | model file | state, country, logs, audit users | none found |
| `Currency` | `Modules/Currency/app/Models/Currency.php` | model file | model file | none found |
| `Unit` | `Modules/Unit/app/Models/Unit.php` | model file | logs, audit users | none found |
| `Year` | `Modules/Year/app/Models/Year.php` | model file | model file | none found |
| `Role` | `Modules/Role/app/Models/Role.php` | model file | permissions/year access | none found |
| `RoleYearAccess` | `Modules/Role/app/Models/RoleYearAccess.php` | model file | role/year | none found |
| `Setting` | `Modules/Setting/app/Models/Setting.php` | model file | model file | none found |
| `MenuMaster` | `Modules/MenuMaster/app/Models/MenuMaster.php` | model file | parent, children, recursive children, permissions, audit users | none found |
| `EnvVariable` | `Modules/EnvVariable/app/Models/EnvVariable.php` | model file | model file | none found |
| `DashboardWidget` | `Modules/Dashbord/app/Models/DashboardWidget.php` | model file | dashboard config | none found |
| `RoleDashboardConfig` | `Modules/Dashbord/app/Models/RoleDashboardConfig.php` | model file | role/widget | none found |
| `UserDashboardConfig` | `Modules/Dashbord/app/Models/UserDashboardConfig.php` | model file | user/widget | none found |
| `EmailConfig` | `Modules/Email/app/Models/EmailConfig.php` | model file | model file | none found |
| `EmailTemplate` | `Modules/Email/app/Models/EmailTemplate.php` | model file | model file | none found |
| `Noticeboard` | `Modules/Noticeboard/app/Models/Noticeboard.php` | model file | model file | none found |

## Common Traits

- `HasPublicId`: route key and public id lookup behavior.
- `HasActivityLogging`: automatic activity log behavior.
- `SoftDeletes`: soft deletion where used.
- `HasFactory`: factory support.

## Accessors, Mutators, Scopes

- `HasPublicId` provides `scopeByPublicId` and `scopeByAnyKey`.
- `HasYearAccessScope` provides `scopeVisibleForYearAccess`.
- Additional accessors/mutators/scopes must be read from individual model files
  before modifying each module.
