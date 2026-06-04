<?php

namespace Modules\Country\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Country\Models\Country;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->country(),
            'code' => $this->faker->unique()->countryCode(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
