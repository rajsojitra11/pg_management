<?php

namespace Modules\Currency\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Currency\Models\Currency;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'currency_name' => $this->faker->unique()->currencyCode(),
            'currency_symbol' => $this->faker->randomElement(['$', '€', '£', '¥', '₹']),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
