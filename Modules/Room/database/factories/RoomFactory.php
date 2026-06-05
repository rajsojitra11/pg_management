<?php

namespace Modules\Room\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Room\Models\Room;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'pg_id' => 1,
            'category_id' => 1,
            'room_no' => $this->faker->unique()->numerify('###'),
            'bed_capacity' => $this->faker->numberBetween(1, 4),
            'rent_amount' => $this->faker->randomFloat(2, 3000, 15000),
        ];
    }
}
