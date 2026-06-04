<?php

namespace App\Services\EnvManifest;

/**
 * Single env-var manifest entry. Loaded from config/env-manifest/{group}.php.
 *
 * Required fields: description, type, default.
 * Optional: subgroup, long, allowed, profile_key, criticality, secret,
 * mutable, deprecated_in, removed_in, replaced_by, since, related, notes,
 * business_relevant, profile_gap, recommended.
 */
final readonly class EnvManifestEntry
{
    public const TYPE_BOOL = 'bool';

    public const TYPE_INT = 'int';

    public const TYPE_STRING = 'string';

    public const TYPE_ENUM = 'enum';

    public const TYPE_SECRET = 'secret';

    public const TYPE_URL = 'url';

    public const TYPE_EMAIL = 'email';

    public const TYPE_CSV = 'csv';

    public const TYPE_JSON = 'json';

    public const CRIT_HIGH = 'high';

    public const CRIT_MEDIUM = 'medium';

    public const CRIT_LOW = 'low';

    public function __construct(
        public string $key,
        public string $group,
        public string $sourceFile,
        public string $description,
        public string $type,
        public mixed $default,
        public ?string $subgroup = null,
        public ?string $long = null,
        /** @var list<scalar>|null */
        public ?array $allowed = null,
        public ?string $profileKey = null,
        public string $criticality = self::CRIT_MEDIUM,
        public bool $secret = false,
        public bool $mutable = false,
        public ?string $deprecatedIn = null,
        public ?string $removedIn = null,
        public ?string $replacedBy = null,
        public ?string $since = null,
        /** @var list<string> */
        public array $related = [],
        public ?string $notes = null,
        public bool $businessRelevant = false,
        public bool $profileGap = false,
        /** @var array<string, scalar>|null  install_type => recommended value */
        public ?array $recommended = null,
    ) {}

    public static function fromArray(string $key, string $group, string $sourceFile, array $data): self
    {
        $required = ['description', 'type', 'default'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw new \RuntimeException("Manifest entry '{$key}' in {$sourceFile} is missing required field '{$field}'");
            }
        }

        return new self(
            key: $key,
            group: $group,
            sourceFile: $sourceFile,
            description: $data['description'],
            type: $data['type'],
            default: $data['default'],
            subgroup: $data['subgroup'] ?? null,
            long: $data['long'] ?? null,
            allowed: $data['allowed'] ?? null,
            profileKey: $data['profile_key'] ?? null,
            criticality: $data['criticality'] ?? self::CRIT_MEDIUM,
            secret: (bool) ($data['secret'] ?? false),
            mutable: (bool) ($data['mutable'] ?? false),
            deprecatedIn: $data['deprecated_in'] ?? null,
            removedIn: $data['removed_in'] ?? null,
            replacedBy: $data['replaced_by'] ?? null,
            since: $data['since'] ?? null,
            related: $data['related'] ?? [],
            notes: $data['notes'] ?? null,
            businessRelevant: (bool) ($data['business_relevant'] ?? false),
            profileGap: (bool) ($data['profile_gap'] ?? false),
            recommended: $data['recommended'] ?? null,
        );
    }

    public function isDeprecated(): bool
    {
        return $this->deprecatedIn !== null;
    }
}
