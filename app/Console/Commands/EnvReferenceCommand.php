<?php

namespace App\Console\Commands;

use App\Services\EnvManifest\EnvManifest;
use App\Services\EnvManifest\EnvManifestEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Auto-generates docs/env-reference.md from the manifest.
 *
 * Single searchable doc with one section per group. Each entry includes
 * description, type, default, allowed values, profile linkage, criticality,
 * deprecation status, and per-profile recommendations (when applicable).
 */
class EnvReferenceCommand extends Command
{
    protected $signature = 'env:reference
                            {--out=docs/env-reference.md : Output path}';

    protected $description = 'Generate docs/env-reference.md from config/env-manifest/*.php';

    public function __construct(private readonly EnvManifest $manifest)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $entries = $this->manifest->entries();
        if ($entries->isEmpty()) {
            $this->components->warn('Manifest is empty. Add files to config/env-manifest/.');

            return self::SUCCESS;
        }

        $out = $this->renderMarkdown();
        $path = $this->resolveOut();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $out);

        $this->components->info("Reference written to {$path} ({$entries->count()} entries across ".count($this->manifest->groups()).' groups)');

        return self::SUCCESS;
    }

    private function renderMarkdown(): string
    {
        $today = now()->format('Y-m-d');
        $entries = $this->manifest->entries();
        $groups = $this->manifest->groups();

        $out = "# Environment Variable Reference\n\n";
        $out .= "**Auto-generated** by `php artisan env:reference` from `config/env-manifest/*.php`. Do not hand-edit — changes here will be overwritten.\n\n";
        $out .= "**Last generated:** {$today} · **Entries:** {$entries->count()} · **Groups:** ".count($groups)."\n\n";

        $out .= "## Table of contents\n\n";
        foreach ($groups as $group) {
            $count = $entries->filter(fn (EnvManifestEntry $e) => $e->group === $group)->count();
            $anchor = '#'.str_replace('-', '', strtolower($group));
            $out .= "- [{$group}]({$anchor}) — {$count} entries\n";
        }
        $out .= "\n---\n\n";

        foreach ($groups as $group) {
            $out .= $this->renderGroup($group);
        }

        return $out;
    }

    private function renderGroup(string $group): string
    {
        $entries = $this->manifest->inGroup($group);
        $out = "## {$group}\n\n";

        foreach ($entries as $entry) {
            $out .= "### `{$entry->key}`\n\n";
            $out .= $entry->description."\n\n";

            $rows = [];
            $rows[] = '| Field | Value |';
            $rows[] = '|---|---|';
            $rows[] = "| Type | `{$entry->type}` |";
            $rows[] = '| Default | `'.$this->fmtValue($entry->default).'` |';
            if ($entry->allowed !== null) {
                $rows[] = '| Allowed | '.implode(', ', array_map(fn ($v) => '`'.$this->fmtValue($v).'`', $entry->allowed)).' |';
            }
            $rows[] = "| Criticality | `{$entry->criticality}` |";
            if ($entry->subgroup !== null) {
                $rows[] = "| Subgroup | `{$entry->subgroup}` |";
            }
            if ($entry->profileKey !== null) {
                $rows[] = "| Profile key | `{$entry->profileKey}` (in `config/profiles.php`) |";
            }
            if ($entry->businessRelevant) {
                $rows[] = '| Business-relevant | yes'.($entry->profileGap ? ' ⚠ profile_gap (not yet in profiles.php)' : '').' |';
            }
            if ($entry->secret) {
                $rows[] = '| Secret | yes — never echo to logs |';
            }
            if ($entry->isDeprecated()) {
                $rows[] = "| Deprecated since | `{$entry->deprecatedIn}`".($entry->replacedBy ? " — use `{$entry->replacedBy}` instead" : '').' |';
            }
            if (! empty($entry->related)) {
                $rows[] = '| Related | '.implode(', ', array_map(fn ($r) => '`'.$r.'`', $entry->related)).' |';
            }

            $out .= implode("\n", $rows)."\n\n";

            if ($entry->long !== null) {
                $out .= "> {$entry->long}\n\n";
            }

            if ($entry->recommended !== null) {
                $out .= "**Recommended per install type:**\n\n";
                $profiles = array_keys($entry->recommended);
                $out .= '| '.implode(' | ', $profiles)." |\n";
                $out .= '|'.str_repeat('---|', count($profiles))."\n";
                $out .= '| '.implode(' | ', array_map(
                    fn ($p) => '`'.$this->fmtValue($entry->recommended[$p] ?? '?').'`',
                    $profiles
                ))." |\n\n";
            }

            if ($entry->notes !== null) {
                $out .= $entry->notes."\n\n";
            }
        }

        return $out."\n";
    }

    private function fmtValue(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_array($v)) {
            return json_encode($v);
        }
        if ($v === null) {
            return 'null';
        }

        return (string) $v;
    }

    private function resolveOut(): string
    {
        $out = $this->option('out');
        if (str_starts_with($out, '/') || preg_match('/^[A-Za-z]:/', $out)) {
            return $out;
        }

        return base_path($out);
    }
}
