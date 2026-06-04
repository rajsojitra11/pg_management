<?php

namespace Modules\Dashbord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class UserDashboardConfig extends Model
{
    protected $table = 'user_dashboard_configs';

    protected $fillable = [
        'user_id',
        'widget_id',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id');
    }
}
