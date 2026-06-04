<?php

namespace Modules\City\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\City\Models\City;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city(),
            'state_id' => 1,
            'country_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
