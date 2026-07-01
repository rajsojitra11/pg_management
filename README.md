# PG Management

PG Management is a modular Laravel ERP application for managing paying guest
properties, rooms, tenants, payments, complaints, maintenance, subscriptions,
users, roles, menus, settings, and dashboard reporting.

The application is built with Laravel 13, PHP 8.4, Tailwind CSS 4, Vite, Pest,
Spatie Permission, Yajra DataTables, Sanctum, DomPDF, and
`nwidart/laravel-modules`.

## Requirements

- PHP 8.4+
- Composer
- Node.js and npm
- MySQL or MariaDB by default
- A web server or `php artisan serve`

PostgreSQL configuration is also documented in `.env.example`, but the default
project configuration uses MySQL.

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database and mail settings in `.env`, then run:

```bash
php artisan migrate --seed
npm run build
```

For a full first-time setup, the Composer script can also be used:

```bash
composer run setup
```

## Development

Run the Laravel server, queue worker, logs, and Vite dev server together:

```bash
composer run dev
```

Or run services separately:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

## Testing

The project uses Pest.

```bash
composer run test
```

Run a specific test file or filter when working on a focused change:

```bash
php artisan test --compact tests/Feature/TenantTest.php
php artisan test --compact --filter=tenant
```

Format PHP changes with Pint:

```bash
vendor/bin/pint --dirty --format agent
```

## Core Modules

The application uses `nwidart/laravel-modules`. Each feature module owns its
routes, controllers, requests, models, migrations, seeders, views, language
files, assets, and service providers.

Important modules:

- `Login` - authentication flow and logout
- `Dashbord` - dashboard pages, KPI data, chart data, widget configuration
- `PgManagement` - PG property master data
- `Room` - room categories and PG rooms
- `Tenant` - tenant onboarding, user/profile creation, bed allocation
- `Payment` - tenant payment entries and verification
- `Complaint` - complaint registration and tracking
- `Maintenance` - maintenance records
- `Service` - service categories and services used by complaints
- `Subscription` - subscription records
- `User` - user management, profile, password, preferences
- `Role` - roles, permissions, and year access
- `MenuMaster` - dynamic menu management
- `Setting` - application/company settings
- `Email` - email configuration, templates, and rent reminders
- `Country`, `State`, `City`, `Currency`, `Unit`, `Year` - master data
- `EnvVariable` - environment variable management
- `Noticeboard` - notices

## Main Business Flow

The primary PG workflow is:

```text
PgManagement -> RoomCategory -> Room -> Tenant -> Payment
                                      -> Complaint
                                      -> Maintenance
```

Supporting master data includes countries, states, cities, services, units,
currencies, years, users, roles, menus, and application settings.

## Routing

The root route redirects to login. Authentication routes are owned by the
`Login` module rather than the default Breeze controllers.

Shared root routes include:

- AJAX lookup endpoints for countries, states, cities, PGs, rooms, tenants, and complaints
- session extension, heartbeat, status, warning, and configuration endpoints
- impersonation endpoints for privileged users
- generated ERP CSS configuration

Most module routes are protected by:

```php
['auth', 'verified']
```

## Data And Model Conventions

Most business models follow these conventions:

- public route keys through `App\Traits\HasPublicId`
- soft deletes
- `created_by`, `updated_by`, and `deleted_by` audit columns
- module-specific log tables
- activity logging through `App\Traits\HasActivityLogging`
- FormRequest validation for create, update, and delete actions
- DataTables-powered index pages

Common log tables include:

- `pg_management_logs`
- `pg_room_logs`
- `tenant_logs`
- `payment_logs`
- `complaint_logs`
- `maintenance_logs`

## Frontend

The frontend is Blade-based with Tailwind CSS 4 and Vite. Shared Tailwind ERP
layout files live in `resources/views/layouts-tw`, while reusable partials live
in `resources/views/partials-tw`.

Shared JavaScript assets live under:

- `resources/js`
- `public/assets-tw/js`
- `public/assets/custom`

