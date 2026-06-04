<?php

namespace Modules\Role\Models;

use App\Traits\HasActivityLogging;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // HasPublicId intentionally not used: Spatie's package creates Role rows
    // directly and bypasses this model's boot listeners. The `roles` table is
    // also excluded from the public_id migration.
    use HasActivityLogging, SoftDeletes;

    protected $fillable = [
        'name',
        'title',
        'guard_name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => RoleLog::class,
            'foreign_key' => 'role_id',
            'name_field' => 'name',
            'model_name' => 'Role',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function yearAccess()
    {
        return $this->hasOne(RoleYearAccess::class);
    }
}
