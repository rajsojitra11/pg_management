<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ModuleComplianceScanner
{
    /**
     * Boilerplate module catalog. Each new project should append entries
     * here as its modules ship — the audit commands (`module:audit`) read
     * these lists to know what type each module is and which sub-models
     * belong to which parent.
     */
    protected array $simpleCrudModules = [
        'City', 'Country', 'Currency', 'EnvVariable', 'MenuMaster',
        'Role', 'Setting', 'State', 'Unit', 'Year',
    ];

    protected array $entityMasterModules = [
        'Item',
    ];

    /** @var array<string> Modules with multi-step workflow + many sub-models. Empty in the boilerplate. */
    protected array $complexWorkflowModules = [];

    protected array $authConfigModules = [
        'Dashbord', 'Installer', 'Login', 'User',
    ];

    /** @var array<string> Models the scanner should skip (history snapshots, internal joins, etc.). */
    protected array $excludedModels = [];

    /** @var array<string> Stock-snapshot models that don't follow the standard *Log pattern. Empty in the boilerplate. */
    protected array $stockModels = [];

    /**
     * Parent → [sub-models] map. Each sub-model logs into the parent's
     * `<parent>_logs` table rather than maintaining its own.
     *
     * @var array<string, array<int, string>>
     */
    protected array $subModelPatterns = [];

    public function discoverAllModules(): array
    {
        $modulesPath = base_path('Modules');

        if (! File::exists($modulesPath)) {
            return [];
        }

        $modules = [];
        foreach (File::directories($modulesPath) as $dir) {
            $modules[] = basename($dir);
        }

        sort($modules);

        return $modules;
    }

    public function discoverModels(string $module): array
    {
        $modelsPath = base_path("Modules/{$module}/app/Models");

        if (! File::exists($modelsPath)) {
            return [];
        }

        $models = [];
        foreach (File::files($modelsPath) as $file) {
            $name = $file->getFilenameWithoutExtension();

            // Skip Log models — they ARE the audit log, not entities that need logging
            if (Str::endsWith($name, 'Log')) {
                continue;
            }

            // Skip Version models — immutable snapshot tables (e.g. SpecificationVersion,
            // SpecificationParameterVersion, ProcessRouteVersion). Per the Spec/Route v13
            // plan they're append-only audit-grade records that do NOT need
            // HasActivityLogging or their own log table — the snapshot row itself IS
            // the audit. Scanner originally flagged these as "missing trait/log model";
            // false positive — now skipped uniformly.
            if (Str::endsWith($name, 'Version')) {
                continue;
            }

            // Skip excluded models
            if (in_array($name, $this->excludedModels)) {
                continue;
            }

            $models[] = $name;
        }

        sort($models);

        return $models;
    }

    public function getModuleType(string $module): string
    {
        if (in_array($module, $this->simpleCrudModules)) {
            return 'simple_crud';
        }
        if (in_array($module, $this->entityMasterModules)) {
            return 'entity_master';
        }
        if (in_array($module, $this->complexWorkflowModules)) {
            return 'complex_workflow';
        }
        if (in_array($module, $this->authConfigModules)) {
            return 'auth_config';
        }

        return 'unknown';
    }

    public function getPrimaryModelName(string $module): ?string
    {
        $modelsPath = base_path("Modules/{$module}/app/Models");

        if (! File::exists($modelsPath)) {
            return null;
        }

        // The primary model typically matches the module name
        $candidates = [$module];

        foreach ($candidates as $name) {
            if (File::exists("{$modelsPath}/{$name}.php")) {
                return $name;
            }
        }

        // Fallback: return first non-log, non-excluded model
        $models = $this->discoverModels($module);

        return $models[0] ?? null;
    }

    public function isSubModel(string $module, string $model): bool
    {
        // Check explicit sub-model patterns first (handles edge cases like
        // Dispenseorder where no main model exists but DispenseorderRawMaterial is a sub-model)
        if (isset($this->subModelPatterns[$module]) && in_array($model, $this->subModelPatterns[$module])) {
            return true;
        }

        $primary = $this->getPrimaryModelName($module);

        if ($model === $primary) {
            return false;
        }

        // If not explicitly listed but not the primary, treat as sub-model
        return $model !== $primary;
    }

    public function isStockModel(string $model): bool
    {
        return in_array($model, $this->stockModels);
    }

    public function auditModule(string $module): array
    {
        $type = $this->getModuleType($module);
        $models = $this->discoverModels($module);
        $results = [
            'type' => $type,
            'models' => [],
        ];

        foreach ($models as $model) {
            $results['models'][$model] = [
                'file' => "Modules/{$module}/app/Models/{$model}.php",
                'checks' => $this->runChecks($module, $model),
            ];
        }

        return $results;
    }

    protected function runChecks(string $module, string $model): array
    {
        $checks = [];
        $isStock = $this->isStockModel($model);
        $isSub = $this->isSubModel($module, $model);
        $modelFile = base_path("Modules/{$module}/app/Models/{$model}.php");
        $modelContent = File::exists($modelFile) ? File::get($modelFile) : '';

        // Check 1: HasActivityLogging trait
        $checks[] = $this->checkHasActivityLogging($modelContent, $model);

        // Check 2: Log model exists
        $checks[] = $this->checkLogModel($module, $model);

        // Check 3: getLoggingConfig method
        $checks[] = $this->checkGetLoggingConfig($modelContent);

        // Stock models: only checks 1-3
        if ($isStock) {
            return $checks;
        }

        // Check 4: DeleteRequest
        if ($isSub) {
            $checks[] = [
                'name' => 'DeleteRequest',
                'status' => 'N/A',
                'detail' => 'Sub-model — delete handled by parent',
                'fixable' => false,
                'fix_type' => null,
            ];
        } else {
            $checks[] = $this->checkDeleteRequest($module, $model);
        }

        // Sub-models: only checks 1-4
        if ($isSub) {
            return $checks;
        }

        // Checks 5-10 only for primary/main models
        $checks[] = $this->checkViewExistence($module);

        return $checks;
    }

    protected function checkHasActivityLogging(string $content, string $model): array
    {
        if (empty($content)) {
            return [
                'name' => 'HasActivityLogging',
                'status' => 'FAIL',
                'detail' => 'Model file not found',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Trait use statements are indented (inside class body); import statements are not
        // Check if HasActivityLogging is active in a trait use statement (before the semicolon)
        if (preg_match('/^[ \t]+use\s+[^;]*\bHasActivityLogging\b[^;]*;/m', $content)) {
            return [
                'name' => 'HasActivityLogging',
                'status' => 'PASS',
                'detail' => 'Trait is active',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Check if it's commented out after the semicolon in a trait use statement
        if (preg_match('/^[ \t]+use\s+.*;\s*\/\/\s*HasActivityLogging/m', $content)) {
            return [
                'name' => 'HasActivityLogging',
                'status' => 'FAIL',
                'detail' => 'Trait is commented out',
                'fixable' => true,
                'fix_type' => 'uncomment_trait',
            ];
        }

        // Check if HasActivityLogging import exists but trait not used at all
        if (preg_match('/^use\s+App\\\\Traits\\\\HasActivityLogging;/m', $content)) {
            return [
                'name' => 'HasActivityLogging',
                'status' => 'FAIL',
                'detail' => 'Imported but not used in trait statement',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        return [
            'name' => 'HasActivityLogging',
            'status' => 'FAIL',
            'detail' => 'Trait not found',
            'fixable' => false,
            'fix_type' => null,
        ];
    }

    protected function checkLogModel(string $module, string $model): array
    {
        $modelsPath = base_path("Modules/{$module}/app/Models");

        // Direct match: {Model}Log.php
        if (File::exists("{$modelsPath}/{$model}Log.php")) {
            return [
                'name' => 'LogModel',
                'status' => 'PASS',
                'detail' => "{$model}Log.php exists",
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Check if the model's getLoggingConfig references a different log model
        $modelFile = "{$modelsPath}/{$model}.php";
        if (File::exists($modelFile)) {
            $content = File::get($modelFile);
            // Match: 'log_model' => SomeLog::class or fully qualified \Modules\...\SomeLog::class
            if (preg_match("/['\"]log_model['\"]\s*=>\s*([\w\\\\]+)::class/", $content, $matches)) {
                $referencedLog = Str::afterLast(str_replace('\\', '/', $matches[1]), '/');
                if (File::exists("{$modelsPath}/{$referencedLog}.php")) {
                    return [
                        'name' => 'LogModel',
                        'status' => 'PASS',
                        'detail' => "{$referencedLog}.php exists (via getLoggingConfig)",
                        'fixable' => false,
                        'fix_type' => null,
                    ];
                }
            }
        }

        // Case-insensitive fallback: scan Models directory for *Log.php matching model name
        if (File::exists($modelsPath)) {
            $modelLower = strtolower($model);
            foreach (File::files($modelsPath) as $file) {
                $name = $file->getFilenameWithoutExtension();
                if (strtolower($name) === $modelLower.'log') {
                    return [
                        'name' => 'LogModel',
                        'status' => 'PASS',
                        'detail' => "{$name}.php exists (case variant)",
                        'fixable' => false,
                        'fix_type' => null,
                    ];
                }
            }
        }

        return [
            'name' => 'LogModel',
            'status' => 'FAIL',
            'detail' => "{$model}Log.php missing",
            'fixable' => true,
            'fix_type' => 'create_log_model',
        ];
    }

    protected function checkGetLoggingConfig(string $content): array
    {
        if (empty($content)) {
            return [
                'name' => 'getLoggingConfig',
                'status' => 'FAIL',
                'detail' => 'Model file not found',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Check for active (uncommented) getLoggingConfig
        if (preg_match('/^\s*protected\s+function\s+getLoggingConfig\s*\(\s*\)\s*:\s*array/m', $content)) {
            return [
                'name' => 'getLoggingConfig',
                'status' => 'PASS',
                'detail' => 'Method is active',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Check for commented getLoggingConfig
        if (preg_match('/^\s*\/\/\s*protected\s+function\s+getLoggingConfig/m', $content)) {
            return [
                'name' => 'getLoggingConfig',
                'status' => 'FAIL',
                'detail' => 'Method is commented out',
                'fixable' => true,
                'fix_type' => 'uncomment_logging_config',
            ];
        }

        // Method not present at all — could auto-generate but not in scope
        return [
            'name' => 'getLoggingConfig',
            'status' => 'WARN',
            'detail' => 'Method not found (uses convention-based config)',
            'fixable' => false,
            'fix_type' => null,
        ];
    }

    protected function checkDeleteRequest(string $module, string $model): array
    {
        $requestFile = base_path("Modules/{$module}/app/Http/Requests/Delete{$model}Request.php");

        if (File::exists($requestFile)) {
            return [
                'name' => 'DeleteRequest',
                'status' => 'PASS',
                'detail' => "Delete{$model}Request.php exists",
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        return [
            'name' => 'DeleteRequest',
            'status' => 'FAIL',
            'detail' => "Delete{$model}Request.php missing",
            'fixable' => true,
            'fix_type' => 'create_delete_request',
        ];
    }

    /**
     * Verify that every `view('xxx::path.subpath')` call in a *routed* controller
     * method resolves to a real Blade file under resources/views/.
     *
     * Route-aware: ignores view() calls inside controller methods that are NOT
     * bound to any registered route (typical scaffolding leftovers from
     * `Route::resource(...)->except(['create', 'show'])`).
     *
     * Catches gaps like a controller returning view('labmaterial::lab-material-category.index')
     * when the file was never created — which silently 500s the page at runtime.
     */
    protected function checkViewExistence(string $module): array
    {
        $controllersPath = base_path("Modules/{$module}/app/Http/Controllers");
        $viewsPath = base_path("Modules/{$module}/resources/views");
        $namespace = $this->detectViewNamespace($module);

        if (! File::exists($controllersPath) || ! File::exists($viewsPath)) {
            return [
                'name' => 'View Existence',
                'status' => 'N/A',
                'detail' => 'Module has no controllers or views directory',
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        // Build the set of reachable controller@method actions from the route registry.
        $reachableActions = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (str_contains($action, '@')) {
                $reachableActions[$action] = true;
            }
        }

        $missing = [];
        foreach (File::allFiles($controllersPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = File::get($file->getPathname());
            $fqcn = $this->extractControllerFqcn($content);
            if (! $fqcn) {
                continue;
            }

            foreach ($this->extractRoutedMethodViewCalls($content, $namespace) as $methodName => $viewPaths) {
                if (! isset($reachableActions["{$fqcn}@{$methodName}"])) {
                    continue;
                }
                foreach ($viewPaths as $viewPath) {
                    $relPath = str_replace('.', '/', $viewPath).'.blade.php';
                    if (! File::exists("{$viewsPath}/{$relPath}")) {
                        $missing[] = "{$namespace}::{$viewPath} (in {$file->getRelativePathname()}::{$methodName})";
                    }
                }
            }
        }

        if (empty($missing)) {
            return [
                'name' => 'View Existence',
                'status' => 'PASS',
                'detail' => "All routed view('{$namespace}::*') references resolve to a Blade file",
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        return [
            'name' => 'View Existence',
            'status' => 'FAIL',
            'detail' => count($missing).' missing view(s): '.implode('; ', array_unique($missing)),
            'fixable' => false,
            'fix_type' => null,
        ];
    }

    /**
     * Detect the view namespace a module registers via loadViewsFrom().
     * Convention is the module's $nameLower (e.g. ProcessStage → 'processstage');
     * falls back to a normalized lowercase module name if the provider can't be parsed.
     */
    protected function detectViewNamespace(string $module): string
    {
        $providerPath = base_path("Modules/{$module}/app/Providers");
        if (File::exists($providerPath)) {
            foreach (File::allFiles($providerPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $content = File::get($file->getPathname());
                if (preg_match("/protected\\s+string\\s+\\\$nameLower\\s*=\\s*['\"]([\\w-]+)['\"]/", $content, $m)) {
                    return $m[1];
                }
            }
        }

        return strtolower(str_replace(['-', '_', ' '], '', $module));
    }

    /**
     * Pull `Namespace\ClassName` from a controller file's `namespace` + `class` declarations.
     */
    protected function extractControllerFqcn(string $content): ?string
    {
        if (! preg_match('/^\s*namespace\s+([^;]+);/m', $content, $nsM)) {
            return null;
        }
        if (! preg_match('/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $content, $clsM)) {
            return null;
        }

        return trim($nsM[1]).'\\'.$clsM[1];
    }

    /**
     * Parse a controller body, extract each public/protected method's body
     * via brace-balanced scanning, and return a map of method name → view paths
     * referenced via `view('{namespace}::path')`.
     */
    protected function extractRoutedMethodViewCalls(string $content, string $namespace): array
    {
        $result = [];
        $sigPattern = '/\bfunction\s+(\w+)\s*\([^)]*\)[^{;]*\{/';
        $pos = 0;
        $len = strlen($content);

        while (preg_match($sigPattern, $content, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $methodName = $m[1][0];
            $bodyStart = $m[0][1] + strlen($m[0][0]); // index just past the opening `{`
            $depth = 1;
            $i = $bodyStart;

            while ($i < $len && $depth > 0) {
                $ch = $content[$i];
                if ($ch === '"' || $ch === '\'') {
                    // skip string literal (incl. escaped quotes)
                    $quote = $ch;
                    $i++;
                    while ($i < $len) {
                        if ($content[$i] === '\\' && $i + 1 < $len) {
                            $i += 2;

                            continue;
                        }
                        if ($content[$i] === $quote) {
                            $i++;
                            break;
                        }
                        $i++;
                    }

                    continue;
                }
                if ($ch === '/' && $i + 1 < $len && $content[$i + 1] === '/') {
                    while ($i < $len && $content[$i] !== "\n") {
                        $i++;
                    }

                    continue;
                }
                if ($ch === '/' && $i + 1 < $len && $content[$i + 1] === '*') {
                    $i += 2;
                    while ($i + 1 < $len && ! ($content[$i] === '*' && $content[$i + 1] === '/')) {
                        $i++;
                    }
                    $i += 2;

                    continue;
                }
                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                }
                $i++;
            }

            $body = substr($content, $bodyStart, $i - $bodyStart - 1);
            $viewPattern = "/view\\(\\s*['\"]".preg_quote($namespace, '/')."::([\\w.\\-\\/]+)['\"]/i";
            if (preg_match_all($viewPattern, $body, $vm)) {
                $result[$methodName] = array_values(array_unique($vm[1]));
            }

            $pos = $i;
        }

        return $result;
    }

    protected function checkRequestField(string $module, string $model, string $action, string $checkName, array $fieldPatterns): array
    {
        $requestFile = base_path("Modules/{$module}/app/Http/Requests/{$action}{$model}Request.php");

        if (! File::exists($requestFile)) {
            $status = $action === 'Delete' ? 'FAIL' : 'WARN';

            return [
                'name' => $checkName,
                'status' => $status,
                'detail' => "{$action}{$model}Request.php not found",
                'fixable' => false,
                'fix_type' => null,
            ];
        }

        $content = File::get($requestFile);

        foreach ($fieldPatterns as $pattern) {
            if (Str::contains($content, $pattern)) {
                return [
                    'name' => $checkName,
                    'status' => 'PASS',
                    'detail' => "{$pattern} found in {$action}Request",
                    'fixable' => false,
                    'fix_type' => null,
                ];
            }
        }

        return [
            'name' => $checkName,
            'status' => 'FAIL',
            'detail' => 'Field not found in '.$action.'Request',
            'fixable' => false,
            'fix_type' => null,
        ];
    }

    // ===== Fix Methods =====

    public function fixUncommentTrait(string $module, string $model): array
    {
        $modelFile = base_path("Modules/{$module}/app/Models/{$model}.php");

        if (! File::exists($modelFile)) {
            return ['success' => false, 'message' => 'Model file not found'];
        }

        $content = File::get($modelFile);
        $original = $content;

        // Match: "use <traits>; // HasActivityLogging," or ";// HasActivityLogging," or ";//HasActivityLogging,"
        // Also handle trailing space after comma: ";//HasActivityLogging, "
        $content = preg_replace(
            '/^(\s*use\s+)(.*?);\s*\/\/\s*HasActivityLogging,?\s*$/m',
            '$1HasActivityLogging, $2;',
            $content
        );

        // Clean up double spaces in use statement
        $content = preg_replace('/\buse\s{2,}/', 'use ', $content);

        if ($content === $original) {
            return ['success' => false, 'message' => 'No commented HasActivityLogging found to uncomment'];
        }

        File::put($modelFile, $content);

        return ['success' => true, 'message' => 'HasActivityLogging trait uncommented'];
    }

    public function fixUncommentLoggingConfig(string $module, string $model): array
    {
        $modelFile = base_path("Modules/{$module}/app/Models/{$model}.php");

        if (! File::exists($modelFile)) {
            return ['success' => false, 'message' => 'Model file not found'];
        }

        $content = File::get($modelFile);
        $original = $content;

        // Find and uncomment the getLoggingConfig block
        // Match lines starting with "    // " from "protected function getLoggingConfig" through closing "}"
        $content = preg_replace_callback(
            '/^([ \t]*\/\/\s*protected\s+function\s+getLoggingConfig\s*\(\s*\)\s*:\s*array\s*\n(?:[ \t]*\/\/.*\n)*)/m',
            function ($matches) {
                $block = $matches[0];
                // Remove "// " prefix from each line, preserving indentation
                $lines = explode("\n", $block);
                $uncommented = [];
                foreach ($lines as $line) {
                    // Remove the comment prefix: "    // " -> "    "
                    $uncommented[] = preg_replace('/^(\s*)\/\/\s?/', '$1', $line);
                }

                return implode("\n", $uncommented);
            },
            $content
        );

        if ($content === $original) {
            return ['success' => false, 'message' => 'No commented getLoggingConfig found to uncomment'];
        }

        File::put($modelFile, $content);

        return ['success' => true, 'message' => 'getLoggingConfig() method uncommented'];
    }

    public function fixCreateLogModel(string $module, string $model): array
    {
        $logModelFile = base_path("Modules/{$module}/app/Models/{$model}Log.php");

        if (File::exists($logModelFile)) {
            return ['success' => false, 'message' => "{$model}Log.php already exists"];
        }

        // Use existing artisan command
        try {
            $exitCode = \Artisan::call('module:make-log', [
                'model' => $model,
                'module' => $module,
            ]);

            if ($exitCode === 0) {
                return ['success' => true, 'message' => "{$model}Log model and migration created"];
            }

            return ['success' => false, 'message' => 'module:make-log command failed (exit code: '.$exitCode.')'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    public function fixCreateDeleteRequest(string $module, string $model): array
    {
        $requestFile = base_path("Modules/{$module}/app/Http/Requests/Delete{$model}Request.php");

        if (File::exists($requestFile)) {
            return ['success' => false, 'message' => "Delete{$model}Request.php already exists"];
        }

        // Read table name from model
        $modelFile = base_path("Modules/{$module}/app/Models/{$model}.php");
        $tableName = $this->extractTableName($modelFile, $model);

        $namespace = "Modules\\{$module}\\Http\\Requests";

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;

class Delete{$model}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:{$tableName},id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'id.required' => '{$model} ID is required for deletion.',
            'id.exists' => 'Selected {$model} does not exist.',
        ];
    }
}

PHP;

        // Ensure Requests directory exists
        $requestsDir = dirname($requestFile);
        if (! File::exists($requestsDir)) {
            File::makeDirectory($requestsDir, 0755, true);
        }

        File::put($requestFile, $content);

        return ['success' => true, 'message' => "Delete{$model}Request.php created"];
    }

    protected function extractTableName(string $modelFile, string $model): string
    {
        if (File::exists($modelFile)) {
            $content = File::get($modelFile);

            // Match: $table = 'table_name'; or protected $table = 'table_name';
            if (preg_match('/\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                return $matches[1];
            }
        }

        // Fallback: generate table name from model using Laravel convention
        return Str::snake(Str::pluralStudly($model));
    }

    // ===== Status File Update =====

    public function updateModuleStatusFile(array $fixes): int
    {
        $statusFile = base_path('docs/module-status.md');

        if (! File::exists($statusFile)) {
            return 0;
        }

        $content = File::get($statusFile);
        $updatedCount = 0;

        foreach ($fixes as $fix) {
            if (! $fix['success']) {
                continue;
            }

            $module = $fix['module'];
            $model = $fix['model'];
            $fixType = $fix['fix_type'];

            switch ($fixType) {
                case 'uncomment_trait':
                    // Change COMMENTED -> YES in Logging column for this model
                    $content = $this->updateStatusTableCell($content, $model, 'Logging', 'COMMENTED', 'YES');
                    // Remove from P1 Known Gaps table
                    $content = $this->removeFromKnownGaps($content, $module, $model, 'P1');
                    $updatedCount++;
                    break;

                case 'uncomment_logging_config':
                    // Remove from P5 Known Gaps
                    $content = $this->removeFromKnownGaps($content, $module, $model, 'P5');
                    $updatedCount++;
                    break;

                case 'create_log_model':
                    // Change NO -> YES in Log Model column
                    $content = $this->updateStatusTableCell($content, $model, 'Log Model', 'NO', "{$model}Log");
                    // Remove from P2 Known Gaps
                    $content = $this->removeFromKnownGaps($content, $module, $model, 'P2');
                    $updatedCount++;
                    break;

                case 'create_delete_request':
                    // Change NO -> YES in Rmk-Delete and BD-Delete columns
                    $content = $this->updateStatusTableCell($content, $model, 'Rmk-Delete', 'NO', 'YES');
                    $content = $this->updateStatusTableCell($content, $model, 'BD-Delete', 'NO', 'YES');
                    // Remove from P3 Known Gaps
                    $content = $this->removeFromKnownGaps($content, $module, $model, 'P3');
                    $updatedCount++;
                    break;
            }
        }

        if ($updatedCount > 0) {
            File::put($statusFile, $content);
        }

        return $updatedCount;
    }

    protected function updateStatusTableCell(string $content, string $model, string $columnName, string $oldValue, string $newValue): string
    {
        // Find markdown table rows containing the model name
        $lines = explode("\n", $content);
        $headerIndices = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Find header row containing our column
            if (preg_match('/\|/', $line) && Str::contains($line, $columnName)) {
                // Parse column index
                $columns = array_map('trim', explode('|', $line));
                $colIndex = null;
                foreach ($columns as $idx => $col) {
                    if ($col === $columnName) {
                        $colIndex = $idx;
                        break;
                    }
                }

                if ($colIndex === null) {
                    continue;
                }

                // Skip separator line (next line with dashes)
                // Look at subsequent data rows for our model
                for ($j = $i + 2; $j < count($lines); $j++) {
                    $dataLine = $lines[$j];

                    // Stop if we hit an empty line or non-table line
                    if (! preg_match('/^\|/', $dataLine)) {
                        break;
                    }

                    $dataCols = array_map('trim', explode('|', $dataLine));

                    // Check if this row is for our model (column 1 typically)
                    if (isset($dataCols[1]) && trim($dataCols[1]) === $model) {
                        // Update the target column
                        if (isset($dataCols[$colIndex]) && trim($dataCols[$colIndex]) === $oldValue) {
                            $dataCols[$colIndex] = ' '.$newValue.' ';
                            $lines[$j] = implode('|', $dataCols);
                        }
                        break;
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function removeFromKnownGaps(string $content, string $module, string $model, string $priority): string
    {
        $lines = explode("\n", $content);
        $result = [];
        $inPrioritySection = false;
        $inTable = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Detect priority section headers
            if (preg_match('/^###\s+'.preg_quote($priority, '/').'\s/', $line)) {
                $inPrioritySection = true;
                $result[] = $line;

                continue;
            }

            // Detect next section header (exit priority section)
            if ($inPrioritySection && preg_match('/^###?\s/', $line) && ! preg_match('/^###\s+'.preg_quote($priority, '/').'\s/', $line)) {
                $inPrioritySection = false;
            }

            if ($inPrioritySection && preg_match('/^\|/', $line)) {
                $inTable = true;

                // Check if this data row contains our module and model
                if (Str::contains($line, $module) && Str::contains($line, $model)) {
                    // Skip this row (remove it from gaps)
                    continue;
                }
            }

            if ($inPrioritySection && $inTable && ! preg_match('/^\|/', $line) && trim($line) !== '') {
                $inTable = false;
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    public function getFixableChecks(array $auditResults): array
    {
        $fixable = [];

        foreach ($auditResults['models'] as $model => $data) {
            foreach ($data['checks'] as $check) {
                if ($check['status'] === 'FAIL' && $check['fixable']) {
                    $fixable[] = array_merge($check, ['model' => $model]);
                }
            }
        }

        return $fixable;
    }
}
