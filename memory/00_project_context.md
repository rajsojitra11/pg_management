# Project Context

## System Purpose

PG Management is a Laravel ERP-style application for managing paying guest
properties and related operations: PG master data, rooms, tenants, payments,
complaints, maintenance, subscriptions, users, roles, menus, settings, email,
noticeboards, and dashboards.

## Business Rules

- A PG record is the parent business entity for rooms, tenants, payments,
  complaints, and maintenance records.
- Rooms belong to a PG and a room category.
- Tenants belong to a PG and room, and tenant creation can create a linked user
  account and user profile.
- Payments belong to tenant, PG, and room.
- Complaints belong to PG, room, service category, and service.
- Most master and transaction records use status values instead of hard removal.
- Most business entities use soft deletes and activity log side tables.
- Public URLs should use `public_id` route keys where the model uses
  `App\Traits\HasPublicId`.
- Root authentication flow is owned by the `Login` module, not default Breeze
  routes.

## Core Constraints

- PHP 8.4 and Laravel 13 are required.
- Database sessions are enabled by default; migrations must run before login
  flows work.
- Module ownership is enforced by folder structure under `Modules/*`.
- Shared root routes are intentionally thin.
- Most module web routes require `auth` and `verified`.
- API routes are versioned under `api/v1`.
- Permission checks use Spatie Permission middleware and role/permission data.

## Non-Negotiable Logic Rules

- Do not bypass FormRequest validation for create/update/delete workflows.
- Do not manually assign route keys when `HasPublicId` should generate them.
- Do not delete audit logging logic from models that already use
  `HasActivityLogging`.
- Do not break `created_by`, `updated_by`, and `deleted_by` tracking conventions.
- Do not move authentication out of the `Login` module without a migration plan.
- Do not introduce frontend behavior that conflicts with existing DataTables,
  Select2, Flatpickr, and ERP helper scripts.

## Single Source Of Truth Rules

- Database structure: migrations under `database/migrations` and
  `Modules/*/database/migrations`.
- Module routes: each module's `routes/web.php` and `routes/api.php`.
- Validation rules: module FormRequest classes under `app/Http/Requests`.
- Permissions and roles: Spatie permission tables plus module seeders.
- UI layout: `resources/views/layouts-tw`.
- Shared lookup contract: `App\Http\Controllers\LookupController`.
- Activity logs: `App\Traits\HasActivityLogging` and module log models/tables.
