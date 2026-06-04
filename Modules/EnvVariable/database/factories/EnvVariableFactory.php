<?php

namespace Modules\EnvVariable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EnvVariable\Models\EnvVariable;

class EnvVariableFactory extends Factory
{
    protected $model = EnvVariable::class;

    /**
     * Human-readable purpose tags rotated through factory invocations so test
     * fixtures look like "TEST_FEATURE_FLAG_03" instead of Latin/lorem-ipsum
     * (which previously polluted .env as keys like ACCUSAMUS, ALIQUAM, etc.).
     * The TEST_ prefix is intentional — it makes test-generated rows easy to
     * spot in audits and grep-able for cleanup.
     */
    private const TEST_KEY_PURPOSES = [
        'FEATURE_FLAG',
        'FORM_LABEL',
        'CACHE_KEY',
        'API_TIMEOUT',
        'DISPLAY_LIMIT',
        'BATCH_SIZE',
        'RETRY_COUNT',
        'WEBHOOK_URL',
        'SETTING_VALUE',
        'CONFIG_OPTION',
    ];

    private const TEST_DESCRIPTIONS = [
        'Test fixture: example feature flag for integration tests.',
        'Test fixture: sample form label override used by the env-variable test suite.',
        'Test fixture: cache key prefix override; verifies the env-driven config works end-to-end.',
        'Test fixture: API request timeout (seconds); covers numeric-value handling.',
        'Test fixture: max items per page; covers integer config retrieval.',
        'Test fixture: batch processor chunk size; covers integer config retrieval.',
        'Test fixture: retry count for failed jobs; covers integer config retrieval.',
        'Test fixture: webhook URL override; covers URL-shaped string handling.',
        'Test fixture: arbitrary setting; baseline string value test.',
        'Test fixture: configurable option; baseline string value test.',
    ];

    public function definition(): array
    {
        // Use the unique counter to walk the purpose array deterministically;
        // resulting keys look like TEST_FEATURE_FLAG_001 / TEST_API_TIMEOUT_002.
        $purpose = $this->faker->unique()->numberBetween(1, 99999);
        $purposeIndex = $purpose % count(self::TEST_KEY_PURPOSES);
        $purposeName = self::TEST_KEY_PURPOSES[$purposeIndex];

        return [
            'key' => sprintf('TEST_%s_%03d', $purposeName, $purpose),
            'value' => 'fixture-value-'.$purpose,
            'description' => self::TEST_DESCRIPTIONS[$purposeIndex],
            'is_encrypted' => false,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function encrypted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_encrypted' => true,
                'value' => 'encrypted-fixture-secret-'.uniqid(),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
