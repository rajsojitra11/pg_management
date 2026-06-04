<?php

namespace Modules\MenuMaster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MenuMaster\Models\MenuMaster;

class MenuMasterFactory extends Factory
{
    protected $model = MenuMaster::class;

    public function definition(): array
    {
        return [
            'menu_icon' => 'fas fa-cog',
            'menu_title' => $this->faker->unique()->words(2, true),
            'menu_route' => $this->faker->unique()->slug(2),
            'is_main_menu' => true,
            'parent_id' => null,
            'module_name' => $this->faker->word(),
            'order_display' => (string) $this->faker->numberBetween(1, 100),
            'display_order' => (string) $this->faker->numberBetween(1, 100),
            'if_can' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
