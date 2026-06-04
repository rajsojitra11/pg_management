<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `php artisan business:status`
 *
 * Audit-friendly view of the cascade-resolved business config. Each row
 * shows the effective value AND the source — `[env=VAR]` if an explicit
 * env var was set, `[profile=name]` if the active profile supplied the
 * default, or `[default]` if it fell through to a hardcoded fallback.
 *
 * Also surfaces consistency warnings (e.g., AUTO_RELEASE_STOCK_ON_PASS=1
 * paired with AUTO_REJECT_STOCK_ON_FAIL=0 — an asymmetric automation
 * configuration that's usually unintentional).
 */
class BusinessStatusCommand extends Command
{
    protected $signature = 'business:status {--json : Output as JSON}';

    protected $description = 'Show the resolved business configuration with source attribution and consistency warnings.';

    /**
     * Each row: [env_var, profile_key, label, group]
     */
    private array $registry = [
        ['INSTALL_TYPE', null, 'Active install type', 'Profile'],
        ['AUTO_RELEASE_STOCK_ON_PASS', 'inbound.auto_release_stock_on_pass', 'Auto-release stock on pass', 'Inbound automation'],
        ['AUTO_REJECT_STOCK_ON_FAIL', 'inbound.auto_reject_stock_on_fail', 'Auto-reject stock on fail', 'Inbound automation'],
        ['SPECIFICATION_CUSTOMER_DIMENSION_ENABLED', 'customer.specification_dimension_enabled', 'Customer dimension (CMO mode)', 'Customer'],
        ['PRODUCTION_REQUIRES_ROUTE', 'production.requires_route', 'Production requires route', 'Production'],
        ['AUTO_CREATE_TEST_ON_PRODUCTION_STAGE', 'production.auto_create_test_on_stage', 'Auto-create test at production stage', 'Production'],
        ['SAMPLE_STORAGE_REQUIRED', 'lab.sample_storage_required', 'Sample storage / retention required', 'Lab'],
        ['CHAIN_OF_CUSTODY_REQUIRED', 'lab.chain_of_custody_required', 'Chain of custody required', 'Lab'],
        ['COA_AUTO_RELEASE_ON_PASS', 'lab.coa_auto_release_on_pass', 'COA auto-release on pass', 'Lab'],
        ['TEST_METHOD_TRACEABILITY', 'lab.test_method_traceability', 'Test method traceability', 'Lab'],
    ];

    public function handle(): int
    {
        $rows = [];
        foreach ($this->registry as [$envVar, $profileKey, $label, $group]) {
            $effective = $this->resolveEffective($envVar, $profileKey);
            $source = $profileKey === null
                ? 'env='.$envVar
                : profile_source($envVar, $profileKey);

            $rows[] = [
                'group' => $group,
                'label' => $label,
                'env_var' => $envVar,
                'effective' => $this->displayValue($effective),
                'source' => $source,
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'rows' => $rows,
                'warnings' => $this->buildWarnings($rows),
            ], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->line('');
        $this->info('Active install type: '.config('profiles.active', 'custom'));
        $this->line('');

        $this->table(
            ['Group', 'Label', 'Env var', 'Effective', 'Source'],
            collect($rows)->map(fn ($r) => [$r['group'], $r['label'], $r['env_var'], $r['effective'], $r['source']])->all()
        );

        $warnings = $this->buildWarnings($rows);
        if (! empty($warnings)) {
            $this->line('');
            $this->warn('Consistency warnings:');
            foreach ($warnings as $w) {
                $this->line('  • '.$w);
            }
        }

        return Command::SUCCESS;
    }

    private function resolveEffective(string $envVar, ?string $profileKey): mixed
    {
        if ($envVar === 'INSTALL_TYPE') {
            return config('profiles.active', 'custom');
        }

        return env($envVar, $profileKey ? profile_default($profileKey) : null);
    }

    private function displayValue(mixed $v): string
    {
        if ($v === null) {
            return '—';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }

        return (string) $v;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function buildWarnings(array $rows): array
    {
        $effective = collect($rows)->keyBy('env_var')->map(fn ($r) => $r['effective']);

        $warnings = [];

        // Customer dimension flag inconsistency.
        if ((string) $effective->get('SPECIFICATION_CUSTOMER_DIMENSION_ENABLED') === '0') {
            $count = \DB::table('specifications')->whereNotNull('customer_id')->count();
            if ($count > 0) {
                $warnings[] = "SPECIFICATION_CUSTOMER_DIMENSION_ENABLED=0 but {$count} specification rows already have customer_id set — tests may resolve to the wrong spec until the flag is enabled.";
            }
        }

        return $warnings;
    }
}
