<?php

/*
|--------------------------------------------------------------------------
| Install-Type Profile Cascade Helper
|--------------------------------------------------------------------------
|
| `profile_default($key)` returns the active profile's default value
| for a flag. Supports both nested keys ('inbound.auto_release_stock_on_pass')
| and flat keys ('auto_release_stock_on_pass') for backward compatibility
| during PR 10's migration to grouped-domain profile shape.
|
| Used as the second argument of env() in config/business.php so that
| an unset env var picks up the profile default while an explicit env
| var still wins.
|
| Example (nested, preferred):
|     env('AUTO_RELEASE_STOCK_ON_PASS', profile_default('inbound.auto_release_stock_on_pass'))
|
| Example (flat, still supported):
|     env('AUTO_RELEASE_STOCK_ON_PASS', profile_default('auto_release_stock_on_pass'))
|
| `install_type()` returns the active install-type from the INSTALL_TYPE env
| var. The companion `profile_source($key)` returns a short marker indicating
| where the EFFECTIVE value came from — used by `business:status` to render
| the audit table.
|
*/

if (! function_exists('install_type')) {
    /**
     * Return the active install type from the INSTALL_TYPE env var.
     * Defaults to 'custom' if not set.
     */
    function install_type(): string
    {
        return (string) env('INSTALL_TYPE', 'default');
    }
}

if (! function_exists('profile_default')) {
    /**
     * Return the active profile's default for a given flag.
     *
     * Lookup order:
     *   1. Nested key path (`'inbound.auto_release_stock_on_pass'`) — preferred
     *   2. Flat key — scans all groups in the active profile (backward compat)
     *   3. Same lookup against 'custom' profile (fallback)
     *   4. $hardFallback (default 0)
     *
     * Falls back to the 'custom' profile if the active profile is
     * unrecognized or the key is absent from the active profile.
     */
    function profile_default(string $key, mixed $hardFallback = 0): mixed
    {
        $active = config('profiles.active', 'default');
        $profiles = config('profiles.profiles', []);

        $found = profile_default_lookup($profiles[$active] ?? [], $key);
        if ($found !== null) {
            return $found;
        }

        $found = profile_default_lookup($profiles['default'] ?? [], $key);
        if ($found !== null) {
            return $found;
        }

        return $hardFallback;
    }
}

if (! function_exists('profile_default_lookup')) {
    /**
     * Internal — lookup a key in a single profile array, supporting both
     * nested ('inbound.foo') and flat ('foo') keys.
     *
     * @param  array<string, mixed>  $profile
     * @return mixed|null null if not found
     */
    function profile_default_lookup(array $profile, string $key): mixed
    {
        // Nested form: 'inbound.auto_release_stock_on_pass'
        if (str_contains($key, '.')) {
            [$group, $flag] = explode('.', $key, 2);
            if (isset($profile[$group]) && is_array($profile[$group]) && array_key_exists($flag, $profile[$group])) {
                return $profile[$group][$flag];
            }

            return null;
        }

        // Flat form: 'auto_release_stock_on_pass' — scan all groups for it.
        foreach ($profile as $group => $values) {
            if (! is_array($values)) {
                continue;
            }
            if (array_key_exists($key, $values)) {
                return $values[$key];
            }
        }

        return null;
    }
}

if (! function_exists('profile_source')) {
    /**
     * Return a marker describing where the effective value of `$envVar`
     * came from. Used by `business:status` and `business:flags`.
     *
     * Returns one of:
     *   - "env=$envVar"      → explicit env var set
     *   - "profile=$name"    → active profile supplied the default
     *   - "default"          → fell through to hardcoded fallback
     */
    function profile_source(string $envVar, string $profileKey): string
    {
        if (env($envVar) !== null) {
            return "env={$envVar}";
        }

        $active = config('profiles.active', 'default');
        $profiles = config('profiles.profiles', []);

        $found = profile_default_lookup($profiles[$active] ?? [], $profileKey);
        if ($found !== null) {
            return "profile={$active}";
        }

        return 'default';
    }
}
