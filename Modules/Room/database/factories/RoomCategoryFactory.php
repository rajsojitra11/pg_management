<?php

namespace Modules\Room\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Room\Models\RoomCategory;

class RoomCategoryFactory extends Factory
{
    protected $model = RoomCategory::class;

    public function definition(): array
    {
        return [
            'pg_id' => 1,
            'category_name' => $this->faker->word().' Room',
        ];
    }
}
