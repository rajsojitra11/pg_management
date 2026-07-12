<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class SubscriptionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'activity',
        'system_remark',
        'old_values',
        'new_values',
        'ip_address',
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
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id')->withTrashed();
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
