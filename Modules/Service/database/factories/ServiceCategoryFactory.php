<?php

namespace Modules\Service\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Service\Models\ServiceCategory;

class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        return [
            'service_category_name' => $this->faker->word().' Service',
        ];
    }
}
