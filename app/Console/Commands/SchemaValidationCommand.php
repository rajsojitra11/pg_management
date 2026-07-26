<?php

namespace App\Console\Commands;

use App\Services\SchemaValidationService;
use Illuminate\Console\Command;

class SchemaValidationCommand extends Command
{
    protected $signature = 'schema:validation
                            {--module= : Process a specific module}
                            {--all : Process all modules}
                            {--dry-run : Show diff only, no file changes (default if no mode specified)}
                            {--apply : Add missing rules to FormRequests + generate JSON}
                            {--force : Overwrite schema-derivable rules in FormRequests}
                            {--json-only : Only generate JSON files}
                            {--requests-only : Only update FormRequests}';

    protected $description = 'Generate validation rules and JSON files from database table schemas';

    protected SchemaValidationService $service;

    protected int $modulesProcessed = 0;

    protected int $jsonFilesGenerated = 0;

    protected int $rulesAdded = 0;

    public function __construct(SchemaValidationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $isDryRun = $this->isDryRun();
        $moduleName = $this->option('module');

        if (! $moduleName && ! $this->option('all')) {
            $this->error('Please specify --module=ModuleName or --all');

            return self::FAILURE;
        }

        $this->info($isDryRun ? 'Running in DRY-RUN mode (no files will be modified)' : 'Running in APPLY mode');
        $this->newLine();

        if ($moduleName) {
            $this->processModule($moduleName, $isDryRun);
        } else {
            $this->processAllModules($isDryRun);
        }

        $this->printSummary($isDryRun);

        return self::SUCCESS;
    }

    protected function isDryRun(): bool
    {
        // Default to dry-run unless --apply or --force is specified
        if ($this->option('apply') || $this->option('force')) {
            return false;
        }

        return true;
    }

    protected function processAllModules(bool $isDryRun): void
    {
        $modules = $this->service->discoverModules();

        if (empty($modules)) {
            $this->warn('No modules found.');

            return;
        }

        $this->info('Found '.count($modules).' modules');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($modules));
        $bar->start();

