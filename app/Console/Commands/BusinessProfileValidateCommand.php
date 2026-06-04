<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `business:profile validate` (PR 8 / Q29 diff cascade).
 *
 * Reads the active manufacturer profile + diffs each profile-controlled flag
 * against the explicit value in `.env`. Reports drift with exit code 1 for
 * CI-friendly use; exit code 0 when .env matches the profile (or has no
 * explicit overrides — which is the canonical state).
 */
class BusinessProfileValidateCommand extends Command
{
    protected $signature = 'business:profile:validate {--strict : Treat any explicit override as drift}';

    protected $description = 'Diff .env against the active profile defaults (Q29 diff cascade)';

    public function handle(): int
    {
        $profile = config('profiles.active', 'custom');
        $defaults = config("profiles.profiles.{$profile}", []);

        if (empty($defaults)) {
            $this->error("Active profile '{$profile}' has no defaults defined in config/profiles.php.");

            return self::FAILURE;
        }

        $this->info("Active profile: {$profile}");
        $this->newLine();

        $rows = [];
        $driftCount = 0;
        foreach ($defaults as $key => $expected) {
            $envVar = strtoupper($key);
            $explicit = env($envVar, null);

            $explicitDisplay = $explicit === null ? '(unset)' : (string) $explicit;
            $expectedDisplay = (string) $expected;

            $status = $this->resolveStatus($explicit, $expected);
            if ($status === 'DRIFT') {
                $driftCount++;
            } elseif ($status === 'OVERRIDE' && $this->option('strict')) {
                $driftCount++;
            }

            $rows[] = [$envVar, $explicitDisplay, $expectedDisplay, $status];
        }

        $this->table(['ENV var', 'Current (.env)', 'Profile default', 'Status'], $rows);

        if ($driftCount > 0) {
            $this->newLine();
            $this->error("{$driftCount} flag(s) drifted from the active profile.");

            return 1;
        }

        $this->newLine();
        $this->info('All profile-controlled flags match the active profile.');

        return self::SUCCESS;
    }

    private function resolveStatus(mixed $explicit, mixed $expected): string
    {
        if ($explicit === null) {
            return 'OK (using profile default)';
        }

        // Explicit value present — check for drift.
        if ((string) $explicit === (string) $expected) {
            return 'OVERRIDE (matches default)';
        }

        return 'DRIFT';
    }
}
