<?php

namespace Modules\Noticeboard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Noticeboard\Models\Noticeboard;

class NoticeboardFactory extends Factory
{
    protected $model = Noticeboard::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'pg_id' => 1,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
        ];
    }
}
