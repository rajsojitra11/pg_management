<?php

namespace Modules\Year\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\User\Models\User;
use Modules\Year\Database\Factories\YearFactory;

class Year extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): YearFactory
    {
        return YearFactory::new();
    }

    protected $table = 'years';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default', 'created_by', 'updated_by', 'deleted_by'];

    protected function casts(): array
    {
        return [
            'set_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Override logging configuration for Year model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => YearLog::class,
            'foreign_key' => 'year_id',
            'name_field' => 'name',
            'model_name' => 'Year',
            'system_remarks' => [
                'created' => 'New year created: {name}',
                'updated' => 'Year updated: {name}',
                'deleted' => 'Year deleted: {name}',
                'restored' => 'Year restored: {name}',
            ],
        ];
    }

    /**
     * Override the name field for logging
     */
    protected function getNameField(): string
    {
        return 'name';
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear year-related caches when year data changes
        static::created(function () {
            self::clearYearCaches();
        });

        static::updated(function () {
            self::clearYearCaches();
        });

        static::deleted(function () {
            self::clearYearCaches();
        });

        static::restored(function () {
            self::clearYearCaches();
        });
    }

    /**
     * Clear all year-related caches
     */
    protected static function clearYearCaches(): void
    {
        Cache::forget('year_dropdown_all');
        Cache::forget('year_dropdown_fiscal');
        Cache::forget('year_display_format');

        // Clear any other year-related caches that might exist
        try {
            Cache::tags(['years'])->flush();
        } catch (\Exception $e) {
            // Cache driver (e.g. array) may not support tags
        }
    }

    /**
     * Get the user who created the year
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the year
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the year
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
