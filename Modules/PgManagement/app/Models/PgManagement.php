<?php

namespace Modules\PgManagement\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PgManagement\Database\Factories\PgManagementFactory;
use Modules\User\Models\User;

class PgManagement extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): PgManagementFactory
    {
        return PgManagementFactory::new();
    }

    protected $table = 'pg_management';

    protected $fillable = [
        'pg_name',
        'owner_id',
        'mobile_no',
        'total_block',
        'total_room',
        'country_id',
        'state_id',
        'city_id',
        'pincode',
        'address',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => PgManagementLog::class,
            'foreign_key' => 'pg_management_id',
            'name_field' => 'pg_name',
            'model_name' => 'PgManagement',
            'system_remarks' => [
                'created' => 'New PG created: {pg_name}',
                'updated' => 'PG updated: {pg_name}',
                'deleted' => 'PG deleted: {pg_name}',
                'restored' => 'PG restored: {pg_name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'pg_name';
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
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
