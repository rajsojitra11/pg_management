<?php

namespace Modules\Complaint\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Complaint\Models\Complaint;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'pg_id' => 1,
            'room_id' => 1,
            'service_category_id' => 1,
            'service_id' => 1,
            'complaint_date' => $this->faker->date(),
            'note' => $this->faker->sentence(),
            'status' => 'pending',
            'created_by' => 1,
        ];
    }
}