        foreach ($modules as $moduleName => $modelInfo) {
            $this->processModule($moduleName, $isDryRun, $modelInfo);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processModule(string $moduleName, bool $isDryRun, ?array $modelInfo = null): void
    {
        // Discover model if not provided
        if (! $modelInfo) {
            $modules = $this->service->discoverModules();
            $modelInfo = $modules[$moduleName] ?? null;

            if (! $modelInfo) {
                $this->warn("[{$moduleName}] No primary model found. Skipping.");

                return;
            }
        }

        $table = $modelInfo['table'];
        $fillable = $modelInfo['fillable'];

        // Get schema
        $schema = $this->service->getTableSchema($table);

        if (empty($schema)) {
            $this->warn("[{$moduleName}] Table '{$table}' not found. Skipping.");

            return;
        }

        // Find FormRequests
        $requests = $this->service->findFormRequests($moduleName);

        if (empty($requests)) {
            $this->warn("[{$moduleName}] No FormRequest classes found. Skipping.");

            return;
        }

        $this->modulesProcessed++;

        foreach ($requests as $action => $requestClass) {
            $this->processFormRequest($moduleName, $action, $requestClass, $schema, $fillable, $table, $isDryRun);
        }
    }

    protected function processFormRequest(string $moduleName, string $action, string $requestClass, array $schema, array $fillable, string $table, bool $isDryRun): void
    {
        // Skip schema introspection for delete requests
        if ($action === 'delete') {
            if (! $this->option('requests-only')) {
                $this->generateDeleteJson($moduleName, $action, $table, $isDryRun);
            }

            return;
        }

        // Get existing rules from FormRequest
        $existingFields = $this->service->getExistingRules($requestClass);

        // Compute diff
        $diff = $this->service->computeDiff(
            $schema['columns'],
            $existingFields,
            $fillable,
            $schema['foreign_keys'],
            $schema['unique_indexes'],
            $table
        );

        if ($isDryRun) {
            $this->displayDiff($moduleName, $action, $requestClass, $diff);
        } else {
            // Generate JSON unless --requests-only
            if (! $this->option('requests-only')) {
                $json = $this->service->generateJson($moduleName, $table, $schema, $action);

                // Filter to only fillable fields
                if (! empty($fillable)) {
                    $json['fields'] = array_filter(
                        $json['fields'],
                        fn ($field) => in_array($field, $fillable),
                        ARRAY_FILTER_USE_KEY
                    );
                }

                $filepath = $this->service->saveJson($moduleName, $action, $json);
                $this->jsonFilesGenerated++;
                $this->line("  [{$moduleName}] Generated: ".basename($filepath));
            }

            // Update FormRequests unless --json-only
            if (! $this->option('json-only')) {
                $missingFields = array_filter($diff, fn ($d) => $d['status'] === 'missing');

                if (! empty($missingFields)) {
                    $reflection = new \ReflectionClass($requestClass);
                    $requestFile = $reflection->getFileName();
                    $added = $this->service->addMissingRules(
                        $requestFile,
                        $missingFields,
                        $schema['columns'],
                        $schema['foreign_keys'],
                        $schema['unique_indexes'],
                        $table
                    );
                    $this->rulesAdded += $added;

                    if ($added > 0) {
                        $this->line("  [{$moduleName}] Added {$added} missing rule(s) to {$action} request");
                    }
                }
            }
        }
    }

    protected function generateDeleteJson(string $moduleName, string $action, string $table, bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->line("  [{$moduleName}] {$action}: Fixed pattern (id + entry_date) — no schema needed");

            return;
        }

        $json = [
            'module' => $moduleName,
            'table' => $table,
            'action' => $action,
            'generated_at' => now()->toIso8601String(),
            'fields' => [
                'id' => [
                    'rules' => ['required', 'integer', "exists:{$table},id"],
                    'type' => 'bigint',
                    'nullable' => false,
                    'html_type' => 'hidden',
                ],
                'entry_date' => [
                    'rules' => ['nullable', 'date', 'before_or_equal:now'],
                    'type' => 'datetime',
                    'nullable' => true,
                    'html_type' => 'date',
                ],
            ],
        ];

        $filepath = $this->service->saveJson($moduleName, $action, $json);
        $this->jsonFilesGenerated++;
        $this->line("  [{$moduleName}] Generated: ".basename($filepath));
    }

    protected function displayDiff(string $moduleName, string $action, string $requestClass, array $diff): void
    {
        $shortClass = class_basename($requestClass);
        $this->newLine();
        $this->info("[{$moduleName}] {$shortClass}:");

        $missing = array_filter($diff, fn ($d) => $d['status'] === 'missing');
        $existing = array_filter($diff, fn ($d) => $d['status'] === 'exists');
        $extra = array_filter($diff, fn ($d) => $d['status'] === 'extra');

        if (! empty($missing)) {
            foreach ($missing as $field => $info) {
                $rules = implode('|', $info['schema_rules']);
                $this->line("  <fg=red>+ {$field}</>: missing from rules — schema suggests: <fg=yellow>{$rules}</>");
            }
        }

        if (! empty($existing)) {
            foreach ($existing as $field => $info) {
                $rules = implode('|', $info['schema_rules']);
                $this->line("  <fg=green>~ {$field}</>: exists (schema: {$rules})");
            }
        }

        if (! empty($extra)) {
            foreach ($extra as $field => $info) {
                $this->line("  <fg=cyan>? {$field}</>: in FormRequest but not in DB (virtual/array field)");
            }
        }

        if (empty($missing)) {
            $this->line('  <fg=green>All fillable fields have validation rules.</>');
        }
    }

    protected function printSummary(bool $isDryRun): void
    {
        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Modules processed: {$this->modulesProcessed}");

        if (! $isDryRun) {
            $this->line("JSON files generated: {$this->jsonFilesGenerated}");
            $this->line("Rules added to FormRequests: {$this->rulesAdded}");
        } else {
            $this->line('Mode: dry-run (no changes made). Use --apply to generate files.');
        }
    }
}
