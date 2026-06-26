# Roles RBAC Matrix

## Matrix

The project uses Spatie Permission. Exact role-permission ownership is seed-data
driven and should be verified against role/user seeders and database content.

| Role | Create | Edit | Approve | Issue | Override | Delete | Close |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Super_Admin | Yes | Yes | Permission-dependent; payment verification exists | Permission-dependent; complaint creation exists | Yes | Yes | Permission-dependent; no universal close workflow |
| Admin | Module-permission based | Module-permission based | Permission based | Permission based | No unless granted | Permission based | Permission based |
| Manager | Permission based | Permission based | Permission based | Permission based | No unless granted | Restricted | Permission based |
| Staff/User | Restricted | Restricted | No by default | Permission based | No | No by default | Permission based |
| Tenant | Tenant-facing actions only if exposed | Own profile/payment-facing actions only if exposed | No | Complaint issue if exposed | No | No | No |

## Permission Source

- Permission tables are created by
  `Modules/User/database/migrations/2025_01_01_000040_121038_create_permission_tables.php`.
- Role extensions are under `Modules/Role/database/migrations`.
- Controllers mostly use route middleware and app-level access helpers rather
  than inline role matrices.

## Lock Rules After Approval

No universal approval lock state machine was found. Implemented lock-like rules:

- Payment has `verified` status and a `toggleVerified` route.
- Current payment verification can toggle `pending` and `verified` both ways.
- No controller-level lock was verified that blocks editing/deleting verified
  payments. Treat verified payment locking as a required future policy if the
  business expects approval immutability.
- No work-order approval lock workflow exists in this codebase.
