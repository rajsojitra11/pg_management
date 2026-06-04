<?php

namespace Modules\Unit\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Unit\Database\Factories\UnitFactory;
use Modules\User\Models\User;

class Unit extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): UnitFactory
    {
        return UnitFactory::new();
    }

    protected $table = 'units';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['id', 'name', 'unit_value', 'created_by', 'updated_by', 'deleted_by'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Override logging configuration for Unit model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => UnitLog::class,
            'foreign_key' => 'unit_id',
            'name_field' => 'name',
            'model_name' => 'Unit',
            'system_remarks' => [
                'created' => 'New unit created: {name}',
                'updated' => 'Unit updated: {name}',
                'deleted' => 'Unit deleted: {name}',
                'restored' => 'Unit restored: {name}',
            ],
        ];
    }

    /**
     * Override the name field for logging
     */
    protected function getNameField(): string
    {
        return 'name';
    }

    public function logs()
    {
        return $this->hasMany(UnitLog::class, 'unit_id');
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
}
