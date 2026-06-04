<?php

namespace Modules\Dashbord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardWidget extends Model
{
    protected $table = 'dashboard_widgets';

    protected $fillable = [
        'key',
        'title',
        'type',
        'section',
        'icon',
        'icon_bg',
        'icon_color',
        'permission',
        'data_endpoint',
        'default_enabled',
        'default_order',
        'description',
        'config_json',
        'is_active',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'is_active' => 'boolean',
        'config_json' => 'array',
    ];

    public function roleConfigs(): HasMany
    {
        return $this->hasMany(RoleDashboardConfig::class, 'widget_id');
    }

    public function userConfigs(): HasMany
    {
        return $this->hasMany(UserDashboardConfig::class, 'widget_id');
    }
}
