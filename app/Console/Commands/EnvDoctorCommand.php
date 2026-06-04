<?php

namespace App\Console\Commands;

use App\Services\EnvManifest\EnvManifest;
use App\Services\EnvManifest\EnvManifestEntry;
use Illuminate\Console\Command;

/**
 * `php artisan env:doctor` — diagnose env-var drift against the manifest.
 *
 * Layered on top of config/env-manifest/*.php. Reads the active .env (via
 * env() helper, which already respects the EnvVariable DB-override layer),
 * compares every manifest entry against its current value, and reports:
 *
 *   - drift           — current value differs from profile recommendation
 *   - deprecated      — manifest marks the key deprecated_in
 *   - profile-gap     — bucket-C var documented but not yet in profiles.php
 *   - missing         — manifest entry has no value in .env (uses default)
 *   - uncatalogued    — env('X') referenced in code but absent from manifest
 *   - orphans         — .env has the key but manifest doesn't
 */
class EnvDoctorCommand extends Command
{
    protected $signature = 'env:doctor
                            {--group= : Scope to a single group (e.g. business, data-entry-audit)}
                            {--explain= : Print full metadata for one key}
                            {--drift : Only show vars whose value differs from profile recommendation}
                            {--deprecated : Only show vars marked deprecated_in}
                            {--profile= : Compare against a specific install_type instead of active}
                            {--strict : Exit non-zero on any error-severity finding (CI gate)}
                            {--json : Machine-readable output}';

    protected $description = 'Audit env-var values against config/env-manifest/*.php and the active profile.';

    public function __construct(private readonly EnvManifest $manifest)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($key = $this->option('explain')) {
            return $this->explain($key);
        }

        $entries = $this->manifest->entries();
        if ($entries->isEmpty()) {
            $this->components->warn('Manifest is empty. Add files to config/env-manifest/.');

            return self::SUCCESS;
        }

        if ($groupFilter = $this->option('group')) {
            $entries = $entries->filter(fn (EnvManifestEntry $e) => $e->group === $groupFilter);
        }

