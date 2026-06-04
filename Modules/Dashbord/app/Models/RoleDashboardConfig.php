<?php

namespace Modules\Dashbord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Role\Models\Role;

class RoleDashboardConfig extends Model
{
    protected $table = 'role_dashboard_configs';

    protected $fillable = [
        'role_id',
        'widget_id',
        'enabled',
        'sort_order',
        'size',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id');
    }
}
