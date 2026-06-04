<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan project-knowledge:check` — detect drift between
 * `.claude/project-knowledge/modules/<Name>.md` files and the actual codebase.
 *
 * The agent system depends on these deep-knowledge files being accurate. When
 * code changes (new column added, env flag added, route added), the matching
 * `<Module>.md` file should be updated. Otherwise agents work from stale facts.
 *
 * Checks:
 *   1. Every `Modules/<Name>` has a `<Name>.md` deep-knowledge file
 *   2. Tables mentioned in module files exist in the database (via Schema)
 *   3. Env flags mentioned in module files exist in `.env` (or `.env.example`)
 *   4. file:line citations resolve to existing files (line-bounds check optional)
 *
 * With --check-docs, ALSO verifies the human-facing `docs/` layer:
 *   5. Every Modules/<Name> has a `docs/03-modules/<Name>/developer.md`
 *   6. Tier 1 modules also have a `docs/03-modules/<Name>/user-guide.md`
 *   7. Tables/env flags mentioned in docs/ files exist (same as 2-3 above)
 *   8. Cross-references in docs/ to other docs/ files resolve
 *
 * Exit codes:
 *   0 — no drift
 *   1 — drift detected (use --strict in CI to fail the build)
 */
class ProjectKnowledgeCheckCommand extends Command
{
    protected $signature = 'project-knowledge:check
                            {module? : Single module to check}
                            {--strict : Exit non-zero on any drift finding (CI gate)}
                            {--json : Machine-readable output}
                            {--missing-only : Only report modules without a deep-knowledge file}
                            {--check-docs : Also verify the human-facing docs/ layer}
                            {--docs-only : Skip .claude/project-knowledge/ checks; only verify docs/ layer}';

    protected $description = 'Detect drift between .claude/project-knowledge/modules/*.md and the codebase. Optionally verify docs/ layer with --check-docs.';

    /** Tier 1 modules — must have BOTH developer.md AND user-guide.md in docs/03-modules/ */
    private const TIER_1_MODULES = [
        'Production', 'Processorder', 'Dispenseorder',
        'Purchase', 'PurchaseReceive',
        'MaterialIssue', 'ExtraMaterialIssue',
        'Testing', 'Specification', 'Formulation',
        'Rawmaterial', 'ChangeRequest',
    ];

    private string $modulesDir;

    private string $knowledgeDir;

    private string $docsModulesDir;

    public function handle(): int
    {
        $this->modulesDir = base_path('Modules');
        $this->knowledgeDir = base_path('.claude/project-knowledge/modules');
        $this->docsModulesDir = base_path('docs/03-modules');

        $checkAgentLayer = ! $this->option('docs-only');
        $checkDocsLayer = $this->option('check-docs') || $this->option('docs-only');

        if ($checkAgentLayer && ! is_dir($this->knowledgeDir)) {
            $this->error("Project-knowledge directory missing: {$this->knowledgeDir}");
            $this->line('Did you complete the agent-system installation?');

            return self::FAILURE;
        }

        if ($checkDocsLayer && ! is_dir($this->docsModulesDir)) {
            $this->error("Docs modules directory missing: {$this->docsModulesDir}");
            $this->line('Did you complete the docs/ scaffolding (D1-D5)?');

            return self::FAILURE;
        }

        $singleModule = $this->argument('module');
        $modules = $singleModule
            ? [$singleModule]
            : collect(scandir($this->modulesDir))
                ->reject(fn ($d) => in_array($d, ['.', '..'], true) || ! is_dir("{$this->modulesDir}/{$d}"))
                ->values()
                ->all();

        $findings = [];
        $totals = [
            'missing' => 0, 'stale_table' => 0, 'stale_env' => 0, 'stale_file_ref' => 0,
            'docs_missing_developer' => 0, 'docs_missing_user_guide' => 0,
            'docs_stale_table' => 0, 'docs_stale_env' => 0, 'docs_stale_file_ref' => 0,
            'docs_stale_xref' => 0,
            'ok' => 0,
        ];

        foreach ($modules as $module) {
            $perModuleFindings = [];

            // Layer 1: Agent-system deep-knowledge (.claude/project-knowledge/)
            if ($checkAgentLayer) {
                $kbFile = "{$this->knowledgeDir}/{$module}.md";

                if (! is_file($kbFile)) {
                    $perModuleFindings[] = [
                        'module' => $module,
                        'layer' => 'agent',
                        'severity' => 'high',
                        'kind' => 'missing',
                        'detail' => "No deep-knowledge file at {$kbFile}",
                    ];
                } elseif (! $this->option('missing-only')) {
                    $kbContent = file_get_contents($kbFile);
                    foreach ($this->checkModule($module, $kbContent) as $f) {
                        $f['layer'] = 'agent';
                        $perModuleFindings[] = $f;
                    }
                }
            }

            // Layer 2: Human-facing docs/ (added by --check-docs)
            if ($checkDocsLayer) {
                foreach ($this->checkDocsLayer($module) as $f) {
                    $f['layer'] = 'docs';
                    $perModuleFindings[] = $f;
                }
            }

            foreach ($perModuleFindings as $f) {
                $findings[] = $f;
                $totals[$f['kind']] = ($totals[$f['kind']] ?? 0) + 1;
            }
            if (empty($perModuleFindings)) {
                $totals['ok']++;
            }
        }

        // Output
        if ($this->option('json')) {
            $this->line(json_encode([
                'totals' => $totals,
                'findings' => $findings,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->renderHuman($findings, $totals);
        }

        $hasDrift = collect($findings)->isNotEmpty();

        return ($hasDrift && $this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Per-module checks.
     *
     * @return array<int, array{module: string, severity: string, kind: string, detail: string}>
     */
    private function checkModule(string $module, string $kb): array
    {
        $findings = [];

        // 1. Tables: any backtick-quoted table name `foo_logs` or `foos` should exist
        if (preg_match_all('/`([a-z_][a-z0-9_]*?)`/', $kb, $m)) {
            $candidates = collect($m[1])->unique()->reject(function ($name) {
                // Filter out plain words that aren't likely tables
                return strlen($name) < 3
                    || ! str_contains($name, '_')
                    || str_ends_with($name, '_id')
                    || str_starts_with($name, 'is_')
                    || in_array($name, ['public_id', 'foreign_key', 'name_field', 'log_model', 'model_name', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'], true);
            });

            foreach ($candidates as $tableCandidate) {
                // Heuristic: ends with _s or _logs or matches known table-shape
                if (! preg_match('/(s|_logs|_categories|_orders|_metas|_versions)$/', $tableCandidate)) {
                    continue;
                }
                try {
                    if (! Schema::hasTable($tableCandidate)) {
                        $findings[] = [
                            'module' => $module,
                            'severity' => 'medium',
                            'kind' => 'stale_table',
                            'detail' => "Table `{$tableCandidate}` mentioned in {$module}.md does not exist in DB",
                        ];
                    }
                } catch (\Throwable $e) {
                    // Schema check failed — DB unreachable, skip
                    return $findings;
                }
            }
        }

        // 2. Env flags: pattern AUTO_*, ENABLE_*, MANAGE_*, SHOW_*, etc.
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');
        $envContent = (is_file($envPath) ? file_get_contents($envPath) : '')
            .(is_file($envExamplePath) ? file_get_contents($envExamplePath) : '');

        if (preg_match_all('/`([A-Z][A-Z0-9_]{4,})`/', $kb, $m)) {
            $envCandidates = collect($m[1])->unique();
            foreach ($envCandidates as $env) {
                // Skip non-env-like uppercase tokens
                if (in_array($env, ['BLOCKER', 'MAJOR', 'MINOR', 'INFO', 'CRUD', 'JSON', 'AJAX', 'YAML', 'PASS', 'FAIL', 'API', 'PDF', 'GST', 'PAN', 'TAN', 'SOP', 'IST', 'FY', 'ISO', 'HTTP', 'HTTPS', 'POST', 'DELETE', 'GET', 'PUT', 'PATCH', 'CI', 'CD', 'TLS', 'SSL', 'URL', 'URI'], true)) {
                    continue;
                }
                if (! str_contains($envContent, $env)) {
                    $findings[] = [
                        'module' => $module,
                        'severity' => 'medium',
                        'kind' => 'stale_env',
                        'detail' => "Env `{$env}` mentioned in {$module}.md not found in .env or .env.example",
                    ];
                }
            }
        }

        // 3. file:line citations — `path/to/file.php:NN` should resolve to existing file
        if (preg_match_all('/`([A-Za-z0-9_\/\-\\\.]+\.(?:php|blade\.php|md))(?::(\d+(?:-\d+)?))?`/', $kb, $m)) {
            $referencedFiles = collect($m[1])->unique();
            foreach ($referencedFiles as $relPath) {
                $abs = base_path($relPath);
                // Skip relative module-internal references that we'd need a base for
                if (! file_exists($abs) && ! str_contains($relPath, '<')) {
                    $findings[] = [
                        'module' => $module,
                        'severity' => 'low',
                        'kind' => 'stale_file_ref',
                        'detail' => "File `{$relPath}` mentioned in {$module}.md does not exist",
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * Layer 2 checks — the human-facing docs/ layer (added by --check-docs).
     *
     * @return array<int, array{module: string, severity: string, kind: string, detail: string}>
     */
    private function checkDocsLayer(string $module): array
    {
        $findings = [];
        $moduleDir = "{$this->docsModulesDir}/{$module}";
        $devFile = "{$moduleDir}/developer.md";
        $userFile = "{$moduleDir}/user-guide.md";
        $isTier1 = in_array($module, self::TIER_1_MODULES, true);

        // 5. Every module should have a developer.md
        if (! is_file($devFile)) {
            $findings[] = [
                'module' => $module,
                'severity' => 'high',
                'kind' => 'docs_missing_developer',
                'detail' => "No human-facing developer.md at docs/03-modules/{$module}/developer.md",
            ];

            // Without developer.md, can't do further checks
            return $findings;
        }

        if ($this->option('missing-only')) {
            // 6. Tier 1 must also have user-guide.md
            if ($isTier1 && ! is_file($userFile)) {
                $findings[] = [
                    'module' => $module,
                    'severity' => 'high',
                    'kind' => 'docs_missing_user_guide',
                    'detail' => "Tier 1 module missing docs/03-modules/{$module}/user-guide.md",
                ];
            }

            return $findings;
        }

        // 6. Tier 1 must also have user-guide.md
        if ($isTier1 && ! is_file($userFile)) {
            $findings[] = [
                'module' => $module,
                'severity' => 'high',
                'kind' => 'docs_missing_user_guide',
                'detail' => "Tier 1 module missing docs/03-modules/{$module}/user-guide.md",
            ];
        }

        // 7-8. Run table/env/file/xref checks against developer.md (and user-guide.md if present)
        $files = [$devFile];
        if (is_file($userFile)) {
            $files[] = $userFile;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            // Tables (same logic as agent layer, but flag with docs_ prefix)
            if (preg_match_all('/`([a-z_][a-z0-9_]*?)`/', $content, $m)) {
                $candidates = collect($m[1])->unique()->reject(function ($name) {
                    return strlen($name) < 3
                        || ! str_contains($name, '_')
                        || str_ends_with($name, '_id')
                        || str_starts_with($name, 'is_')
                        || in_array($name, ['public_id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'manager_id', 'head_id'], true);
                });

                foreach ($candidates as $tableCandidate) {
                    if (! preg_match('/(s|_logs|_categories|_orders|_metas|_versions|_locations|_associates|_specifications)$/', $tableCandidate)) {
                        continue;
                    }
                    try {
                        if (! Schema::hasTable($tableCandidate)) {
                            $findings[] = [
                                'module' => $module,
                                'severity' => 'medium',
                                'kind' => 'docs_stale_table',
                                'detail' => "Table `{$tableCandidate}` mentioned in {$relPath} does not exist in DB",
                            ];
                        }
                    } catch (\Throwable $e) {
                        return $findings;
                    }
                }
            }

            // Env flags
            $envPath = base_path('.env');
            $envExamplePath = base_path('.env.example');
            $envContent = (is_file($envPath) ? file_get_contents($envPath) : '')
                .(is_file($envExamplePath) ? file_get_contents($envExamplePath) : '');

            if (preg_match_all('/`([A-Z][A-Z0-9_]{4,})`/', $content, $m)) {
                $envCandidates = collect($m[1])->unique();
                foreach ($envCandidates as $env) {
                    if (in_array($env, ['BLOCKER', 'MAJOR', 'MINOR', 'INFO', 'CRUD', 'JSON', 'AJAX', 'YAML', 'PASS', 'FAIL', 'API', 'PDF', 'GST', 'PAN', 'TAN', 'SOP', 'IST', 'FY', 'ISO', 'HTTP', 'HTTPS', 'POST', 'DELETE', 'GET', 'PUT', 'PATCH', 'CI', 'CD', 'TLS', 'SSL', 'URL', 'URI'], true)) {
                        continue;
                    }
                    if (! str_contains($envContent, $env)) {
                        $findings[] = [
                            'module' => $module,
                            'severity' => 'medium',
                            'kind' => 'docs_stale_env',
                            'detail' => "Env `{$env}` mentioned in {$relPath} not found in .env or .env.example",
                        ];
                    }
                }
            }

            // Cross-references — markdown links like [`text`](path/to/file.md)
            if (preg_match_all('/\]\((\.\.\/[A-Za-z0-9_\.\/\-]+\.md)\)/', $content, $m)) {
                $refs = collect($m[1])->unique();
                $fileDir = dirname($file);
                foreach ($refs as $ref) {
                    $resolved = realpath("{$fileDir}/{$ref}");
                    if ($resolved === false || ! is_file($resolved)) {
                        // Skip broken links to template module folders (../ModuleName/) common in lists
                        if (str_ends_with($ref, '/') || preg_match('/\/<[A-Za-z]+>\//', $ref)) {
                            continue;
                        }
                        $findings[] = [
                            'module' => $module,
                            'severity' => 'low',
                            'kind' => 'docs_stale_xref',
                            'detail' => "Cross-ref `{$ref}` in {$relPath} doesn't resolve",
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    private function renderHuman(array $findings, array $totals): void
    {
        $this->newLine();
        $title = 'project-knowledge:check — drift detection';
        if ($this->option('check-docs')) {
            $title .= ' (BOTH layers)';
        } elseif ($this->option('docs-only')) {
            $title .= ' (docs/ layer only)';
        }
        $this->components->info($title);
        $this->newLine();

        if (empty($findings)) {
            $this->components->success('No drift detected. All knowledge files match the codebase.');

            return;
        }

        // Group by module
        $byModule = collect($findings)->groupBy('module');

        foreach ($byModule as $module => $items) {
            $this->components->twoColumnDetail("<fg=yellow>{$module}</>", count($items).' finding(s)');
            foreach ($items as $f) {
                $color = match ($f['severity']) {
                    'high' => 'red',
                    'medium' => 'yellow',
                    default => 'gray',
                };
                $layerTag = isset($f['layer']) ? "[{$f['layer']}]" : '';
                $this->line("  <fg={$color}>•</> {$layerTag} [{$f['kind']}] {$f['detail']}");
            }
            $this->newLine();
        }

        $this->components->twoColumnDetail('Modules clean', (string) ($totals['ok'] ?? 0));

        // Agent layer totals
        if (! $this->option('docs-only')) {
            $this->components->twoColumnDetail('Agent: missing knowledge file', (string) ($totals['missing'] ?? 0));
            $this->components->twoColumnDetail('Agent: stale table refs', (string) ($totals['stale_table'] ?? 0));
            $this->components->twoColumnDetail('Agent: stale env refs', (string) ($totals['stale_env'] ?? 0));
            $this->components->twoColumnDetail('Agent: stale file refs', (string) ($totals['stale_file_ref'] ?? 0));
        }

        // Docs layer totals
        if ($this->option('check-docs') || $this->option('docs-only')) {
            $this->components->twoColumnDetail('Docs: missing developer.md', (string) ($totals['docs_missing_developer'] ?? 0));
            $this->components->twoColumnDetail('Docs: Tier 1 missing user-guide.md', (string) ($totals['docs_missing_user_guide'] ?? 0));
            $this->components->twoColumnDetail('Docs: stale table refs', (string) ($totals['docs_stale_table'] ?? 0));
            $this->components->twoColumnDetail('Docs: stale env refs', (string) ($totals['docs_stale_env'] ?? 0));
            $this->components->twoColumnDetail('Docs: stale cross-refs', (string) ($totals['docs_stale_xref'] ?? 0));
        }

        $this->components->twoColumnDetail('TOTAL FINDINGS', (string) count($findings));

        $this->newLine();
        $this->line('To rebuild knowledge files, invoke the documentation-agent for that module.');
        $this->line('To suppress a finding, edit the relevant .md to remove the stale reference.');

        if (! $this->option('check-docs') && ! $this->option('docs-only')) {
            $this->newLine();
            $this->line('<fg=cyan>Tip:</> Add --check-docs to verify the human-facing docs/ layer too.');
        }
    }
}
