<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `php artisan business:flags` — discovery + audit listing of every business
 * flag, grouped by domain, with effective value + source marker.
 *
 * Companion to `business:status`: this version walks the active profile's
 * grouped-domain shape and reports every flag the profile defines, even
 * those without explicit env vars set.
 */
class BusinessFlagsCommand extends Command
{
    protected $signature = 'business:flags {--json} {--strict : Exit non-zero when drift is detected (CI gate)}';

    protected $description = 'List every business flag grouped by domain with effective value + source marker.';

    /**
     * Map (group, flag_key) → ENV_VAR for source resolution.
     *
     * @var array<string, array<string, string>>
     */
    private const ENV_VAR_MAP = [
        'inbound' => [
            'auto_release_stock_on_pass' => 'AUTO_RELEASE_STOCK_ON_PASS',
            'auto_reject_stock_on_fail' => 'AUTO_REJECT_STOCK_ON_FAIL',
        ],
        'production' => [
            'requires_route' => 'PRODUCTION_REQUIRES_ROUTE',
            'auto_create_test_on_stage' => 'AUTO_CREATE_TEST_ON_PRODUCTION_STAGE',
        ],
        'customer' => [
            'specification_dimension_enabled' => 'SPECIFICATION_CUSTOMER_DIMENSION_ENABLED',
        ],
        'lab' => [
            'sample_storage_required' => 'SAMPLE_STORAGE_REQUIRED',
            'chain_of_custody_required' => 'CHAIN_OF_CUSTODY_REQUIRED',
            'coa_auto_release_on_pass' => 'COA_AUTO_RELEASE_ON_PASS',
            'test_method_traceability' => 'TEST_METHOD_TRACEABILITY',
        ],
    ];

    public function handle(): int
    {
        $active = config('profiles.active', 'custom');
        $profile = config("profiles.profiles.{$active}", []);

        $rows = [];
        $warnings = [];

        foreach (self::ENV_VAR_MAP as $group => $flags) {
            foreach ($flags as $flagKey => $envVar) {
                $profileKey = "{$group}.{$flagKey}";
                $envValue = env($envVar);
                $profileValue = profile_default($profileKey);

                $effective = $envValue !== null ? $envValue : $profileValue;
                $source = $envValue !== null
                    ? "env={$envVar}"
                    : (isset($profile[$group][$flagKey]) ? "profile={$active}" : 'default');

                $rows[] = [
                    'group' => ucfirst($group),
                    'flag' => $flagKey,
                    'env_var' => $envVar,
                    'effective' => $this->fmt($effective),
                    'source' => $source,
                ];

                // Drift: explicit env value matches profile default — suggest cleanup
                if ($envValue !== null && $profileValue !== null && (string) $envValue === (string) $profileValue) {
                    $warnings[] = "{$envVar} is explicit in .env but matches profile default for '{$active}' — consider removing.";
                }
            }
        }

        // --strict adds two extra drift checks beyond the per-row "explicit
        // env matches profile default" suggestion above:
        //   1. ENV_VAR_MAP keys that have no corresponding profile entry
        //      (the registry promised a flag the profile cascade never delivers)
        //   2. .env keys that are set but missing from .env.example
        //      (deployments will surprise-skip them on fresh installs)
        $strictWarnings = [];
        foreach (self::ENV_VAR_MAP as $group => $flags) {
            foreach ($flags as $flagKey => $envVar) {
                if (! isset($profile[$group][$flagKey])) {
                    $strictWarnings[] = "Profile '{$active}' has no entry for {$group}.{$flagKey} — registry promises a flag the cascade never delivers.";
                }
            }
        }
        $strictWarnings = array_merge($strictWarnings, $this->envExampleDrift());

        if ($this->option('json')) {
            $this->line(json_encode([
                'active_profile' => $active,
                'rows' => $rows,
                'warnings' => $warnings,
                'strict_warnings' => $strictWarnings,
            ], JSON_PRETTY_PRINT));

            return $this->option('strict') && (! empty($warnings) || ! empty($strictWarnings))
                ? self::FAILURE
                : self::SUCCESS;
        }

        $this->info("Active install type: {$active}");
        $this->newLine();

        $this->table(
            ['Group', 'Flag', 'ENV var', 'Effective', 'Source'],
            collect($rows)->map(fn ($r) => array_values($r))->all()
        );

        if (! empty($warnings)) {
            $this->newLine();
            $this->warn('Drift / cleanup suggestions:');
            foreach ($warnings as $w) {
                $this->line('  • '.$w);
            }
        }
        if (! empty($strictWarnings)) {
            $this->newLine();
            $this->warn('Strict-mode drift:');
            foreach ($strictWarnings as $w) {
                $this->line('  • '.$w);
            }
        }

        if ($this->option('strict') && (! empty($warnings) || ! empty($strictWarnings))) {
            $this->newLine();
            $this->error('Drift detected. --strict failure.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Find env keys present in `.env` but missing from `.env.example`. Both
     * files are at the project root. Comments and blank lines are skipped;
     * commented-out keys (`#FOO=bar`) are not considered "set".
     *
     * @return array<int, string>
     */
    private function envExampleDrift(): array
    {
        $root = base_path();
        $envPath = $root.DIRECTORY_SEPARATOR.'.env';
        $examplePath = $root.DIRECTORY_SEPARATOR.'.env.example';
        if (! file_exists($envPath) || ! file_exists($examplePath)) {
            return [];
        }
        $extract = static function (string $path): array {
            $keys = [];
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = ltrim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $m)) {
                    $keys[] = $m[1];
                }
            }

            return array_values(array_unique($keys));
        };
        $envKeys = $extract($envPath);
        $exampleKeys = $extract($examplePath);
        $missing = array_diff($envKeys, $exampleKeys);
        $warnings = [];
        foreach ($missing as $k) {
            $warnings[] = "{$k} is set in .env but missing from .env.example — fresh installs will not see this key.";
        }

        return $warnings;
    }

    private function fmt(mixed $v): string
    {
        if ($v === null) {
            return '—';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }

        return (string) $v;
    }
}
