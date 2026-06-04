<?php

namespace Modules\City\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\City\Database\Factories\CityFactory;
use Modules\Country\Models\Country;
use Modules\State\Models\State;
use Modules\User\Models\User;

class City extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    public $table = 'cities';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'id',
        'name',
        'code',
        'state_id',
        'country_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => CityLog::class,
            'foreign_key' => 'city_id',
            'name_field' => 'name',
            'model_name' => 'City',
            'system_remarks' => [
                'created' => 'New city created: {name}',
                'updated' => 'City updated: {name}',
                'deleted' => 'City deleted: {name}',
                'restored' => 'City restored: {name}',
            ],
        ];
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function logs()
    {
        return $this->hasMany(CityLog::class, 'city_id');
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

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear city-related caches when city data changes
        static::created(function () {
            self::clearCityCaches();
        });

        static::updated(function () {
            self::clearCityCaches();
        });

        static::deleted(function () {
            self::clearCityCaches();
        });

        static::restored(function () {
            self::clearCityCaches();
        });
    }

    /**
     * Clear all city-related caches
     */
    protected static function clearCityCaches(): void
    {
        try {
            Cache::tags(['cities'])->flush();
        } catch (\Exception $e) {
            // Cache driver (e.g. array) may not support tags
        }
    }
}
