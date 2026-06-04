<?php

namespace App\Logging;

use Illuminate\Support\Facades\Config;
use Nwidart\Modules\Facades\Module;

/**
 * Auto-registers a `daily` Monolog channel for every module discovered by
 * nwidart/laravel-modules. After boot, any module may call
 * `Log::channel('<modulename>')->info(...)` without per-module config.
 *
 * Each channel writes to `storage/logs/<slug>/<slug>-{date}.log` and rotates
 * by `logging.module_channels.retention_days` (default 90, env override
 * `LOG_MODULE_RETENTION_DAYS`).
 *
 * Channels already defined in `config/logging.php` are NOT overridden — that
 * lets a test or an operator pin a specific module to a different driver.
 */
class ModuleChannelRegistrar
{
    public static function register(): void
    {
        $retention = (int) config('logging.module_channels.retention_days', 90);
        $level = (string) config('logging.module_channels.level', 'debug');
        $replacePlaceholders = (bool) config('logging.module_channels.replace_placeholders', true);

        foreach (self::moduleSlugs() as $slug) {
            $key = "logging.channels.{$slug}";
            $existing = Config::get($key);

            // Only skip if a real channel config (an array) was already set —
            // null values left behind by Config::offsetUnset() should be
            // re-registered, not respected as "already configured."
            if (is_array($existing)) {
                continue;
            }

            Config::set($key, [
                'driver' => 'daily',
                'path' => storage_path("logs/{$slug}/{$slug}.log"),
                'level' => $level,
                'days' => $retention,
                'replace_placeholders' => $replacePlaceholders,
            ]);
        }
    }

    /**
     * Lower-cased module names from the nwidart registry. The `Log::channel()`
     * lookup is case-sensitive, and modules across this codebase are referenced
     * by their lower-case slug (matching `config('app.module_slug')` patterns).
     */
    private static function moduleSlugs(): array
    {
        if (! class_exists(Module::class)) {
            return [];
        }

        return array_map(
            fn ($module) => strtolower($module->getName()),
            array_values(Module::all()),
        );
    }
}
