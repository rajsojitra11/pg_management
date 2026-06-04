<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Audit: every model using HasActivityLogging must declare a `belongsTo`
 * (typed OR untyped-with-source-match) for every `*_id` fillable column,
 * so the FK enrichment can produce a `*_id_label` in the audit log.
 *
 * Columns in the skip-list (`created_by`, `updated_by`, `deleted_by`) are
 * exempt — they're audit-only FKs and intentionally not enriched.
 *
 * Failure of this test means a real audit gap: an entity with a foreign key
 * whose label won't resolve in the audit trail. Fix by adding a `belongsTo`
 * relation method (or, if the column genuinely isn't a FK, add it to the
 * `KNOWN_NON_FK_COLUMNS` exception list below with a justification comment).
 */
class HasActivityLoggingFkCoverageAuditTest extends TestCase
{
    /**
     * Columns that LOOK like FKs (end in `_id`) but legitimately aren't.
     * Each entry should be paired with a comment explaining why.
     */
    private const KNOWN_NON_FK_COLUMNS = [
        // No known cases yet — left empty so additions force a code-review moment.
    ];

    private const SKIP_FK_COLUMNS = ['created_by', 'updated_by', 'deleted_by'];

    public function test_every_logged_model_has_belongs_to_for_every_id_fillable_column(): void
    {
        $modelFiles = $this->discoverLoggedModels();
        $this->assertNotEmpty($modelFiles, 'Expected to find HasActivityLogging models');

        $unmapped = [];
        $audited = 0;

        foreach ($modelFiles as $file) {
            $audited++;
            $report = $this->auditModelViaReflection($file);
            if (! empty($report['unmapped'])) {
                $unmapped[$report['class']] = $report['unmapped'];
            }
        }

        $this->assertGreaterThan(50, $audited, 'Audit should cover at least 50 logged models');

        if (! empty($unmapped)) {
            $message = "FK coverage gaps found in HasActivityLogging models:\n\n";
            foreach ($unmapped as $class => $cols) {
                $message .= "  {$class}\n";
                foreach ($cols as $col) {
                    $message .= "    - {$col} (no belongsTo declared)\n";
                }
            }
            $message .= "\nFix by adding a belongsTo relation method in each model, or add to KNOWN_NON_FK_COLUMNS with justification.";
            $this->fail($message);
        }

        $this->assertEmpty($unmapped, 'All FK columns must have a belongsTo declared');
    }

    /**
     * Use the same detection logic as the runtime trait (reflection on
     * `belongsTo` methods). This is the source of truth; if the runtime
     * detector finds a relation, the audit accepts it.
     *
     * @return array{class: string, unmapped: string[]}
     */
    private function auditModelViaReflection(string $file): array
    {
        $source = file_get_contents($file);
        if (! preg_match('/namespace\s+([^;]+);/', $source, $nsMatch)
            || ! preg_match('/class\s+(\w+)/', $source, $classMatch)) {
            return ['class' => $file, 'unmapped' => []];
        }
        $fqcn = trim($nsMatch[1]).'\\'.$classMatch[1];
        if (! class_exists($fqcn)) {
            return ['class' => $fqcn, 'unmapped' => []];
        }

        try {
            /** @var Model $instance */
            $instance = new $fqcn;
        } catch (\Throwable $e) {
            return ['class' => $fqcn, 'unmapped' => []];
        }

        // Replicate HasActivityLogging detection: belongsTo + morphTo
        $detectedFkColumns = array_merge(
            $this->detectBelongsToFkColumns($instance),
            $this->detectMorphToIdColumns($instance)
        );

        // Get fillable, find *_id columns
        $fillable = $this->extractFillable($source);
        $idColumns = array_filter($fillable, function ($col) {
            return str_ends_with($col, '_id')
                && ! in_array($col, self::SKIP_FK_COLUMNS, true)
                && ! in_array($col, self::KNOWN_NON_FK_COLUMNS, true);
        });

        $unmapped = array_values(array_diff($idColumns, $detectedFkColumns));

        return ['class' => $fqcn, 'unmapped' => $unmapped];
    }

