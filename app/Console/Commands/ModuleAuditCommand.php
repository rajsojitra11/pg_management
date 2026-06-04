<?php

namespace App\Console\Commands;

use App\Services\ModuleComplianceScanner;
use Illuminate\Console\Command;

class ModuleAuditCommand extends Command
{
    protected $signature = 'module:audit
                            {module? : The module name to audit}
                            {--all : Audit all modules}
                            {--json : Output results as JSON}
                            {--summary : Show only the summary}';

    protected $description = 'Audit modules for activity logging and remarks compliance';

    protected ModuleComplianceScanner $scanner;

    public function __construct(ModuleComplianceScanner $scanner)
    {
        parent::__construct();
        $this->scanner = $scanner;
    }

    public function handle(): int
    {
        $modules = $this->resolveModules();

        if (empty($modules)) {
            $this->error('Please specify a module name or use --all to audit all modules.');

            return Command::FAILURE;
        }

        $allResults = [];
        $totals = ['pass' => 0, 'fail' => 0, 'warn' => 0, 'na' => 0];
        $failures = [];

        $progressBar = null;
        if ($this->option('all') && ! $this->option('json')) {
            $progressBar = $this->output->createProgressBar(count($modules));
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Scanning modules...');
            $progressBar->start();
        }

        foreach ($modules as $module) {
            if ($progressBar) {
                $progressBar->setMessage("Scanning {$module}...");
            }

            $result = $this->scanner->auditModule($module);
            $allResults[$module] = $result;

            // Tally results
            foreach ($result['models'] as $model => $data) {
                foreach ($data['checks'] as $check) {
                    match ($check['status']) {
                        'PASS' => $totals['pass']++,
                        'FAIL' => $totals['fail']++,
                        'WARN' => $totals['warn']++,
                        'N/A' => $totals['na']++,
                        default => null,
                    };

                    if ($check['status'] === 'FAIL') {
                        $fixTag = $check['fixable'] ? ' [AUTO-FIXABLE]' : '';
                        $failures[] = "[{$module}] {$model}: {$check['name']} — {$check['detail']}{$fixTag}";
                    }
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine(2);
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'modules' => $allResults,
                'summary' => $totals,
                'failures' => $failures,
            ], JSON_PRETTY_PRINT));

            return $totals['fail'] > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        if (! $this->option('summary')) {
            $this->renderDetailedOutput($allResults);
        }

        $this->renderSummary($totals, $failures);

        return $totals['fail'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function resolveModules(): array
    {
        if ($this->option('all')) {
            return $this->scanner->discoverAllModules();
        }

        if ($module = $this->argument('module')) {
            // Validate module exists
            $modulePath = base_path("Modules/{$module}");
            if (! is_dir($modulePath)) {
                $this->error("Module '{$module}' not found at {$modulePath}");

                return [];
            }

            return [$module];
        }

        return [];
    }

    protected function renderDetailedOutput(array $allResults): void
    {
        foreach ($allResults as $module => $result) {
            $type = str_replace('_', ' ', $result['type']);
            $modelCount = count($result['models']);

            $this->newLine();
            $this->line("=== <fg=cyan;options=bold>{$module}</> ===");
            $this->line("  Type: <fg=yellow>{$type}</> | Models: {$modelCount}");

            if (empty($result['models'])) {
                $this->line('  <fg=gray>No auditable models found</>');

                continue;
            }

            foreach ($result['models'] as $model => $data) {
                $this->newLine();
                $this->line("  Model: <options=bold>{$model}</>");

                $rows = [];
                foreach ($data['checks'] as $check) {
                    $statusColor = match ($check['status']) {
                        'PASS' => 'green',
                        'FAIL' => 'red',
                        'WARN' => 'yellow',
                        'N/A' => 'gray',
                        default => 'white',
                    };

                    $detail = $check['detail'];
                    if ($check['fixable']) {
                        $detail .= ' <fg=blue>[AUTO-FIXABLE]</>';
                    }

                    $rows[] = [
                        $check['name'],
                        "<fg={$statusColor}>{$check['status']}</>",
                        $detail,
                    ];
                }

                $this->table(
                    ['Check', 'Status', 'Detail'],
                    $rows
                );
            }
        }
    }

    protected function renderSummary(array $totals, array $failures): void
    {
        $total = $totals['pass'] + $totals['fail'] + $totals['warn'] + $totals['na'];

        $this->newLine();
        $this->line('=== <options=bold>AUDIT SUMMARY</> ===');
        $this->line("  Total: {$total} | <fg=green>Passed: {$totals['pass']}</> | <fg=red>Failed: {$totals['fail']}</> | <fg=yellow>Warnings: {$totals['warn']}</> | <fg=gray>N/A: {$totals['na']}</>");

        if (! empty($failures)) {
            $this->newLine();
            $this->line('<fg=red;options=bold>FAILURES:</>');
            foreach ($failures as $failure) {
                $this->line("  {$failure}");
            }

            $autoFixable = array_filter($failures, fn ($f) => str_contains($f, '[AUTO-FIXABLE]'));
            if (! empty($autoFixable)) {
                $this->newLine();
                $this->info('Tip: Run `php artisan module:fix --all` to auto-fix '.count($autoFixable).' issue(s).');
            }
        }
    }
}
