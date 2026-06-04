<?php

namespace Modules\Setting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setting\Models\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'tag_line' => $this->faker->catchPhrase(),
            'gst_number' => strtoupper($this->faker->bothify('??#####??##?#?#')),
            'pancard_number' => strtoupper($this->faker->bothify('?????####?')),
            'tan_number' => strtoupper($this->faker->bothify('????#####?')),
            'year_display_format' => 'full_short',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
