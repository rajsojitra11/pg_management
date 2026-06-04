<?php

namespace Modules\Role\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Role\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'title' => $this->faker->unique()->jobTitle(),
            'guard_name' => 'web',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
