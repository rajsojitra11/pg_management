<?php

namespace Modules\Payment\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class Payment extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'pg_id',
        'room_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'remarks',
        'verified',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => PaymentLog::class,
            'foreign_key' => 'payment_id',
            'name_field' => 'id',
            'model_name' => 'Payment',
            'system_remarks' => [
                'created' => 'New payment created: #{id}',
                'updated' => 'Payment updated: #{id}',
                'deleted' => 'Payment deleted: #{id}',
                'restored' => 'Payment restored: #{id}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'id';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
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
