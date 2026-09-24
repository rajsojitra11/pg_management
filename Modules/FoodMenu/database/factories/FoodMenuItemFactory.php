<?php

namespace Modules\FoodMenu\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FoodMenu\Models\FoodMenuItem;

class FoodMenuItemFactory extends Factory
{
    protected $model = FoodMenuItem::class;

    public function definition(): array
    {
        return [
            'food_menu_id' => 1,
            'day' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'meal_time' => $this->faker->randomElement(['breakfast', 'lunch', 'dinner']),
            'item_name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'sort_order' => 0,
            'status' => 'active',
        ];
    }
}
