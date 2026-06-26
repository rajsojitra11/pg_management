# Relationship Map

## Eloquent Relationships

Confirmed from model files:

| Model | Relationship | Target | Notes |
| --- | --- | --- | --- |
| `PgManagement` | `owner()` belongsTo | `Modules\User\app\Models\User` | Owner of PG |
| `PgManagement` | `createdBy()/updatedBy()/deletedBy()` belongsTo | `User` | Audit users |
| `Room` | `pg()` belongsTo | `PgManagement` | Room parent PG |
| `Room` | `category()` belongsTo | `RoomCategory` | Room category |
| `Room` | `tenants()` hasMany | `Tenant` | Tenants assigned to room |
| `Tenant` | `user()` belongsTo | `User` | Login/user account |
| `Tenant` | `payments()` hasMany | `Payment` | Tenant payments |
| `Tenant` | `pg()` belongsTo | `PgManagement` | Assigned PG |
| `Tenant` | `room()` belongsTo | `Room` | Assigned room |
| `Tenant` | `permanentState()` belongsTo | `State` | Permanent address |
| `Tenant` | `permanentCity()` belongsTo | `City` | Permanent address |
| `Payment` | `tenant()` belongsTo | `Tenant` | Payment owner |
| `Payment` | `pg()` belongsTo | `PgManagement` | PG paid for |
| `Payment` | `room()` belongsTo | `Room` | Room paid for |
| `Complaint` | `pg()` belongsTo | `PgManagement` | Complaint PG |
| `Complaint` | `room()` belongsTo | `Room` | Complaint room |
| `Complaint` | `serviceCategory()` belongsTo | `ServiceCategory` | Complaint category |
| `Complaint` | `service()` belongsTo | `Service` | Complaint service |
| `Service` | `category()` belongsTo | `ServiceCategory` | Service category |
| `ServiceCategory` | `services()` hasMany | `Service` | Category services |
| `Country` | `createdBy()/updatedBy()/deletedBy()` belongsTo | `User` | Audit users |
| `State` | `country()` belongsTo | `Country` | State country |
| `State` | `logs()` hasMany | `StateLog` | Activity logs |
| `City` | `state()` belongsTo | `State` | City state |
| `City` | `country()` belongsTo | `Country` | City country |
| `Unit` | `logs()` hasMany | `UnitLog` | Activity logs |

## Morph Relationships

- Spatie Permission uses polymorphic `model_has_roles` and
  `model_has_permissions`.
- `HasActivityLogging` includes polymorphic helper logic for resolving relation
  labels, but module log tables are concrete entity log tables.

## Pivot Tables

- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `role_year_accesses`

## Dependency Notes

- Tenant data depends on valid PG and room data.
- Payment data depends on valid tenant, PG, and room data.
- Complaint data depends on valid PG, room, service category, and service data.
- City data depends on state and country.
- State data depends on country.
- Role-year access constrains access by year where `HasYearAccessScope` is used.
