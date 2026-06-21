<?php

namespace Modules\Maintenance\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Complaint\Models\Complaint;
use Modules\Maintenance\Database\Factories\MaintenanceFactory;
use Modules\User\Models\User;

class Maintenance extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): MaintenanceFactory
    {
        return MaintenanceFactory::new();
    }

    protected $table = 'maintenances';

    protected $fillable = [
        'maintenance_no',
        'complaint_id',
        'cost',
        'proof',
        'description',
        'maintenance_date',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'maintenance_date' => 'date',
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => MaintenanceLog::class,
            'foreign_key' => 'maintenance_id',
            'name_field' => 'maintenance_no',
            'model_name' => 'Maintenance',
            'system_remarks' => [
                'created' => 'New maintenance created: {maintenance_no}',
                'updated' => 'Maintenance updated: {maintenance_no}',
                'deleted' => 'Maintenance deleted: {maintenance_no}',
                'restored' => 'Maintenance restored: {maintenance_no}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'maintenance_no';
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
