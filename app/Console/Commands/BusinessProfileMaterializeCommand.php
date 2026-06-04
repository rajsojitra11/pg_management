<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `business:profile materialize` (PR 8 / Q29 freeze cascade).
 *
 * Reads the active manufacturer profile + writes its effective values into
 * `.env`. Useful when ops want explicit values committed to env rather than
 * relying on the cascade. After running, `business:status` will report every
 * flag with `[env=VAR]` source instead of `[profile=name]`.
 *
 * Idempotent — running twice produces the same `.env`. Safe-by-default:
 *   - Never overwrites a key that already has an explicit value (even if it
 *     matches the profile default).
 *   - Always writes the comment block above each section.
 *   - Backs up `.env` to `.env.backup-YYYY-MM-DD-HH-MM-SS` before modifying.
 */
class BusinessProfileMaterializeCommand extends Command
{
    protected $signature = 'business:profile:materialize {--dry-run : Print the diff without writing} {--force : Overwrite existing values too}';

    protected $description = 'Freeze the active manufacturer profile defaults into .env (Q29 freeze cascade)';

    public function handle(): int
    {
        $profile = config('profiles.active', 'custom');
        $defaults = config("profiles.profiles.{$profile}", []);

        if (empty($defaults)) {
            $this->error("Active profile '{$profile}' has no defaults defined in config/profiles.php.");

            return self::FAILURE;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('No .env file found at '.$envPath);

            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);
        $existingKeys = $this->parseEnvKeys($envContent);

        $envVarMap = $this->keyToEnvVarMap($defaults);

        $writes = [];
        $skips = [];
        foreach ($envVarMap as $envVar => $value) {
            $hasExplicit = isset($existingKeys[$envVar]) && $existingKeys[$envVar] !== '';
            if ($hasExplicit && ! $this->option('force')) {
                $skips[] = "{$envVar}={$existingKeys[$envVar]} (already set; use --force to overwrite)";

                continue;
            }
            $writes[$envVar] = (string) $value;
        }

        $this->info("Active profile: {$profile}");
        $this->newLine();

        if (empty($writes)) {
            $this->info('Nothing to write — all profile defaults are already explicit in .env.');

            return self::SUCCESS;
        }

        $this->table(['ENV var', 'New value'], collect($writes)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($skips) {
            $this->newLine();
            $this->warn('Skipped (already explicit):');
            foreach ($skips as $s) {
                $this->line('  - '.$s);
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry-run: no changes written.');

            return self::SUCCESS;
        }

        $backup = $envPath.'.backup-'.now()->format('Y-m-d-H-i-s');
        copy($envPath, $backup);
        $this->info("Backed up to {$backup}");

        $newContent = $this->applyWrites($envContent, $writes);
        file_put_contents($envPath, $newContent);

        $this->info('Profile materialized into .env.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> existing env key → raw value
     */
    private function parseEnvKeys(string $content): array
    {
        $keys = [];
        foreach (preg_split("/\r?\n/", $content) as $line) {
            if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', trim($line), $m)) {
                $keys[$m[1]] = $m[2];
            }
        }

        return $keys;
    }

    /**
     * Translate snake_case profile keys to UPPER_SNAKE env var names.
     *
     * @param  array<string, mixed>  $profileDefaults
     * @return array<string, mixed>
     */
    private function keyToEnvVarMap(array $profileDefaults): array
    {
        $map = [];
        foreach ($profileDefaults as $key => $value) {
            $map[strtoupper($key)] = $value;
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $writes
     */
    private function applyWrites(string $content, array $writes): string
    {
        // Append a clearly marked block at the end of .env.
        $block = "\n\n# === Materialized by `business:profile:materialize` ".now()->toIso8601String().' ===';
        foreach ($writes as $key => $value) {
            $block .= "\n{$key}={$value}";
        }
        $block .= "\n# === end materialized block ===";

        return rtrim($content, "\n").$block."\n";
    }
}
