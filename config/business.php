<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business Logic Configuration
    |--------------------------------------------------------------------------
    |
    | Boilerplate-level business feature flags. Resolves through the
    | env → profile → fallback cascade. See `config/profiles.php` and
    | `app/Helpers/profile_helper.php` for the resolution logic.
    |
    */

    'default_country' => (int) env('DEFAULT_COUNTRY', 1),

    'company_name' => env('COMPANY_NAME', 'Company'),
    'live_app_domain' => env('PRODUCTION_DOMAIN_URL', null),
    'system_date_format' => env('SYSTEM_DATE_FORMAT', 'd-m-Y h:i:s'),
    'system_date_only_format' => explode(' ', env('SYSTEM_DATE_FORMAT', 'd-m-Y h:i:s'))[0],
    'fy_start_month' => (int) env('FY_START_MONTH', profile_default('other.fy_start_month')),

    'delivery_challan_prefix' => env('DELIVERY_CHALLAN_PREFIX', 'VDC'),

    /*
    |--------------------------------------------------------------------------
    | Theme Migration (Development Aid)
    |--------------------------------------------------------------------------
    | show_converted_modules: When 1, shows a checkmark beside sidebar menu
    | items whose module has been converted to the Tailwind theme.
    | converted_modules: List of module names (lowercase) that have been fully
    | converted. Update this array each time a module conversion is completed.
    */
    'show_converted_modules' => env('SHOW_CONVERTED_MODULES', 0),
    'converted_modules' => [
        'city',
        'client',
        'country',
        'currency',
        'dashbord',
        'deliverychallan',
        'envvariable',
        'item',
        'jobcardreport',
        'jobsize',
        'login',
        'machine',
        'menumaster',
        'paper',
        'papercoating',
        'paperfinish',
        'papergsm',
        'platedetail',
        'postpress',
        'printing',
        'printingformat',
        'role',
        'setting',
        'sheetsize',
        'state',
        'unit',
        'user',
        'vendor',
        'year',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo Background Configuration
    |--------------------------------------------------------------------------
    | Controls the background color behind the logo in different placements.
    | Accepts any CSS color value: hex (#ffffff), rgb(), rgba(), or 'transparent'.
    */
    'logo' => [
        // Light mode / always-dark surfaces
        'bg_login_hero' => env('LOGO_BG_LOGIN_HERO', 'rgba(255,255,255,0.15)'),
        'bg_login_card' => env('LOGO_BG_LOGIN_CARD', 'transparent'),
        'bg_horizontal' => env('LOGO_BG_HORIZONTAL', 'transparent'),
        'bg_sidebar' => env('LOGO_BG_SIDEBAR', 'transparent'),
        'bg_sidebar_sm' => env('LOGO_BG_SIDEBAR_SM', 'transparent'),
        'bg_mobile' => env('LOGO_BG_MOBILE', 'transparent'),
        // Dark mode overrides
        'bg_horizontal_dark' => env('LOGO_BG_HORIZONTAL_DARK', '#ffffff'),
        'bg_sidebar_dark' => env('LOGO_BG_SIDEBAR_DARK', '#ffffff'),
        'bg_sidebar_sm_dark' => env('LOGO_BG_SIDEBAR_SM_DARK', '#ffffff'),
        'bg_mobile_dark' => env('LOGO_BG_MOBILE_DARK', '#ffffff'),
        'bg_login_card_dark' => env('LOGO_BG_LOGIN_CARD_DARK', 'transparent'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Email Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'custom_from' => env('CUSTOM_EMAIL_FROM', null),
        'custom_from_name' => env('CUSTOM_EMAIL_FROM_NAME', null),
    ],

];
