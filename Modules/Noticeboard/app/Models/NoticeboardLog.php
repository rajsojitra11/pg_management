<?php

namespace Modules\Noticeboard\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeboardLog extends Model
{
    public $timestamps = false;

    protected $table = 'noticeboard_logs';

    protected $fillable = [
        'noticeboard_id',
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
