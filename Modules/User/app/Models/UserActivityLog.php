<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User as UserModel;

class UserActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'user_id_acting_on',
        'activity',
        'user_remark',
        'system_remark',
        'old_values',
        'new_values',
        'ip_address',
        'location',
        'user_agent',
        'device',
        'platform',
        'browser',
        'successful',
        'logout_reason',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
            'old_values' => 'json',
            'new_values' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by')->withTrashed();
    }

    public function userActingOn(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id_acting_on')->withTrashed();
    }
}