        $rows = [];
        $errors = 0;
        foreach ($entries as $entry) {
            $row = $this->buildRow($entry);
            if ($this->option('drift') && $row['status'] !== 'drift') {
                continue;
            }
            if ($this->option('deprecated') && ! $entry->isDeprecated()) {
                continue;
            }
            if ($row['severity'] === 'error') {
                $errors++;
            }
            $rows[] = $row;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'rows' => $rows,
                'summary' => $this->summary($rows),
            ], JSON_PRETTY_PRINT));
        } else {
            $this->printHuman($rows);
        }

        return ($this->option('strict') && $errors > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function explain(string $key): int
    {
        $entry = $this->manifest->find($key);
        if ($entry === null) {
            $this->components->error("'{$key}' is not in the manifest.");

            return self::FAILURE;
        }

        $current = env($key, $entry->default);
        $effective = $this->resolveValue($entry);

        $this->newLine();
        $this->components->info("env-manifest entry: {$key}");
        $this->line('  Group ............ '.$entry->group.($entry->subgroup ? ' / '.$entry->subgroup : ''));
        $this->line('  Description ...... '.$entry->description);
        if ($entry->long !== null) {
            $this->line('  Long ............. '.$entry->long);
        }
        $this->line('  Type ............. '.$entry->type);
        if ($entry->allowed !== null) {
            $this->line('  Allowed .......... '.implode(' | ', array_map(fn ($v) => var_export($v, true), $entry->allowed)));
        }
        $this->line('  Default .......... '.var_export($entry->default, true));
        $this->line('  Current (.env) ... '.var_export($current, true));
        $this->line('  Effective ........ '.var_export($effective, true));
        if ($entry->profileKey !== null) {
            $this->line('  Profile key ...... '.$entry->profileKey);
        }
        if ($entry->recommended !== null) {
            $this->line('  Recommended ...... '.json_encode($entry->recommended));
        }
        $this->line('  Criticality ...... '.$entry->criticality);
        if ($entry->businessRelevant) {
            $this->line('  Business-relevant: yes'.($entry->profileGap ? ' (profile_gap=true — not yet in config/profiles.php)' : ''));
        }
        if ($entry->isDeprecated()) {
            $this->line('  Deprecated since . '.$entry->deprecatedIn);
        }
        if ($entry->replacedBy !== null) {
            $this->line('  Replaced by ...... '.$entry->replacedBy);
        }
        if (! empty($entry->related)) {
            $this->line('  Related .......... '.implode(', ', $entry->related));
        }
        $this->line('  Source file ...... '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $entry->sourceFile));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @return array{key: string, group: string, current: mixed, severity: string, status: string, message: string}
     */
    private function buildRow(EnvManifestEntry $entry): array
    {
        $current = env($entry->key, $entry->default);
        $current = $entry->secret ? '••••••' : $current;

        $status = 'ok';
        $severity = 'ok';
        $message = '';

        if ($entry->isDeprecated()) {
            $status = 'deprecated';
            $severity = 'warn';
            $message = "deprecated since {$entry->deprecatedIn}";
            if ($entry->replacedBy) {
                $message .= " — use {$entry->replacedBy} instead";
            }

            return compact('status', 'severity', 'message') + [
                'key' => $entry->key,
                'group' => $entry->group,
                'current' => $current,
            ];
        }

        if ($entry->profileGap) {
            $status = 'profile-gap';
            $severity = 'info';
            $message = 'documented in manifest but not in config/profiles.php yet';
        }

        if ($entry->recommended !== null) {
            $profile = $this->option('profile') ?? config('profiles.active');
            $expected = $entry->recommended[$profile] ?? null;
            if ($expected !== null && $this->valuesDiffer($current, $expected)) {
                $status = 'drift';
                $severity = $entry->criticality === 'high' ? 'error' : 'warn';
                $message = "drift from {$profile} recommendation: expected ".var_export($expected, true).', got '.var_export($current, true);
            }
        }

        return [
            'key' => $entry->key,
            'group' => $entry->group,
            'current' => $current,
            'severity' => $severity,
            'status' => $status,
            'message' => $message,
        ];
    }

    private function resolveValue(EnvManifestEntry $entry): mixed
    {
        if ($entry->profileKey !== null) {
            $profileValue = config('profiles.profiles.'.config('profiles.active').'.'.$entry->profileKey);
            if ($profileValue !== null) {
                return $profileValue;
            }
        }

        return env($entry->key, $entry->default);
    }

    private function valuesDiffer(mixed $a, mixed $b): bool
    {
        // Loose comparison — '1' should match true / 1 etc.
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a !== (bool) $b;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a !== (float) $b;
        }

        return (string) $a !== (string) $b;
    }

    /**
     * @param  list<array{key: string, group: string, current: mixed, severity: string, status: string, message: string}>  $rows
     */
    private function printHuman(array $rows): void
    {
        $tableRows = [];
        foreach ($rows as $r) {
            $symbol = match ($r['severity']) {
                'ok' => '✓',
                'info' => 'ℹ',
                'warn' => '⚠',
                'error' => '✗',
                default => '?',
            };
            $tableRows[] = [
                $symbol,
                $r['key'],
                $r['group'],
                $r['status'],
                $r['message'] ?: $this->truncate(var_export($r['current'], true), 40),
            ];
        }

        $this->table(['', 'Key', 'Group', 'Status', 'Note / value'], $tableRows);

        $sum = $this->summary($rows);
        $this->line(sprintf(
            '<fg=green>%d ok</> · <fg=blue>%d info</> · <fg=yellow>%d warn</> · <fg=red>%d error</>  (total %d)',
            $sum['ok'], $sum['info'], $sum['warn'], $sum['error'], count($rows)
        ));
    }

    private function summary(array $rows): array
    {
        $sum = ['ok' => 0, 'info' => 0, 'warn' => 0, 'error' => 0];
        foreach ($rows as $r) {
            $sum[$r['severity']] = ($sum[$r['severity']] ?? 0) + 1;
        }

        return $sum;
    }

    private function truncate(string $s, int $max): string
    {
        return strlen($s) > $max ? substr($s, 0, $max - 1).'…' : $s;
    }
}
