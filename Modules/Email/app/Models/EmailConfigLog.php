<?php

namespace Modules\Email\Models;

use Illuminate\Database\Eloquent\Model;

class EmailConfigLog extends Model
{
    public $timestamps = false;

    protected $table = 'email_config_logs';

    protected $fillable = [
        'email_config_id',
        'user_id',
        'created_by',
        'activity',
        'user_remark',
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
