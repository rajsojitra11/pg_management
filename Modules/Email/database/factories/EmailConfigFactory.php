<?php

namespace Modules\Email\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Email\Models\EmailConfig;

class EmailConfigFactory extends Factory
{
    protected $model = EmailConfig::class;

    public function definition(): array
    {
        return [
            'sender_email' => fake()->email(),
            'sender_name' => fake()->name(),
            'subject_prefix' => fake()->word(),
            'status' => 'active',
        ];
    }
}
