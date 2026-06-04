<?php

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;

class RoleYearAccess extends Model
{
    protected $fillable = [
        'role_id',
        'all_years',
        'allowed_year',
    ];

    protected function casts(): array
    {
        return [
            'all_years' => 'boolean',
            'allowed_year' => 'integer',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
