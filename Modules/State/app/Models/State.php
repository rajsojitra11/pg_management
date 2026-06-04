<?php

namespace Modules\State\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Country\Models\Country;
use Modules\State\Database\Factories\StateFactory;
use Modules\User\Models\User;

class State extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): StateFactory
    {
        return StateFactory::new();
    }

    public $table = 'states';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'id',
        'name',
        'code',
        'country_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => StateLog::class,
            'foreign_key' => 'state_id',
            'name_field' => 'name',
            'model_name' => 'State',
            'system_remarks' => [
                'create' => __('state::message.system_state_created'),
                'update' => __('state::message.system_state_updated'),
                'delete' => __('state::message.system_state_deleted'),
            ],
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
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

    public function logs()
    {
        return $this->hasMany(StateLog::class, 'state_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear state-related caches when state data changes
        static::created(function () {
            self::clearStateCaches();
        });

        static::updated(function () {
            self::clearStateCaches();
        });

        static::deleted(function () {
            self::clearStateCaches();
        });

        static::restored(function () {
            self::clearStateCaches();
        });
    }

    /**
     * Clear all state-related caches
     */
    protected static function clearStateCaches(): void
    {
        try {
            Cache::tags(['states'])->flush();
        } catch (\Exception $e) {
            // Cache driver (e.g. array) may not support tags
        }
    }
}
