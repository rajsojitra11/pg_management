<?php

namespace Modules\Unit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Unit\Models\Unit;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'unit_value' => $this->faker->randomFloat(2, 0.01, 1000),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