    /**
     * Mirrors the runtime detector — invokes every public no-arg method that
     * has a typed BelongsTo return OR contains `belongsTo(` in its source body.
     * Returns the FK column names actually wired to a relation.
     *
     * @return string[]
     */
    private function detectBelongsToFkColumns(Model $instance): array
    {
        $belongsToFqcn = BelongsTo::class;
        $cols = [];
        $fileSourceCache = [];
        try {
            $reflection = new \ReflectionClass($instance);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $declaringClass = $method->getDeclaringClass()->getName();
                if (! str_starts_with($declaringClass, 'Modules\\') && ! str_starts_with($declaringClass, 'App\\')) {
                    continue;
                }
                if ($method->getNumberOfRequiredParameters() > 0
                    || $method->isStatic() || $method->isAbstract()
                    || $method->isConstructor() || $method->isDestructor()) {
                    continue;
                }
                $rt = $method->getReturnType();
                $isTypedBelongsTo = $rt instanceof \ReflectionNamedType && $rt->getName() === $belongsToFqcn;

                $looksLikeRelation = $isTypedBelongsTo;
                if (! $looksLikeRelation) {
                    $f = $method->getFileName();
                    if ($f && is_readable($f)) {
                        if (! isset($fileSourceCache[$f])) {
                            $fileSourceCache[$f] = @file($f) ?: [];
                        }
                        $body = implode('', array_slice(
                            $fileSourceCache[$f],
                            (int) $method->getStartLine() - 1,
                            (int) $method->getEndLine() - (int) $method->getStartLine() + 1
                        ));
                        if (str_contains($body, 'belongsTo(')) {
                            $looksLikeRelation = true;
                        }
                    }
                }
                if (! $looksLikeRelation) {
                    continue;
                }
                try {
                    $relation = $method->invoke($instance);
                    if ($relation instanceof BelongsTo) {
                        $cols[] = $relation->getForeignKeyName();
                    }
                } catch (\Throwable $e) {
                    // skip
                }
            }
        } catch (\Throwable $e) {
            // skip
        }

