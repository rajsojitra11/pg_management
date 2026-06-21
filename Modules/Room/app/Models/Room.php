<?php

namespace Modules\Room\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Database\Factories\RoomFactory;
use Modules\Tenant\Models\Tenant;

class Room extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }

    protected $table = 'pg_rooms';

    protected $fillable = [
        'pg_id',
        'category_id',
        'room_no',
        'bed_capacity',
        'rent_amount',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'rent_amount' => 'decimal:2',
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => RoomLog::class,
            'foreign_key' => 'pg_room_id',
            'name_field' => 'room_no',
            'model_name' => 'Room',
            'system_remarks' => [
                'created' => 'New room created: {room_no}',
                'updated' => 'Room updated: {room_no}',
                'deleted' => 'Room deleted: {room_no}',
                'restored' => 'Room restored: {room_no}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'room_no';
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function category()
    {
        return $this->belongsTo(RoomCategory::class, 'category_id');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'room_id');
    }
}
