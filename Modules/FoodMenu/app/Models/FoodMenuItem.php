<?php

namespace Modules\FoodMenu\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FoodMenu\Database\Factories\FoodMenuItemFactory;

class FoodMenuItem extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): FoodMenuItemFactory
    {
        return FoodMenuItemFactory::new();
    }

    protected $table = 'food_menu_items';

    protected $fillable = [
        'food_menu_id',
        'day',
        'meal_time',
        'item_name',
        'description',
        'sort_order',
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

    public function foodMenu()
    {
        return $this->belongsTo(FoodMenu::class, 'food_menu_id');
    }
}
