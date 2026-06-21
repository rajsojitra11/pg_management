<?php

namespace Modules\PgManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PgManagement\Models\PgManagement;

class PgManagementFactory extends Factory
{
    protected $model = PgManagement::class;

    public function definition(): array
    {
        return [
            'pg_name' => $this->faker->company().' PG',
            'mobile_no' => $this->faker->numerify('##########'),
            'total_block' => $this->faker->numberBetween(1, 10),
            'total_room' => $this->faker->numberBetween(10, 100),
            'pincode' => $this->faker->numerify('######'),
            'address' => $this->faker->address(),
        ];
    }
}
