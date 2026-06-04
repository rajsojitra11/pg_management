<?php

namespace Modules\Subscription\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscription\Models\Subscription;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'subscriber_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'plan_type' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['active', 'expired', 'cancelled', 'pending']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_status' => $this->faker->randomElement(['paid', 'unpaid', 'pending']),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
