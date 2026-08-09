<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * `php artisan ui:audit` — scan Modules/*\/resources/views/**\/*.blade.php for
 * violations of the project's UI design-system rules.
 *
 * Companion to ModuleComplianceScanner but for blade views. Catches drift
 * from the canonical Tailwind class strings + mandatory partials-tw/* includes.
 *
 * Checks (from .claude/lessons/lesson-book.md R-PROJ-013, R-PROJ-014):
 *   - Card surfaces drifted from canonical class string (R-PROJ-013, MINOR)
 *   - Hardcoded English strings (R-PROJ-014, MAJOR)
 *
 * Exit codes:
 *   0 — no violations (or only MINOR with --no-strict)
 *   1 — violations (with --strict, any severity)
 */
class UiAuditCommand extends Command
{
    protected $signature = 'ui:audit
                            {module? : Single module to audit (default: all)}
                            {--severity=all : Filter findings by severity (blocker|major|minor|all)}
                            {--strict : Exit non-zero on any finding (CI gate)}
                            {--json : Machine-readable output}';

    protected $description = 'Audit Modules/*/resources/views/**/*.blade.php against UI design-system rules.';

    private const CANONICAL_CARD = 'rounded-lg border border-zinc-200 bg-white shadow-sm';

    private const CANONICAL_INPUT_TOKENS = ['h-9', 'rounded-md', 'border-zinc-200', 'focus:ring-zinc-500'];

    public function handle(): int
    {
        $modulesDir = base_path('Modules');
        if (! is_dir($modulesDir)) {
            $this->error('Modules/ directory missing.');

            return self::FAILURE;
        }

        $module = $this->argument('module');
        $severityFilter = strtolower($this->option('severity') ?: 'all');

        $finder = new Finder;
        $finder->files()->in($modulesDir)
            ->path('resources/views')
            ->name('*.blade.php');

        if ($module) {
            $finder->path("Modules/{$module}/resources/views");
        }

        $findings = [];
        $filesScanned = 0;

        foreach ($finder as $file) {
            $filesScanned++;
            $rel = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $rel = str_replace('\\', '/', $rel);
            $content = $file->getContents();
            $lines = explode("\n", $content);

            $perFile = $this->scanFile($rel, $lines, $content);
            foreach ($perFile as $f) {
                if ($severityFilter !== 'all' && $f['severity'] !== $severityFilter) {
                    continue;
                }
                $findings[] = $f;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'files_scanned' => $filesScanned,
                'findings_count' => count($findings),
                'findings' => $findings,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->renderHuman($findings, $filesScanned);
        }

        $hasFindings = ! empty($findings);
        $hasBlockerOrMajor = collect($findings)->contains(fn ($f) => in_array($f['severity'], ['blocker', 'major'], true));

        if ($this->option('strict') && $hasFindings) {
            return self::FAILURE;
        }
        if ($hasBlockerOrMajor) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Per-file scan.
     *
     * @param  array<int, string>  $lines
     * @return array<int, array{file: string, line: int, severity: string, rule: string, detail: string, excerpt: string}>
     */
    private function scanFile(string $rel, array $lines, string $content): array
    {
        $findings = [];

        // R-PROJ-013 — card surface drifted from canonical
        foreach ($lines as $i => $line) {
            // Look for divs that include some card-like classes but don't match canonical exactly
            if (preg_match('/class="[^"]*(rounded-lg|rounded-md)[^"]*(border|bg-white)[^"]*"/', $line)
                && (str_contains($line, 'shadow-') || str_contains($line, 'border-zinc'))
                && ! str_contains($line, self::CANONICAL_CARD)
                && (str_contains($line, 'shadow-lg') || str_contains($line, 'shadow-xl') || str_contains($line, 'border-gray') || str_contains($line, 'border-slate') || str_contains($line, 'border-neutral'))) {
                $findings[] = [
                    'file' => $rel,
                    'line' => $i + 1,
                    'severity' => 'minor',
                    'rule' => 'R-PROJ-013',
                    'detail' => 'Card surface drifted — not exactly "'.self::CANONICAL_CARD.'"',
                    'excerpt' => trim(substr($line, 0, 200)),
                ];
            }
        }

        // R-PROJ-014 — hardcoded English in blade
        // Heuristic: text node with English-looking words OUTSIDE of __() and outside HTML attributes
        foreach ($lines as $i => $line) {
            $stripped = trim($line);
            // Skip comments / directives / empty / pure HTML
            if ($stripped === '' || str_starts_with($stripped, '{{--') || str_starts_with($stripped, '<!--') || str_starts_with($stripped, '@')) {
                continue;
            }
            // Find text between > and < that's all-English-words (rough heuristic)
            if (preg_match_all('/>\s*([A-Z][A-Za-z][A-Za-z\s]{4,40}?)\s*</', $line, $matches)) {
                foreach ($matches[1] as $text) {
                    // Skip if the line includes __() or trans()
                    if (str_contains($line, '__(') || str_contains($line, 'trans(') || str_contains($line, '@lang')) {
                        continue;
                    }
                    // Skip if it's likely a programmatic identifier (e.g., looks like ARN-001, CR-2026)
                    if (preg_match('/^[A-Z]{2,4}[\-\d]/', $text)) {
                        continue;
                    }
                    // Skip if it's an HTML entity-only string
                    if (preg_match('/^[\s\&\;]+$/', $text)) {
                        continue;
                    }
                    $findings[] = [
                        'file' => $rel,
                        'line' => $i + 1,
                        'severity' => 'major',
                        'rule' => 'R-PROJ-014',
                        'detail' => "Hardcoded English text: \"{$text}\" — wrap with __()",
                        'excerpt' => trim(substr($line, 0, 200)),
                    ];
                    break; // one finding per line is enough
                }
            }
        }

        return $findings;
    }

    private function renderHuman(array $findings, int $filesScanned): void
    {
        $this->newLine();
        $this->components->info('ui:audit — design-system drift detection');
        $this->components->twoColumnDetail('Files scanned', (string) $filesScanned);
        $this->components->twoColumnDetail('Findings', (string) count($findings));
        $this->newLine();

        if (empty($findings)) {
            $this->components->success('No UI drift detected.');

            return;
        }

        // Group by file
        $byFile = collect($findings)->groupBy('file');
        foreach ($byFile as $file => $items) {
            $this->line("<fg=yellow>{$file}</>");
            foreach ($items as $f) {
                $color = match ($f['severity']) {
                    'blocker' => 'red',
                    'major' => 'yellow',
                    default => 'gray',
                };
                $this->line("  <fg={$color}>L{$f['line']}</> [{$f['rule']}] {$f['detail']}");
                if (! empty($f['excerpt'])) {
                    $this->line("    <fg=gray>{$f['excerpt']}</>");
                }
            }
            $this->newLine();
        }

        $bySeverity = collect($findings)->groupBy('severity');
        $this->components->twoColumnDetail('Blocker', (string) $bySeverity->get('blocker', collect())->count());
        $this->components->twoColumnDetail('Major', (string) $bySeverity->get('major', collect())->count());
        $this->components->twoColumnDetail('Minor', (string) $bySeverity->get('minor', collect())->count());
    }
}