Common frontend libraries include Alpine.js, jQuery, DataTables, Select2,
Flatpickr, Chart.js, and custom ERP helpers.

## Useful Artisan Commands

```bash
php artisan route:list --except-vendor
php artisan migrate
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

Project-specific commands include audits, environment checks, schema validation,
module audit, business profile tools, and app refresh commands. List them with:

```bash
php artisan list
```

## Environment Notes

Important `.env` values include:

- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`
- `SESSION_DRIVER`, `SESSION_LIFETIME`
- `QUEUE_CONNECTION`
- `ENABLE_MULTI_SESSION_LOGOUT`

The default session driver is `database`, so migrations must be run before using
login/session flows.

## Project Structure

```text
app/                 Shared application code, middleware, traits, services
bootstrap/           Laravel bootstrap files and provider registration
config/              Application, package, business, session, and module config
database/            Root migrations, factories, and seeders
Modules/             Modular business features
public/              Public assets and entry point
resources/           Shared Blade layouts, components, CSS, and JS
routes/              Thin root web/api/auth routes
storage/             Laravel storage
tests/               Pest feature and unit tests
```

## Development Rules

- Follow the existing module structure and sibling file conventions.
- Use FormRequest classes for validation.
- Prefer named routes and module-local language keys.
- Reuse shared Blade partials and ERP JavaScript helpers before adding new ones.
- Add or update tests for behavior changes.
- Run the focused test set and Pint before finalizing PHP changes.



## Command for creating migration file in module:
 => php artisan module:make-migration create_units_table Unit
 => php artisan module:make-migration add_is_base_column_to_currencies_table Currency

 ## Command for seed user permison :
 => php artisan db:seed --class=Database\Seeders\PermissionTableSeeder
 => php artisan db:seed --class=Modules\User\Database\Seeders\PermissionTableSeeder

 ## Comand for all seed of module:
 => php artisan module:seed Module_name_here

 ## comand for run migration in module:
 php artisan module:migrate Unit

 ## command for disable or enable module:
 php artisan module:enable PrefixMaster
 php artisan module:disable ByProduct

## command for Make Model
 php artisan module:make-model PriceList --migration PriceList
 
 php artisan module:make-controller ByProductRaw Rawmaterial

 php artisan module:make Unit

 php artisan make:export RawmaterialExport --model=Modules\Rawmaterial\Entities\Rawmaterial

 php artisan module:make-with-model SalesQuotation


 ## **🏗️ MODULE ARCHITECTURE REQUIREMENTS**

### **📁 Mandatory Directory Structure**

```
Modules/{ModuleName}/
├── app/
│   ├── Helpers/
│   │   └── {ModuleName}Helper.php (MANDATORY - Module utilities)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── {ModuleName}Controller.php
│   │   └── Requests/
│   │       ├── Store{ModuleName}Request.php
│   │       └── Update{ModuleName}Request.php
│   ├── Models/
│   │   └── {ModuleName}.php
│   ├── Providers/
│   │   └── {ModuleName}ServiceProvider.php
│   └── Traits/ (optional - for module-specific logging)
│       └── Logs{ModuleName}Activity.php
├── database/
│   ├── migrations/
│   │   └── {timestamp}_create_{table_name}_table.php
│   ├── factories/
│   │   └── {ModuleName}Factory.php
│   └── seeders/
│       ├── {ModuleName}DatabaseSeeder.php
│       └── {ModuleName}PermissionSeeder.php (MANDATORY - Module permissions)
├── lang/
│   └── en/
│       └── message.php          (Language translations)
├── resources/
│   └── views/
│       ├── index.blade.php      (List view)
│       ├── create.blade.php     (Create form)
│       ├── edit.blade.php       (Edit form)
│       └── show.blade.php       (Detail view)
├── routes/
│   └── web.php
├── tests/
│   └── Feature/
│       └── {ModuleName}ControllerTest.php
├── composer.json
├── module.json
├── package.json
└── vite.config.js
