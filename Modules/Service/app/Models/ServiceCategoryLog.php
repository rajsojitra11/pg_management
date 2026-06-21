<?php

namespace Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategoryLog extends Model
{
    public $timestamps = false;

    protected $table = 'service_category_logs';

    protected $fillable = [
        'service_category_id',
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
