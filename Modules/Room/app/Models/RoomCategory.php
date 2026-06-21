<?php

namespace Modules\Room\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PgManagement\Models\PgManagement;
use Modules\Room\Database\Factories\RoomCategoryFactory;

class RoomCategory extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): RoomCategoryFactory
    {
        return RoomCategoryFactory::new();
    }

    protected $table = 'pg_room_categories';

    protected $fillable = [
        'pg_id',
        'category_name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => RoomCategoryLog::class,
            'foreign_key' => 'pg_room_category_id',
            'name_field' => 'category_name',
            'model_name' => 'RoomCategory',
            'system_remarks' => [
                'created' => 'New room category created: {category_name}',
                'updated' => 'Room category updated: {category_name}',
                'deleted' => 'Room category deleted: {category_name}',
                'restored' => 'Room category restored: {category_name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'category_name';
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }
}
