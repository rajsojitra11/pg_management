# Permissions List

## Source

Permissions are stored with Spatie Permission tables. Exact permissions and role
mapping are seed/database driven.

## Permission Tables

- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`
- `role_year_accesses`

## Expected Permission Areas

Permissions should exist or be seeded for:

- PG management
- Rooms
- Tenants
- Payments
- Complaints
- Maintenance
- Services
- Subscriptions
- Users
- Roles
- Menus
- Settings
- Email
- Env variables
- Noticeboards
- Master data
- Dashboard settings

## Seeder Mapping

Review module seeders under `Modules/*/database/seeders` and the root
`database/seeders/DatabaseSeeder.php` for actual permission creation. Do not
hardcode assumptions from this file into authorization logic.
