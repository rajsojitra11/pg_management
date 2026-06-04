<?php

/*
|--------------------------------------------------------------------------
| Install-Type Profile Cascade
|--------------------------------------------------------------------------
|
| One env var (`INSTALL_TYPE`) selects the active profile. Profile
| defaults are applied via the `profile_default($key)` autoloaded helper.
|
| Cascade order, highest precedence first:
|   1. Explicit env var
|   2. Active profile's default
|   3. Hardcoded fallback in the consumer (e.g. config('business.X', 0))
|
| `php artisan business:status` prints every business config value with
| its source marker — `[env=VAR]` / `[profile=name]` / `[default]` —
| so audits can see exactly where each effective value came from.
|
| The boilerplate ships with one profile: `default`. Add more profiles
| as your project grows (e.g. `multi_tenant`, `single_user`, `enterprise`).
|
*/

return [

    /*
    | Active install-type selector. The boilerplate ships with one profile
    | ('default'). To add more, declare them in `profiles` below and set
    | `INSTALL_TYPE=<name>` in `.env`.
    */
    'active' => env('INSTALL_TYPE', 'default'),

    /*
    | Per-install-type defaults. Each profile MUST declare every domain
    | it uses (empty arrays are fine for unused domains).
    */
    'profiles' => [

        'default' => [
            'other' => [
                'fy_start_month' => 4,
                'multi_session_logout' => 1,
            ],
            'modules' => [
                'recommend_enabled' => [
                    'Login', 'User', 'Role', 'MenuMaster', 'Setting',
                    'Dashbord', 'EnvVariable', 'Installer',
                    'Country', 'State', 'City', 'Currency', 'Unit', 'Year',
                    'Item',
                ],
                'recommend_disabled' => [],
            ],
        ],

    ],

];
