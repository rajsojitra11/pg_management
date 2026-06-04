<?php

namespace Modules\State\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\State\Models\State;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->state(),
            'code' => strtoupper($this->faker->lexify('??')),
            'country_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
