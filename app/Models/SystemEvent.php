<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit-grade system event log. Rows here document state
 * transitions that affect the whole system (migration cutover, etc.).
 */
class SystemEvent extends Model
{
    use HasPublicId;

    protected $table = 'system_events';

    public $timestamps = false;

    protected $fillable = [
        'public_id',
        'type',
        'actor_user_id',
        'reason',
        'payload',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
