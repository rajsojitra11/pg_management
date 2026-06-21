<?php

namespace Modules\Service\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Service\Models\Service;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'service_category_id' => 1,
            'service_name' => $this->faker->unique()->word().' Service',
        ];
    }
}
