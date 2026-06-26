# Database Design

## Tables And Migration Sources

The database is defined by root migrations and module migrations. This list is
the schema source inventory.

| Table / Concern | Purpose | Migration Source | Soft Deletes |
| --- | --- | --- | --- |
| users | User accounts | `Modules/User/database/migrations/*create_users_table.php` and user alter migrations | No base soft delete found |
| user_profile | User profile/preferences extension | `Modules/User/database/migrations/*create_user_profile_table.php` and profile alter migrations | Migration-defined; verify before destructive changes |
| user_hierarchies | User hierarchy assignments | `Modules/User/database/migrations/*create_user_hierarchies_table.php` | Migration-defined; verify before destructive changes |
| user_logs | User activity logs | `Modules/User/database/migrations/*create_user_logs_table.php` | No |
| user_preferences | User preference rows | `Modules/User/database/migrations/*create_user_preferences_table.php` | Migration-defined; verify before destructive changes |
| cache, cache_locks | Laravel cache tables | `Modules/User/database/migrations/*create_cache_table.php` | No |
| jobs, job_batches, failed_jobs | Queue tables | `Modules/User/database/migrations/*create_jobs_table.php` | No |
| password_reset_tokens | Password reset tokens | `Modules/User/database/migrations/*create_password_reset_tokens_table.php` | No |
| personal_access_tokens | Sanctum tokens | `Modules/User/database/migrations/*create_personal_access_tokens_table.php` | No |
| roles, permissions, model_has_roles, model_has_permissions, role_has_permissions | Spatie permissions | `Modules/User/database/migrations/*create_permission_tables.php` plus Role migrations | No |
| role_logs | Role audit logs | `Modules/Role/database/migrations/*create_role_logs_table.php` | No |
| role_year_accesses | Role-year access matrix | `Modules/Role/database/migrations/*create_role_year_accesses_table.php` | Migration-defined; verify before destructive changes |
| settings, setting_logs | Company/app settings and logs | `Modules/Setting/database/migrations/*` | Model uses soft deletes |
| countries, country_logs | Country master and logs | `Modules/Country/database/migrations/*` | Model uses soft deletes |
| states, state_logs | State master and logs | `Modules/State/database/migrations/*` | Model uses soft deletes |
| cities, city_logs | City master and logs | `Modules/City/database/migrations/*` | Model uses soft deletes |
| currencies, currency_logs | Currency master and logs | `Modules/Currency/database/migrations/*` | Model uses soft deletes |
| units, unit_logs | Unit master and logs | `Modules/Unit/database/migrations/*` | Model uses soft deletes |
| years, year_logs | Financial/calendar year master and logs | `Modules/Year/database/migrations/*` | Model uses soft deletes |
| menu_masters, menu_master_logs | Menu tree and logs | `Modules/MenuMaster/database/migrations/*` | Model uses soft deletes |
| env_variables, env_variable_logs | Editable environment variables and logs | `Modules/EnvVariable/database/migrations/*` | Model convention |
| dashboard_widgets | Dashboard widget definitions | `Modules/Dashbord/database/migrations/*dashboard_widgets*` | Migration-defined; verify before destructive changes |
| role_dashboard_configs | Role dashboard config | `Modules/Dashbord/database/migrations/*role_dashboard_configs*` | Migration-defined; verify before destructive changes |
| user_dashboard_configs | User dashboard config | `Modules/Dashbord/database/migrations/*user_dashboard_configs*` | Migration-defined; verify before destructive changes |
| subscriptions, subscription_logs | Subscription records and logs | `Modules/Subscription/database/migrations/*` | Model uses soft deletes |
| pg_management, pg_management_logs | PG master and logs | `Modules/PgManagement/database/migrations/*` | Model uses soft deletes |
| pg_room_categories, pg_room_category_logs | Room categories and logs | `Modules/Room/database/migrations/*room_categories*` | Model uses soft deletes |
| pg_rooms, pg_room_logs | PG rooms and logs | `Modules/Room/database/migrations/*pg_rooms*` | Model uses soft deletes |
| tenants, tenant_logs | Tenant records and logs | `Modules/Tenant/database/migrations/*` | Model uses soft deletes |
| payments, payment_logs | Payment records and logs | `Modules/Payment/database/migrations/*` | Yes |
| service_categories, service_category_logs | Service categories and logs | `Modules/Service/database/migrations/*service_categories*` | Model uses soft deletes |
| services, services_logs | Service master and logs | `Modules/Service/database/migrations/*services*` | Model uses soft deletes |
| complaints, complaint_logs | Complaints and logs | `Modules/Complaint/database/migrations/*` | Model uses soft deletes |
| maintenances, maintenance_logs | Maintenance records and logs | `Modules/Maintenance/database/migrations/*` | Model uses soft deletes |
| email_configs, email_config_logs | Email provider/config records and logs | `Modules/Email/database/migrations/*email_config*` | Model convention |
| email_templates, email_template_logs | Email templates and logs | `Modules/Email/database/migrations/*email_template*` | Model convention |
| noticeboards, noticeboard_logs | Noticeboard posts and logs | `Modules/Noticeboard/database/migrations/*` | Model convention |
| notifications | App notifications | `database/migrations/*create_notifications_table.php` | No |
| system_events | System event audit rows | `database/migrations/*create_system_events_table.php` | No |

## Index Strategy

- `public_id` columns are unique on public-facing business tables.
- Log tables usually index entity id plus `created_at`, user id plus activity,
  and activity plus `created_at`.
- Foreign-key columns are used for domain dependencies such as tenant, PG, room,
  service category, and service.
- Spatie permission tables carry package-defined role/permission indexes.

## Foreign Keys

Common dependency directions:

- `tenants.user_id -> users.id`
- `tenants.pg_id -> pg_management.id`
- `tenants.room_id -> pg_rooms.id` where constrained by code/schema
- `payments.tenant_id -> tenants.id`
- `payments.pg_id -> pg_management.id`
- `payments.room_id -> pg_rooms.id`
- `complaints.pg_id -> pg_management.id`
- `complaints.room_id -> pg_rooms.id`
- `complaints.service_category_id -> service_categories.id`
- `complaints.service_id -> services.id`
- `cities.state_id -> states.id`
- `cities.country_id -> countries.id`
- `states.country_id -> countries.id`

## Enum Definitions

The codebase uses string status fields rather than PHP enum classes in the
reviewed files. Known values from requests/migrations include:

- PG status: `active`, `inactive`
- Tenant status: `Active`, `Inactive`
- Payment method: `Cash`, `Bank Transfer`, `Cheque`, `UPI`, `Other`
- Payment verified: `pending`, `verified`
- Complaint status default: `pending`
- Subscription status default: `active`
- Subscription payment status default: `pending`
