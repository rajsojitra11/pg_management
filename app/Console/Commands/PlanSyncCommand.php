<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Mirrors `.claude/state/feature-tracker.md` to `.claude/plan.md`.
 *
 * The v4 agent system uses `.claude/state/feature-tracker.md` as its task
 * index. The cross-project tracking pattern (and convenient single-file
 * review) wants `.claude/plan.md`. On Linux/macOS this is a symlink. On
 * Windows we copy instead — run this command after task updates.
 */
class PlanSyncCommand extends Command
{
    protected $signature = 'plan:sync {--check : Report drift without writing}';

    protected $description = 'Mirror .claude/state/feature-tracker.md → .claude/plan.md (Windows replacement for symlink).';

    public function handle(): int
    {
        $base = base_path('.claude');
        $source = $base.DIRECTORY_SEPARATOR.'state'.DIRECTORY_SEPARATOR.'feature-tracker.md';
        $target = $base.DIRECTORY_SEPARATOR.'plan.md';

        if (! is_file($source)) {
            $this->error("Source not found: {$source}");
            $this->line('Hint: the v4 agent system creates this file on the first feature run, or you can `touch` it manually.');

            return self::FAILURE;
        }

        $sourceContent = (string) file_get_contents($source);
        $targetExists = is_file($target);
        $targetContent = $targetExists ? (string) file_get_contents($target) : '';

        if ($sourceContent === $targetContent) {
            $this->info('plan.md is already in sync with state/feature-tracker.md.');

            return self::SUCCESS;
        }

        if ($this->option('check')) {
            $this->warn('Drift detected — plan.md is stale relative to state/feature-tracker.md.');
            $this->line('Run `php artisan plan:sync` to update.');

            return self::FAILURE;
        }

        if (file_put_contents($target, $sourceContent) === false) {
            $this->error("Failed to write {$target}");

            return self::FAILURE;
        }

        $this->info('Synced state/feature-tracker.md → plan.md ('.strlen($sourceContent).' bytes).');

        return self::SUCCESS;
    }
}
