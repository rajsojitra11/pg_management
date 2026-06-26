# Architecture Overview

## Laravel Version

- Laravel framework: `^13.1`
- PHP: `^8.4`
- Frontend: Tailwind CSS 4 and Vite 8

## Packages Used

Runtime packages from `composer.json`:

- `barryvdh/laravel-dompdf`
- `jenssegers/agent`
- `lab404/laravel-impersonate`
- `laravel/framework`
- `laravel/sanctum`
- `laravel/tinker`
- `nwidart/laravel-modules`
- `spatie/laravel-permission`
- `yajra/laravel-datatables-oracle`

Development packages include Laravel Breeze, Boost, Pail, Pint, Sail, Pest, and
Ignition.

## Folder Structure

```text
app/                 Shared app controllers, middleware, traits, services
bootstrap/           Laravel bootstrap and provider registration
config/              App, database, modules, permission, business, session config
database/            Root migrations, seeders, factories
Modules/             Feature modules
public/              Public entry point and static assets
resources/           Shared Blade views, components, JS, CSS
routes/              Root web/api/auth/console routes
storage/             Runtime storage, compiled views, fonts
tests/               Pest feature and unit tests
```

## Queue Usage

- `QUEUE_CONNECTION` defaults to `database`.
- `jobs`, `job_batches`, and `failed_jobs` are created by the User module
  migrations.
- Composer dev script runs `php artisan queue:listen --tries=1`.
- No explicit `app/Jobs` classes were found in the current tree.

## Event Flow

- Providers exist per module, commonly `EventServiceProvider`,
  `RouteServiceProvider`, and module service provider.
- User authentication logging uses `Modules\User\app\Listeners\LogUserAuthentication`.
- Model activity logging is trait-driven through `HasActivityLogging`.

## High-Level Module Interaction

```text
Login -> User -> Role/Permission
Setting -> Layout/Branding/Theme
PgManagement -> Room -> Tenant -> Payment
PgManagement -> Room -> Complaint -> Service
PgManagement -> Maintenance
Dashbord -> PG/Tenant/Payment/Complaint dashboard data
Email -> Templates/Configs/Rent reminders
MenuMaster -> Sidebar/navigation structure
Country/State/City/Currency/Unit/Year -> shared master data
```
