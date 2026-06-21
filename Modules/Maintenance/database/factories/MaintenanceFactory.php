<?php

namespace Modules\Maintenance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Maintenance\Models\Maintenance;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        return [
            'cost' => fake()->randomFloat(2, 100, 5000),
            'description' => fake()->sentence(),
            'maintenance_date' => fake()->date(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
        ];
    }
}