        return array_values(array_unique($cols));
    }

    /**
     * Detect morphTo relations and return their `*_id` columns. Mirror of
     * the runtime trait's morphTo detector.
     *
     * @return string[]
     */
    private function detectMorphToIdColumns(Model $instance): array
    {
        $cols = [];
        $morphFqcn = MorphTo::class;
        $fileSourceCache = [];
        try {
            $reflection = new \ReflectionClass($instance);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $declaringClass = $method->getDeclaringClass()->getName();
                if (! str_starts_with($declaringClass, 'Modules\\') && ! str_starts_with($declaringClass, 'App\\')) {
                    continue;
                }
                if ($method->getNumberOfRequiredParameters() > 0
                    || $method->isStatic() || $method->isAbstract()
                    || $method->isConstructor() || $method->isDestructor()) {
                    continue;
                }
                $rt = $method->getReturnType();
                $isTyped = $rt instanceof \ReflectionNamedType && $rt->getName() === $morphFqcn;
                $looksLikeMorph = $isTyped;
                if (! $looksLikeMorph) {
                    $f = $method->getFileName();
                    if ($f && is_readable($f)) {
                        if (! isset($fileSourceCache[$f])) {
                            $fileSourceCache[$f] = @file($f) ?: [];
                        }
                        $body = implode('', array_slice(
                            $fileSourceCache[$f],
                            (int) $method->getStartLine() - 1,
                            (int) $method->getEndLine() - (int) $method->getStartLine() + 1
                        ));
                        if (str_contains($body, 'morphTo(')) {
                            $looksLikeMorph = true;
                        }
                    }
                }
                if (! $looksLikeMorph) {
                    continue;
                }
                try {
                    $relation = $method->invoke($instance);
                    if ($relation instanceof MorphTo) {
                        $cols[] = $relation->getForeignKeyName();
                    }
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
        }

        return array_values(array_unique($cols));
    }

    /**
     * Legacy regex-based audit (kept for reference but no longer called).
     *
     * @return array{class: string, unmapped: string[]}
     */
    private function auditModelFile(string $file): array
    {
        $source = file_get_contents($file);

        // Extract namespace + class name to build FQCN
        if (! preg_match('/namespace\s+([^;]+);/', $source, $nsMatch)) {
            return ['class' => $file, 'unmapped' => []];
        }
        if (! preg_match('/class\s+(\w+)/', $source, $classMatch)) {
            return ['class' => $file, 'unmapped' => []];
        }
        $fqcn = trim($nsMatch[1]).'\\'.$classMatch[1];

        // Extract fillable columns
        $fillable = $this->extractFillable($source);

        // Find *_id columns (excluding skip list and known non-FKs)
        $idColumns = array_filter($fillable, function ($col) {
            if (! str_ends_with($col, '_id')) {
                return false;
            }
            if (in_array($col, self::SKIP_FK_COLUMNS, true)) {
                return false;
            }
            if (in_array($col, self::KNOWN_NON_FK_COLUMNS, true)) {
                return false;
            }

            return true;
        });

        // Find belongsTo declarations and the FK column each one targets
        $belongsToColumns = $this->extractBelongsToFkColumns($source);

        // Diff: any *_id fillable column not in belongsTo set is a gap
        $unmapped = array_values(array_diff($idColumns, $belongsToColumns));

        return ['class' => $fqcn, 'unmapped' => $unmapped];
    }

    /**
     * Pull the contents of `protected $fillable = [ ... ];` and return
     * an array of single-quoted column names.
     */
    private function extractFillable(string $source): array
    {
        if (! preg_match('/protected\s+\$fillable\s*=\s*\[(.+?)\];/s', $source, $m)) {
            return [];
        }
        $body = $m[1];
        preg_match_all("/'([a-z_][a-z0-9_]*)'/i", $body, $cols);

        return $cols[1] ?? [];
    }

    /**
     * Find `belongsTo(SomeModel::class, 'fk_column'...)` calls AND
     * `belongsTo(SomeModel::class)` (default convention) calls in the source.
     * Returns the FK column names. For default-convention belongsTo, infer
     * from the method name (snake_case + _id).
     */
    private function extractBelongsToFkColumns(string $source): array
    {
        $columns = [];

        // Pattern 1: explicit FK column — belongsTo(X::class, 'fk_col'
        preg_match_all("/belongsTo\([^,)]+,\s*'([a-z_][a-z0-9_]*)'/i", $source, $m1);
        if (! empty($m1[1])) {
            $columns = array_merge($columns, $m1[1]);
        }

        // Pattern 2: convention-based — belongsTo(X::class) inside a public function
        // The relation method name (e.g. `parent`) implies FK column `parent_id`.
        // Walk the source line-by-line: when we see `public function NAME(`
        // followed within ~5 lines by a `belongsTo(X::class)` with no second arg,
        // emit `NAME_id`.
        if (preg_match_all('/public\s+function\s+(\w+)\s*\([^)]*\)[^{]*\{([^}]+)\}/s', $source, $methods, PREG_SET_ORDER)) {
            foreach ($methods as $method) {
                $methodName = $method[1];
                $body = $method[2];
                // Match belongsTo with NO second argument (default-convention)
                if (preg_match('/->belongsTo\([^,)]+\)(?:\s*->[a-zA-Z]+\([^)]*\))*\s*;/', $body)) {
                    $columns[] = Str::snake($methodName).'_id';
                }
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * Glob all model PHP files that include `use HasActivityLogging`.
     */
    private function discoverLoggedModels(): array
    {
        $files = glob(base_path('Modules/*/app/Models/*.php'));
        $out = [];
        foreach ($files as $f) {
            $base = basename($f);
            if (str_ends_with($base, 'Log.php')) {
                continue;
            }
            $contents = file_get_contents($f);
            if (! $contents) {
                continue;
            }
            // Match either `use HasActivityLogging` (alone) or `use ..., HasActivityLogging`
            // (as part of a multi-trait `use` line)
            if (preg_match('/\buse\b[^;]*\bHasActivityLogging\b/', $contents)) {
                $out[] = $f;
            }
        }

        return $out;
    }
}
