<?php

namespace Modules\Complaint\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Complaint\Database\Factories\ComplaintFactory;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;
use Modules\User\Models\User;

class Complaint extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): ComplaintFactory
    {
        return ComplaintFactory::new();
    }

    protected $table = 'complaints';

    protected $fillable = [
        'complaint_no',
        'pg_id',
        'room_id',
        'service_category_id',
        'service_id',
        'complaint_date',
        'note',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'complaint_date' => 'date',
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => ComplaintLog::class,
            'foreign_key' => 'complaint_id',
            'name_field' => 'id',
            'model_name' => 'Complaint',
            'system_remarks' => [
                'created' => 'New complaint created',
                'updated' => 'Complaint updated',
                'deleted' => 'Complaint deleted',
                'restored' => 'Complaint restored',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'id';
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
