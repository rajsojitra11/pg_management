<?php

namespace Modules\Tenant\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\City\Models\City;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Models\Room;
use Modules\State\Models\State;
use Modules\User\Models\User;

class Tenant extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'status',

        // Step 1: PG & Personal Details
        'pg_id',
        'room_id',
        'bed_no',
        'date_of_birth',
        'gender',
        'occupation',

        // Step 2: Stay & Payment Details
        'checkin_date',
        'expected_checkout_date',
        'monthly_rent',
        'security_deposit',
        'payment_method',
        'id_proof_type',
        'id_proof_number',
        'id_proof_file',

        // Step 3: Emergency Contact & Permanent Address
        'emergency_contact_name',
        'emergency_relation',
        'emergency_contact_number',
        'permanent_state_id',
        'permanent_city_id',
        'permanent_address',
        'additional_notes',

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
            'date_of_birth' => 'date',
            'checkin_date' => 'date',
            'expected_checkout_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'security_deposit' => 'decimal:2',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => TenantLog::class,
            'foreign_key' => 'tenant_id',
            'name_field' => 'name',
            'model_name' => 'Tenant',
            'system_remarks' => [
                'created' => 'New Tenant created: {name}',
                'updated' => 'Tenant updated: {name}',
                'deleted' => 'Tenant deleted: {name}',
                'restored' => 'Tenant restored: {name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'name';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(\Modules\Payment\Models\Payment::class, 'tenant_id');
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function permanentState()
    {
        return $this->belongsTo(State::class, 'permanent_state_id');
    }

    public function permanentCity()
    {
        return $this->belongsTo(City::class, 'permanent_city_id');
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
