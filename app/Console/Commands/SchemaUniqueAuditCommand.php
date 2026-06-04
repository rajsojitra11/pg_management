<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SchemaUniqueAuditCommand extends Command
{
    protected $signature = 'schema:unique-audit
                            {--module= : Audit a single module}
                            {--report= : Write a markdown report to the given path (e.g. docs/unique-audit.md)}';

    protected $description = 'Audit unique-constraint coverage for every module table; flag suspect columns without unique enforcement.';

    /**
     * Column names that almost always benefit from a unique constraint.
     * Matched literally OR as a suffix (e.g. *_code, *_number).
     */
    private const SUSPECT_NAMES = ['code', 'name', 'slug', 'public_id'];

    private const SUSPECT_SUFFIXES = ['_code', '_number', '_no'];

    public function handle(): int
    {
        $moduleFilter = $this->option('module');
        $modulesDir = base_path('Modules');

        if (! is_dir($modulesDir)) {
            $this->error('Modules directory not found.');

            return self::FAILURE;
        }

        $rows = [];
        foreach (File::directories($modulesDir) as $moduleDir) {
            $module = basename($moduleDir);
            if ($moduleFilter && strcasecmp($module, $moduleFilter) !== 0) {
                continue;
            }
            foreach ($this->tablesForModule($module) as $table) {
                $rows[] = $this->auditTable($module, $table);
            }
        }

        $this->printConsole($rows);

        if ($reportPath = $this->option('report')) {
            $absPath = Str::startsWith($reportPath, ['/', '\\']) || preg_match('/^[A-Za-z]:/', $reportPath)
                ? $reportPath
                : base_path($reportPath);
            File::ensureDirectoryExists(dirname($absPath));
            File::put($absPath, $this->renderMarkdown($rows));
            $this->info('Report written to '.$absPath);
        }

        return self::SUCCESS;
    }

    private function tablesForModule(string $module): array
    {
        $migrationsDir = base_path("Modules/{$module}/database/migrations");
        if (! is_dir($migrationsDir)) {
            return [];
        }

        $tables = [];
        foreach (File::glob($migrationsDir.'/*.php') as $file) {
            $contents = @file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            if (preg_match_all("/Schema::create\(['\"]([a-z0-9_]+)['\"]/i", $contents, $m)) {
                $tables = array_merge($tables, $m[1]);
            }
        }

        $tables = array_values(array_unique($tables));

        return array_values(array_filter($tables, fn ($t) => $this->tableExists($t)));
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function auditTable(string $module, string $table): array
    {
        $columns = $this->columnsFor($table);
        $uniqueIndexes = $this->uniqueIndexesFor($table);

        $constrainedCols = [];
        foreach ($uniqueIndexes as $cols) {
            foreach ($cols as $c) {
                $constrainedCols[$c] = true;
            }
        }

        $suspectMissing = [];
        foreach ($columns as $col) {
            if (isset($constrainedCols[$col])) {
                continue;
            }
            if ($this->isSuspect($col)) {
                $suspectMissing[] = $col;
            }
        }

        return [
            'module' => $module,
            'table' => $table,
            'unique_indexes' => $uniqueIndexes,
            'suspect_missing' => $suspectMissing,
            'priority' => $this->prioritize($suspectMissing),
        ];
    }

    private function columnsFor(string $table): array
    {
        try {
            return DB::getSchemaBuilder()->getColumnListing($table);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function uniqueIndexesFor(string $table): array
    {
        $driver = DB::getDriverName();
        $indexes = [];

        try {
            if ($driver === 'sqlite') {
                $list = DB::select("PRAGMA index_list('{$table}')");
                foreach ($list as $idx) {
                    if (! $idx->unique) {
                        continue;
                    }
                    $info = DB::select("PRAGMA index_info('{$idx->name}')");
                    $cols = array_map(fn ($r) => $r->name, $info);
                    if (! empty($cols)) {
                        $indexes[] = $cols;
                    }
                }
            } else {
                $rows = DB::select(
                    'SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0 AND INDEX_NAME != ?
                     ORDER BY INDEX_NAME, SEQ_IN_INDEX',
                    [$table, 'PRIMARY']
                );
                $byName = [];
                foreach ($rows as $r) {
                    $byName[$r->INDEX_NAME][] = $r->COLUMN_NAME;
                }
                $indexes = array_values($byName);
            }
        } catch (\Throwable $e) {
            // ignore — table may not exist on this connection
        }

        return $indexes;
    }

    private function isSuspect(string $col): bool
    {
        $lower = Str::lower($col);
        if (in_array($lower, self::SUSPECT_NAMES, true)) {
            return true;
        }
        foreach (self::SUSPECT_SUFFIXES as $sfx) {
            if (Str::endsWith($lower, $sfx)) {
                return true;
            }
        }

        return false;
    }

    private function prioritize(array $suspectMissing): string
    {
        if (empty($suspectMissing)) {
            return 'OK';
        }
        if (count($suspectMissing) >= 3) {
            return 'P2';
        }
        if (count(array_intersect($suspectMissing, ['code', 'public_id'])) > 0) {
            return 'P3';
        }

        return 'P4';
    }

    private function printConsole(array $rows): void
    {
        $this->table(
            ['Module', 'Table', 'Unique Indexes', 'Suspect Missing', 'Priority'],
            array_map(fn ($r) => [
                $r['module'],
                $r['table'],
                $this->formatIndexes($r['unique_indexes']),
                implode(', ', $r['suspect_missing']) ?: '—',
                $r['priority'],
            ], $rows)
        );

        $issues = array_filter($rows, fn ($r) => ! empty($r['suspect_missing']));
        $this->info(sprintf('Audited %d tables — %d with suspect missing uniques.', count($rows), count($issues)));
    }

    private function formatIndexes(array $indexes): string
    {
        if (empty($indexes)) {
            return 'none';
        }

        return implode('; ', array_map(fn ($cols) => '('.implode(',', $cols).')', $indexes));
    }

    private function renderMarkdown(array $rows): string
    {
        $today = now()->format('Y-m-d');
        $out = "# Unique Constraint Audit — {$today}\n\n";
        $out .= "Generated by `php artisan schema:unique-audit --report=...`. Audits every module table for unique-index coverage and flags suspect columns (`code`, `name`, `slug`, `public_id`, `*_code`, `*_number`, `*_no`) that lack a unique constraint.\n\n";

        $out .= "## Priority legend\n\n";
        $out .= "- OK — no suspect columns missing\n";
        $out .= "- P2 — 3+ suspect cols missing (likely a fresh module that skipped uniqueness entirely)\n";
        $out .= "- P3 — `code` or `public_id` missing unique (high-impact business identifier)\n";
        $out .= "- P4 — other suspect column missing (lower priority)\n\n";

        $out .= "## Findings\n\n";
        $out .= "| Module | Table | Unique indexes | Suspect missing | Priority |\n";
        $out .= "|---|---|---|---|---|\n";
        foreach ($rows as $r) {
            $missing = empty($r['suspect_missing']) ? '—' : '`'.implode('`, `', $r['suspect_missing']).'`';
            $idx = empty($r['unique_indexes']) ? 'none' : implode('; ', array_map(fn ($cols) => '`('.implode(',', $cols).')`', $r['unique_indexes']));
            $out .= sprintf(
                "| %s | %s | %s | %s | %s |\n",
                $r['module'],
                '`'.$r['table'].'`',
                $idx,
                $missing,
                $r['priority']
            );
        }
        $out .= "\n";

        $issues = array_values(array_filter($rows, fn ($r) => ! empty($r['suspect_missing'])));
        $out .= "## Summary\n\n";
        $out .= '- Total tables audited: '.count($rows)."\n";
        $out .= '- Tables with suspect missing uniques: '.count($issues)."\n";

        return $out;
    }
}
