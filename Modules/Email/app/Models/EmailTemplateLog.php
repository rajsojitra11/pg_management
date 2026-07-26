<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateLog extends Model
{
    public $timestamps = false;

    protected $table = 'email_template_logs';

    protected $fillable = [
        'email_template_id',
        'user_id',
        'created_by',
        'activity',
        'system_remark',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device',
        'platform',
        'browser',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }
}
