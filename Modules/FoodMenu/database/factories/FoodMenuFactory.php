<?php

namespace Modules\FoodMenu\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FoodMenu\Models\FoodMenu;

class FoodMenuFactory extends Factory
{
    protected $model = FoodMenu::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'pg_id' => 1,
            'menu_type' => 'weekly',
            'title' => $this->faker->sentence(3),
            'week_start_date' => $this->faker->date(),
            'status' => 'active',
        ];
    }

    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_type' => 'special',
            'week_start_date' => null,
            'special_date' => $this->faker->date(),
        ]);
    }
}
