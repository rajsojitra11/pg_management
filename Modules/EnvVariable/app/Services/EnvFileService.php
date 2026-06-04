<?php

namespace Modules\EnvVariable\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class EnvFileService
{
    protected string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    public function updateEnvFile(string $key, ?string $value): bool
    {
        // Test-environment guard — without this, every run of EnvVariableTest
        // permanently pollutes the developer's real .env (writing TEST_NEW_VAR,
        // faker-generated Latin words, etc.). Tests can still verify the
        // controller/service contract (DB rows, API responses, log entries);
        // only the side-effect of mutating the real .env is skipped.
        if (app()->environment('testing')) {
            return true;
        }

        try {
            if (! File::exists($this->envPath)) {
                return false;
            }

            $content = File::get($this->envPath);
            $key = strtoupper($key);

            // Format value with quotes if needed
            $formattedValue = $this->formatEnvValue($value);

            // Check if key exists
            if ($this->keyExists($key, $content)) {
                // Update existing key
                $pattern = "/^{$key}=.*$/m";
                $replacement = "{$key}={$formattedValue}";
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                // Add new key at the end
                $content = trim($content)."\n{$key}={$formattedValue}\n";
            }

            // Write updated content
            File::put($this->envPath, $content);

            return true;
        } catch (Exception $e) {

            return false;
        }
    }

    public function removeFromEnvFile(string $key): bool
    {
        // Test-environment guard — see updateEnvFile() for rationale.
        if (app()->environment('testing')) {
            return true;
        }

        try {
            if (! File::exists($this->envPath)) {
                return false;
            }

            $content = File::get($this->envPath);
            $key = strtoupper($key);

            if ($this->keyExists($key, $content)) {
                // Remove the line containing the key
                $pattern = "/^{$key}=.*\n?/m";
                $content = preg_replace($pattern, '', $content);

                File::put($this->envPath, $content);

                return true;
            }

            return false;
        } catch (Exception $e) {

            return false;
        }
    }

    public function getEnvValue(string $key): ?string
    {
        try {
            if (! File::exists($this->envPath)) {
                return null;
            }

            $content = File::get($this->envPath);
            $key = strtoupper($key);

            if (preg_match("/^{$key}=(.*)$/m", $content, $matches)) {
                return $this->parseEnvValue($matches[1]);
            }

            return null;
        } catch (Exception $e) {

            return null;
        }
    }

    public function clearCache(): bool
    {
        try {
            // Clear application cache
            Artisan::call('cache:clear');

            return true;
        } catch (Exception $e) {

            return false;
        }
    }

    public function dumpAutoload(): bool
    {
        try {
            $process = new Process(['composer', 'dump-autoload']);
            $process->setWorkingDirectory(base_path());
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            } else {

                return false;
            }
        } catch (Exception $e) {

            return false;
        }
    }

    public function getAllEnvVariables(): array
    {
        try {
            if (! File::exists($this->envPath)) {
                return [];
            }

            $content = File::get($this->envPath);
            $lines = explode("\n", $content);
            $variables = [];

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip empty lines and comments
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                    $key = $matches[1];
                    $value = $this->parseEnvValue($matches[2]);
                    $variables[$key] = $value;
                }
            }

            return $variables;
        } catch (Exception $e) {

            return [];
        }
    }

    protected function keyExists(string $key, string $content): bool
    {
        return preg_match("/^{$key}=.*$/m", $content) === 1;
    }

    protected function formatEnvValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // If value contains spaces, quotes, or special characters, wrap in quotes
        if (preg_match('/\s|["\']|#|\\\\/', $value)) {
            return '"'.addslashes($value).'"';
        }

        return $value;
    }

    protected function parseEnvValue(string $value): string
    {
        $value = trim($value);

        // Remove surrounding quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Unescape quotes
        $value = str_replace(['\"', "\'"], ['"', "'"], $value);

        return $value;
    }
}
