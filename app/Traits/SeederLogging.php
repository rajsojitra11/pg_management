<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait SeederLogging
{
    /**
     * Set up seeding context with authentication
     */
    protected function setupSeederContext(): void
    {
        $superAdminId = $this->getSuperAdminId();

        // Temporarily authenticate as Super_Admin for proper logging
        Auth::loginUsingId($superAdminId);

        request()->merge([
            'seeder_context' => true,
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
        ]);
    }

    /**
     * Clean up seeding context
     */
    protected function cleanupSeederContext(): void
    {
        Auth::logout();
        request()->offsetUnset('seeder_context');
        request()->offsetUnset('created_by');
        request()->offsetUnset('updated_by');
    }

    /**
     * Create a model with seeding context.
     */
    protected function createWithLogging(string $modelClass, array $data): object
    {
        $this->setupSeederContext();

        $migrationDate = $this->getMigrationDate();
        $superAdminId = $this->getSuperAdminId();

        $model = $modelClass::create(array_merge($data, [
            'created_by' => $superAdminId,
            'updated_by' => $superAdminId,
            'created_at' => $migrationDate,
            'updated_at' => $migrationDate,
        ]));

        $this->cleanupSeederContext();

        return $model;
    }

    /**
     * Create multiple models with seeding context
     */
    protected function createManyWithLogging(string $modelClass, array $dataArray): void
    {
        foreach ($dataArray as $data) {
            // Check if record already exists (assuming unique field is 'name' or 'code')
            $uniqueField = $this->getUniqueField($data);
            if ($uniqueField && $modelClass::where($uniqueField['field'], $uniqueField['value'])->exists()) {
                continue;
            }

            $this->createWithLogging($modelClass, $data);
        }
    }

    /**
     * Update a model with seeding context
     */
    protected function updateWithLogging(object $model, array $data): void
    {
        $this->setupSeederContext();

        $migrationDate = $this->getMigrationDate();
        $superAdminId = $this->getSuperAdminId();

        $model->update(array_merge($data, [
            'updated_by' => $superAdminId,
            'updated_at' => $migrationDate,
        ]));

        $this->cleanupSeederContext();
    }

    /**
     * Delete a model with seeding context
     */
    protected function deleteWithLogging(object $model, ?string $reason = null): void
    {
        $this->setupSeederContext();

        if ($reason) {
            request()->merge(['reason' => $reason]);
        }

        $model->delete();

        $this->cleanupSeederContext();
    }

    /**
     * Get the Super_Admin ID (assuming ID = 1)
     */
    protected function getSuperAdminId(): int
    {
        return 1; // Super_Admin user ID
    }

    // No-op stub. Was part of the removed historical-data-entry subsystem —
    // used to set is_initial_data_load=1 and effective_at on seeded rows.
    // Those columns no longer exist; method retained so legacy callers don't fatal.
    protected function stampInitialDataLoad($model, ?Carbon $effectiveAt = null): void {}

    /**
     * Get the migration date from environment variables
     */
    protected function getMigrationDate(): Carbon
    {
        // First, try to get from .env file directly

        $systemBaseDate = env('SYSTEM_MIGRATION_BASE_DATE');
        if ($systemBaseDate) {
            try {
                return Carbon::parse($systemBaseDate);
            } catch (\Exception $e) {
                Log::warning('Failed to parse SYSTEM_MIGRATION_BASE_DATE from .env', [
                    'system_base_date' => $systemBaseDate,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Try to get from EnvVariable model as fallback
        try {
            $envVariableClass = '\Modules\EnvVariable\Models\EnvVariable';
            if (class_exists($envVariableClass)) {
                $envVariable = $envVariableClass::where('key', 'SYSTEM_MIGRATION_BASE_DATE')->first();
                if ($envVariable && $envVariable->value) {
                    return Carbon::parse($envVariable->value);
                }
            }
        } catch (\Exception $e) {
            // Fallback if EnvVariable model is not available or table doesn't exist
        }

        // Fallback to helper function
        if (function_exists('getDefaultMigrationDate')) {
            return Carbon::parse(getDefaultMigrationDate());
        }

        // Final fallback
        return Carbon::parse('2025-01-01 00:00:00');
    }

    /**
     * Get unique field for duplicate checking
     */
    protected function getUniqueField(array $data): ?array
    {
        // Common unique fields to check
        $commonFields = ['code', 'name', 'email', 'key', 'slug'];

        foreach ($commonFields as $field) {
            if (isset($data[$field])) {
                return [
                    'field' => $field,
                    'value' => $data[$field],
                ];
            }
        }

        return null;
    }

    /**
     * Log a custom message for seeding operations
     */
    protected function logSeederOperation(string $message, ?string $modelClass, $fullData): void
    {
        $migrationDate = $this->getMigrationDate();
        $superAdminId = $this->getSuperAdminId();

        // Log to Laravel's logger

        // If we have a specific model class, try to create a log entry in its log table
        if ($modelClass && class_exists($modelClass)) {
            try {
                $this->setupSeederContext();

                // Try to find the corresponding log model
                $logModelClass = $this->getLogModelClass($modelClass);

                if ($logModelClass && class_exists($logModelClass)) {
                    // Get foreign key field name dynamically
                    $modelName = class_basename($modelClass);
                    $foreignKeyField = strtolower($modelName).'_id';

                    $logData = [
                        $foreignKeyField => 0, // No specific record for seeder operations
                        'user_id' => $superAdminId,
                        'activity' => 'System Record Creation',
                        'system_remark' => $message,
                        'user_remark' => 'Initial Data Created By System Setup',
                        'old_values' => null,
                        'new_values' => json_encode($fullData),
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'System Data Creator',
                        'device' => 'Server',
                        'platform' => 'Server',
                        'browser' => 'Server',
                        'created_by' => $superAdminId,
                        'created_at' => $migrationDate,
                    ];

                    // Special handling for User model logs which has user_id_acting_on field
                    if ($modelName === 'User') {
                        $logData['user_id_acting_on'] = $superAdminId;
                        $logData['successful'] = 1;
                        $logData['logout_reason'] = null;
                    }

                    $logModelClass::create($logData);
                }

                $this->cleanupSeederContext();
            } catch (\Exception $e) {
                // If logging fails, just continue - don't break the seeding process
                Log::warning("Failed to create seeder log entry: {$e->getMessage()}");
            }
        }
    }

    /**
     * Get the log model class name for a given model class
     */
    protected function getLogModelClass(string $modelClass): ?string
    {
        // Extract model name
        $modelName = class_basename($modelClass);

        // Extract namespace parts
        $namespaceParts = explode('\\', $modelClass);

        // Build potential log model class name
        if (count($namespaceParts) >= 4 && $namespaceParts[0] === 'Modules') {
            // For module models: Modules\Module\Models\Model -> Modules\Module\Models\ModelLog
            $moduleName = $namespaceParts[1];

            return "Modules\\{$moduleName}\\Models\\{$modelName}Log";
        } elseif (count($namespaceParts) >= 3 && $namespaceParts[0] === 'App') {
            // For app models: App\Models\Model -> App\Models\ModelLog
            return "App\\Models\\{$modelName}Log";
        }

        return null;
    }

    /**
     * Truncate a table safely across MySQL and PostgreSQL.
     *
     * Disables FK constraints before truncating and re-enables after.
     * PostgreSQL requires TRUNCATE … CASCADE when other tables reference
     * the table being emptied.
     */
    protected function safeTruncate(string $table): void
    {
        Schema::disableForeignKeyConstraints();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE "'.$table.'" CASCADE');
        } else {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Create or update model ensuring proper logging context
     */
    protected function createOrUpdateWithLogging(string $modelClass, array $searchCriteria, array $data): object
    {
        $this->setupSeederContext();

        $migrationDate = $this->getMigrationDate();
        $superAdminId = $this->getSuperAdminId();

        // Check if record exists
        $model = $modelClass::where($searchCriteria)->first();

        if ($model) {
            $fullData = array_merge($data, [
                'updated_by' => $superAdminId,
                'updated_at' => $migrationDate,
            ]);
            // Update existing record
            $model->update(array_merge($data, [
                'updated_by' => $superAdminId,
                'updated_at' => $migrationDate,
            ]));

            $this->logSeederOperation("Updated existing {$modelClass}: ".($model->name ?? $model->id), $modelClass, $fullData);
        } else {

            $fullData = array_merge($data, [
                'created_by' => $superAdminId,
                'updated_by' => $superAdminId,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]);
            // Create new record
            $model = $modelClass::create(array_merge($data, [
                'created_by' => $superAdminId,
                'updated_by' => $superAdminId,
                'created_at' => $migrationDate,
                'updated_at' => $migrationDate,
            ]));

            $this->logSeederOperation("Created new {$modelClass}: ".($model->name ?? $model->id), $modelClass, $fullData);
        }

        $this->cleanupSeederContext();

        return $model;
    }
}
