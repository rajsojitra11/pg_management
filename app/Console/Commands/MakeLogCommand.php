<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-log {model} {module} {--force : Overwrite existing log model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a log model and migration for an existing module model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = Str::studly($this->argument('model'));
        $module = Str::studly($this->argument('module'));
        $force = $this->option('force');

        $modulePath = base_path("Modules/{$module}");

        if (! is_dir($modulePath)) {
            $this->error("Module '{$module}' does not exist at {$modulePath}");

            return 1;
        }

        $logClass = "{$model}Log";
        $foreignKey = Str::snake($model).'_id';
        $logTable = Str::snake($model).'_logs';
        $modelVariable = Str::camel($model);

        $this->createLogModel($module, $model, $logClass, $foreignKey, $modelVariable, $modulePath, $force);
        $this->createLogMigration($module, $logTable, $foreignKey, $modulePath);

        $this->info("Log model and migration created successfully for {$model} in {$module} module.");
        $this->line("  Model: Modules/{$module}/app/Models/{$logClass}.php");
        $this->line("  Migration: Modules/{$module}/database/migrations/..._create_{$logTable}_table.php");

        if (! file_exists("{$modulePath}/app/Models/{$model}.php")) {
            $this->warn("Note: Model {$model}.php not found in the module. Make sure to add HasActivityLogging trait to it.");
        }

        return 0;
    }

    /**
     * Create the log model file from the stub.
     */
    protected function createLogModel(string $module, string $model, string $logClass, string $foreignKey, string $modelVariable, string $modulePath, bool $force): void
    {
        $targetPath = "{$modulePath}/app/Models/{$logClass}.php";

        if (file_exists($targetPath) && ! $force) {
            $this->warn("Log model already exists: {$targetPath} (use --force to overwrite)");

            return;
        }

        $stubPath = base_path('stubs/nwidart-stubs/model-log.stub');
        $stub = file_get_contents($stubPath);

        $namespace = "Modules\\{$module}\\Models";

        $content = str_replace(
            ['$NAMESPACE$', '$LOG_CLASS$', '$FOREIGN_KEY$', '$MODEL_VARIABLE$', '$MODEL_CLASS$'],
            [$namespace, $logClass, $foreignKey, $modelVariable, $model],
            $stub
        );

        // Ensure the Models directory exists
        $modelsDir = dirname($targetPath);
        if (! is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }

        file_put_contents($targetPath, $content);
        $this->info("Created: Modules/{$module}/app/Models/{$logClass}.php");
    }

    /**
     * Create the log migration file from the stub.
     */
    protected function createLogMigration(string $module, string $logTable, string $foreignKey, string $modulePath): void
    {
        $stubPath = base_path('stubs/nwidart-stubs/migration/create-log.stub');
        $stub = file_get_contents($stubPath);

        $content = str_replace(
            ['$LOG_TABLE$', '$FOREIGN_KEY$'],
            [$logTable, $foreignKey],
            $stub
        );

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$logTable}_table.php";
        $migrationDir = "{$modulePath}/database/migrations";

        if (! is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }

        $targetPath = "{$migrationDir}/{$fileName}";
        file_put_contents($targetPath, $content);
        $this->info("Created: Modules/{$module}/database/migrations/{$fileName}");
    }
}
