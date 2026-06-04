<?php

namespace Modules\Year\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Year\Models\Year;

class YearFactory extends Factory
{
    protected $model = Year::class;

    public function definition(): array
    {
        $startYear = $this->faker->unique()->numberBetween(2020, 2099);
        $endYear = $startYear + 1;
        $shortStart = substr($startYear, 2);
        $shortEnd = substr($endYear, 2);

        return [
            'name' => "{$startYear}-{$shortEnd}",
            'full_short' => "{$startYear}-{$shortEnd}",
            'short_full' => "{$shortStart}-{$endYear}",
            'short_short' => "{$shortStart}-{$shortEnd}",
            'full_full' => "{$startYear}-{$endYear}",
            'short' => $shortStart,
            'full' => (string) $startYear,
            'set_default' => false,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
