<?php

namespace Modules\Complaint\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintLog extends Model
{
    public $timestamps = false;

    protected $table = 'complaint_logs';

    protected $fillable = [
        'complaint_id',
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
