<?php

namespace Modules\EnvVariable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class EnvVariableLog extends Model
{
    public $timestamps = false;

    protected $table = 'env_variable_logs';

    protected $fillable = [
        'env_variable_id',
        'user_id',
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
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function envVariable(): BelongsTo
    {
        return $this->belongsTo(EnvVariable::class, 'env_variable_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
