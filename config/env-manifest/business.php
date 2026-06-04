<?php

/*
 * Manifest for the BUSINESS group — env vars whose effective value is
 * resolved via the env → profile → fallback cascade (see config/profiles.php).
 *
 * Boilerplate set: minimal — only the variables actually consumed by surviving
 * modules. Add new entries here as your project's modules ship new flags.
 */

return [
    'INSTALL_TYPE' => [
        'description' => 'Selects the active profile from config/profiles.php.',
        'long' => 'The boilerplate ships with one profile (`default`). Add more profiles in config/profiles.php and set this var to switch.',
        'type' => 'enum',
        'allowed' => ['default'],
        'default' => 'default',
        'criticality' => 'high',
        'business_relevant' => true,
    ],
    'FY_START_MONTH' => [
        'description' => 'Month the fiscal year begins (1=January, 4=April Indian default).',
        'type' => 'int',
        'allowed' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        'default' => 4,
        'criticality' => 'low',
        'business_relevant' => true,
    ],
    'ENABLE_MULTI_SESSION_LOGOUT' => [
        'description' => 'Logging in from a new device automatically logs out other active sessions.',
        'type' => 'bool',
        'default' => 1,
        'criticality' => 'medium',
        'business_relevant' => true,
    ],
];
