<?php

namespace App\Services\EnvManifest;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Loads + merges per-group manifest files from config/env-manifest/*.php.
 *
 * Each entry is a structured description of one env var (description,
 * group, type, allowed values, default, profile_key, criticality, etc.).
 * Used by env:doctor to detect drift, env:reference to generate docs, and
 * env:example to regenerate .env.example.
 *
 * Only env vars listed in the manifest are tracked. Vars referenced in code
 * but absent from the manifest are flagged by env:doctor as "uncatalogued"
 * (visibility, not failure).
 */
class EnvManifest
{
    /** @var Collection<string, EnvManifestEntry>|null */
    private ?Collection $entries = null;

    public function __construct(private readonly string $manifestDir) {}

    /**
     * @return Collection<string, EnvManifestEntry> KEY => entry
     */
    public function entries(): Collection
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $merged = collect();
        if (! is_dir($this->manifestDir)) {
            return $this->entries = $merged;
        }

        foreach (File::glob($this->manifestDir.'/*.php') as $file) {
            $group = basename($file, '.php');
            $rows = require $file;
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $key => $data) {
                if ($merged->has($key)) {
                    throw new \RuntimeException("Duplicate manifest entry: '{$key}' is declared in both {$merged->get($key)->sourceFile} and {$file}");
                }
                $merged->put($key, EnvManifestEntry::fromArray($key, $group, $file, $data));
            }
        }

        return $this->entries = $merged;
    }

    public function find(string $key): ?EnvManifestEntry
    {
        return $this->entries()->get($key);
    }

    /**
     * @return Collection<string, EnvManifestEntry>
     */
    public function inGroup(string $group): Collection
    {
        return $this->entries()->filter(fn (EnvManifestEntry $e) => $e->group === $group)->values()
            ->keyBy(fn (EnvManifestEntry $e) => $e->key);
    }

    /**
     * @return list<string> All group names present in the manifest
     */
    public function groups(): array
    {
        return $this->entries()->map(fn ($e) => $e->group)->unique()->sort()->values()->all();
    }

    public function count(): int
    {
        return $this->entries()->count();
    }
}
