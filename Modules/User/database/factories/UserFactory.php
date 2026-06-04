<?php

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile' => $this->faker->unique()->numerify('##########'),
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'status' => 'active',
            'menu_style' => 'vertical',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
