<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Model;

class RoomLog extends Model
{
    public $timestamps = false;

    protected $table = 'pg_room_logs';

    protected $fillable = [
        'pg_room_id',
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
