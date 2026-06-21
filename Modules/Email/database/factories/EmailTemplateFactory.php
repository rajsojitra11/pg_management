<?php

namespace Modules\Email\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Email\Models\EmailTemplate;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'rent_reminder',
            'subject' => 'Rent Reminder - {current_month}',
            'body' => '<p>Dear {tenant_name},</p><p>This is a reminder that your rent of ₹{monthly_rent} for room {room_no} at {pg_name} is due on {due_date}.</p><p>Thank you,<br>{sender_name}</p>',
            'is_default' => true,
            'status' => 'active',
        ];
    }
}
