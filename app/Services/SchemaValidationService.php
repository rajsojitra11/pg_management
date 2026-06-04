<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchemaValidationService
{
    protected array $skipColumns;

    protected string $connection;

    public function __construct()
    {
        $this->skipColumns = config('schema-validation.skip_columns', [
            'id', 'created_at', 'updated_at', 'deleted_at',
            'created_by', 'updated_by', 'deleted_by',
        ]);
        $this->connection = config('schema-validation.connection', 'mariadb');
    }

    /**
     * Get full schema information for a database table.
     */
    public function getTableSchema(string $table): array
    {
        if (! Schema::connection($this->connection)->hasTable($table)) {
            return [];
        }

        $columns = $this->getColumns($table);
        $foreignKeys = $this->getForeignKeys($table);
        $uniqueIndexes = $this->getUniqueIndexes($table);

        return [
            'table' => $table,
            'columns' => $columns,
            'foreign_keys' => $foreignKeys,
            'unique_indexes' => $uniqueIndexes,
        ];
    }

    /**
     * Get column details from the database.
     */
    protected function getColumns(string $table): array
    {
        $rows = DB::connection($this->connection)
            ->select("SHOW FULL COLUMNS FROM `{$table}`");

        $columns = [];
        foreach ($rows as $row) {
            $field = $row->Field;

            if (in_array($field, $this->skipColumns)) {
                continue;
            }

            $columns[$field] = [
                'name' => $field,
                'type' => $row->Type,
                'nullable' => $row->Null === 'YES',
                'default' => $row->Default,
                'key' => $row->Key,
                'extra' => $row->Extra,
                'comment' => $row->Comment ?? '',
            ];
        }

        return $columns;
    }

    /**
     * Get foreign key constraints for a table.
     */
    protected function getForeignKeys(string $table): array
    {
        $database = DB::connection($this->connection)->getDatabaseName();

        $rows = DB::connection($this->connection)->select('
            SELECT
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ', [$database, $table]);

        $fks = [];
        foreach ($rows as $row) {
            $fks[$row->COLUMN_NAME] = [
                'table' => $row->REFERENCED_TABLE_NAME,
                'column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }

        return $fks;
    }

    /**
     * Get unique indexes for a table.
     */
    protected function getUniqueIndexes(string $table): array
    {
        $rows = DB::connection($this->connection)
            ->select("SHOW INDEX FROM `{$table}` WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->Key_name][] = $row->Column_name;
        }

        // Return only single-column unique indexes (composite uniques need business logic)
        $uniqueColumns = [];
        foreach ($indexes as $columns) {
            if (count($columns) === 1) {
                $uniqueColumns[] = $columns[0];
            }
        }

        return $uniqueColumns;
    }

    /**
     * Map a column's schema to Laravel validation rules.
     */
    public function mapColumnToRules(array $column, array $foreignKeys, array $uniqueIndexes, string $table): array
    {
        $rules = [];
        $field = $column['name'];
        $type = $column['type'];

        // Nullable vs required
        if (! $column['nullable'] && $column['default'] === null && $column['extra'] !== 'auto_increment') {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-specific rules
        $rules = array_merge($rules, $this->getTypeRules($type));

        // Foreign key → exists rule
        if (isset($foreignKeys[$field])) {
            $fk = $foreignKeys[$field];
            $rules[] = "exists:{$fk['table']},{$fk['column']}";
        }

        return $rules;
    }

    /**
     * Parse column type and return appropriate validation rules.
     */
    protected function getTypeRules(string $type): array
    {
        $rules = [];
        $typeLower = strtolower($type);

        // varchar(N)
        if (preg_match('/^varchar\((\d+)\)/', $typeLower, $m)) {
            $rules[] = 'string';
            $rules[] = "max:{$m[1]}";

            return $rules;
        }

        // text, mediumtext, longtext
        if (preg_match('/text/', $typeLower)) {
            $rules[] = 'string';

            return $rules;
        }

        // enum('A','B','C')
        if (preg_match("/^enum\((.+)\)/", $typeLower, $m)) {
            $values = str_replace("'", '', $m[1]);
            $rules[] = "in:{$values}";

            return $rules;
        }

        // tinyint(1) — boolean
        if (preg_match('/^tinyint\(1\)/', $typeLower)) {
            $rules[] = 'boolean';

            return $rules;
        }

        // int, bigint, smallint, mediumint, tinyint (non-boolean)
        if (preg_match('/^(big|small|medium|tiny)?int/', $typeLower)) {
            $rules[] = 'integer';

            return $rules;
        }

        // double, float, decimal
        if (preg_match('/^(double|float|decimal)/', $typeLower)) {
            $rules[] = 'numeric';

            return $rules;
        }

        // date, datetime, timestamp
        if (preg_match('/^(date|datetime|timestamp)/', $typeLower)) {
            $rules[] = 'date';

            return $rules;
        }

        return $rules;
    }

    /**
     * Determine HTML input type from column metadata.
     */
    public function getHtmlType(array $column, array $foreignKeys): string
    {
        $field = $column['name'];
        $type = strtolower($column['type']);

        if (isset($foreignKeys[$field])) {
            return 'select';
        }

        if (preg_match('/^enum/', $type)) {
            return 'select';
        }

        if (preg_match('/^tinyint\(1\)/', $type)) {
            return 'checkbox';
        }

        if (preg_match('/text/', $type)) {
            return 'textarea';
        }

        if (preg_match('/^(date|datetime|timestamp)/', $type)) {
            return 'date';
        }

        return 'text';
    }

    /**
     * Generate the JSON structure for a module's validation.
     */
    public function generateJson(string $moduleName, string $table, array $schema, string $action): array
    {
        $fields = [];

        foreach ($schema['columns'] as $field => $column) {
            $rules = $this->mapColumnToRules($column, $schema['foreign_keys'], $schema['unique_indexes'], $table);
            $htmlType = $this->getHtmlType($column, $schema['foreign_keys']);

            $fieldData = [
                'rules' => $rules,
                'type' => $this->extractBaseType($column['type']),
                'nullable' => $column['nullable'],
                'html_type' => $htmlType,
            ];

            // Add max_length for varchar
            if (preg_match('/^varchar\((\d+)\)/i', $column['type'], $m)) {
                $fieldData['max_length'] = (int) $m[1];
            }

            // Add foreign key info
            if (isset($schema['foreign_keys'][$field])) {
                $fieldData['foreign'] = $schema['foreign_keys'][$field];
            }

            // Add enum values
            if (preg_match("/^enum\((.+)\)/i", $column['type'], $m)) {
                $values = str_replace("'", '', $m[1]);
                $fieldData['enum_values'] = explode(',', $values);
            }

            $fields[$field] = $fieldData;
        }

        return [
            'module' => $moduleName,
            'table' => $table,
            'action' => $action,
            'generated_at' => now()->toIso8601String(),
            'fields' => $fields,
        ];
    }

    /**
     * Extract base type name from full column type.
     */
    protected function extractBaseType(string $type): string
    {
        $type = strtolower($type);

        if (preg_match('/^(\w+)/', $type, $m)) {
            return $m[1];
        }

        return $type;
    }

    /**
     * Save JSON validation file for a module.
     */
    public function saveJson(string $moduleName, string $action, array $json): string
    {
        $requestsPath = base_path("Modules/{$moduleName}/app/Http/Requests");

        if (! File::isDirectory($requestsPath)) {
            File::makeDirectory($requestsPath, 0755, true);
        }

        $filename = Str::lower($moduleName)."-{$action}.json";
        $filepath = "{$requestsPath}/{$filename}";

        File::put($filepath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $filepath;
    }

    /**
     * Get existing FormRequest rules via reflection.
     */
    public function getExistingRules(string $requestClass): array
    {
        if (! class_exists($requestClass)) {
            return [];
        }

        try {
            $reflection = new \ReflectionClass($requestClass);
            $method = $reflection->getMethod('rules');

            // Read source to extract field names (we can't instantiate without a request)
            $source = File::get($method->getFileName());
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            $lines = array_slice(explode("\n", $source), $startLine - 1, $endLine - $startLine + 1);
            $methodSource = implode("\n", $lines);

            // Extract field names from the rules array using regex
            $fields = [];
            // Match 'field_name' => or "field_name" =>
            preg_match_all("/['\"]([a-zA-Z_][a-zA-Z0-9_.*]*)['\"]\\s*=>/", $methodSource, $matches);

            if (! empty($matches[1])) {
                $fields = $matches[1];
            }

            return $fields;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if a rule looks like a business rule (should be preserved, not overwritten).
     */
    public function isBusinessRule(string $ruleSource): bool
    {
        $businessPatterns = [
            'required_if',
            'required_with',
            'required_without',
            'required_unless',
            'regex:',
            'config(',
            'Rule::unique',
            'Rule::exists',
            'Rule::in',
            'function (',
            'function(',
            '$this->',
        ];

        foreach ($businessPatterns as $pattern) {
            if (str_contains($ruleSource, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Discover all modules and their primary models.
     */
    public function discoverModules(): array
    {
        $modulesPath = base_path('Modules');
        $excludedModules = config('schema-validation.excluded_modules', []);
        $modules = [];

        if (! File::isDirectory($modulesPath)) {
            return [];
        }

        foreach (File::directories($modulesPath) as $dir) {
            $moduleName = basename($dir);

            if (in_array($moduleName, $excludedModules)) {
                continue;
            }

            $modelPath = "{$dir}/app/Models";
            if (! File::isDirectory($modelPath)) {
                continue;
            }

            // Find primary model (matching module name, excluding Log models)
            $primaryModel = $this->findPrimaryModel($moduleName, $modelPath);

            if ($primaryModel) {
                $modules[$moduleName] = $primaryModel;
            }
        }

        return $modules;
    }

    /**
     * Find the primary model for a module.
     */
    protected function findPrimaryModel(string $moduleName, string $modelPath): ?array
    {
        $files = File::files($modelPath);
        $primaryModelFile = null;

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();

            // Skip Log models, PrintHistory, etc.
            if (Str::endsWith($filename, 'Log') || Str::endsWith($filename, 'printhistory')) {
                continue;
            }

            // Prefer exact module name match
            if ($filename === $moduleName) {
                $primaryModelFile = $file;
                break;
            }

            // Fall back to first non-Log model
            if (! $primaryModelFile) {
                $primaryModelFile = $file;
            }
        }

        if (! $primaryModelFile) {
            return null;
        }

        $modelClass = "Modules\\{$moduleName}\\Models\\".$primaryModelFile->getFilenameWithoutExtension();

        if (! class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass;

            return [
                'class' => $modelClass,
                'table' => $model->getTable(),
                'fillable' => $model->getFillable(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Find FormRequest classes for a module.
     */
    public function findFormRequests(string $moduleName): array
    {
        $requestsPath = base_path("Modules/{$moduleName}/app/Http/Requests");

        if (! File::isDirectory($requestsPath)) {
            return [];
        }

        $requests = [];
        $namespace = "Modules\\{$moduleName}\\Http\\Requests";

        foreach (['Store', 'Update', 'Delete'] as $action) {
            // Try standard naming: Store{Module}Request
            $className = "{$namespace}\\{$action}{$moduleName}Request";

            if (class_exists($className)) {
                $requests[strtolower($action)] = $className;
            }
        }

        return $requests;
    }

    /**
     * Compute diff between schema-suggested rules and existing FormRequest rules.
     */
    public function computeDiff(array $schemaColumns, array $existingFields, array $fillable, array $foreignKeys, array $uniqueIndexes, string $table): array
    {
        $diff = [];

        foreach ($schemaColumns as $field => $column) {
            // Skip if not in fillable (not user-editable)
            if (! empty($fillable) && ! in_array($field, $fillable)) {
                continue;
            }

            $schemaRules = $this->mapColumnToRules($column, $foreignKeys, $uniqueIndexes, $table);
            $inExisting = in_array($field, $existingFields);

            if (! $inExisting) {
                $diff[$field] = [
                    'status' => 'missing',
                    'schema_rules' => $schemaRules,
                    'message' => 'Field in DB/fillable but missing from FormRequest rules',
                ];
            } else {
                $diff[$field] = [
                    'status' => 'exists',
                    'schema_rules' => $schemaRules,
                    'message' => 'Field has existing rules (schema suggests: '.implode('|', $schemaRules).')',
                ];
            }
        }

        // Check for fields in FormRequest but not in DB
        foreach ($existingFields as $field) {
            if (! isset($schemaColumns[$field]) && ! str_contains($field, '.*') && ! in_array($field, ['user_remark', 'entry_date'])) {
                $diff[$field] = [
                    'status' => 'extra',
                    'schema_rules' => [],
                    'message' => 'Field in FormRequest but not in DB table (virtual/array field)',
                ];
            }
        }

        return $diff;
    }

    /**
     * Generate a rule string for adding to a FormRequest.
     */
    public function formatRuleString(array $rules): string
    {
        return "'".implode('|', $rules)."'";
    }

    /**
     * Add missing field rules to a FormRequest file.
     */
    public function addMissingRules(string $requestFile, array $missingFields, array $schemaColumns, array $foreignKeys, array $uniqueIndexes, string $table): int
    {
        $source = File::get($requestFile);
        $added = 0;

        foreach ($missingFields as $field => $info) {
            if ($info['status'] !== 'missing') {
                continue;
            }

            $rules = $info['schema_rules'];
            $ruleString = $this->formatRuleString($rules);

            // Find the rules() method return array and insert before the closing bracket
            $patterns = [
                // return [ ... ];
                '/(return\s*\[)(.*?)(\]\s*;)/s',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $source)) {
                    $source = preg_replace_callback($pattern, function ($matches) use ($field, $ruleString) {
                        $existingContent = rtrim($matches[2]);
                        // Add comma if there's existing content
                        $comma = ! empty(trim($existingContent)) ? ',' : '';

                        return $matches[1].$existingContent.$comma."\n            '{$field}' => {$ruleString}".$matches[3];
                    }, $source, 1);

                    $added++;
                    break;
                }
            }
        }

        if ($added > 0) {
            File::put($requestFile, $source);
        }

        return $added;
    }
}
