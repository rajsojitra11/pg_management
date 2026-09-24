<?php

namespace Modules\FoodMenu\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FoodMenu\Database\Factories\FoodMenuFactory;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;

class FoodMenu extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): FoodMenuFactory
    {
        return FoodMenuFactory::new();
    }

    protected $table = 'food_menus';

    protected $fillable = [
        'user_id',
        'pg_id',
        'menu_type',
        'title',
        'week_start_date',
        'special_date',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'week_start_date' => 'string',
            'special_date' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => FoodMenuLog::class,
            'foreign_key' => 'food_menu_id',
            'name_field' => 'title',
            'model_name' => 'FoodMenu',
            'system_remarks' => [
                'created' => 'New food menu created: {title}',
                'updated' => 'Food menu updated: {title}',
                'deleted' => 'Food menu deleted: {title}',
                'restored' => 'Food menu restored: {title}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'title';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FoodMenuItem::class, 'food_menu_id');
    }
}
