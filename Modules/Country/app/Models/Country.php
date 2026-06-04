<?php

namespace Modules\Country\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Country\Database\Factories\CountryFactory;
use Modules\User\Models\User;

class Country extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    public $table = 'countries';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'id',
        'name',
        'code',
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
        ];
    }

    /**
     * Override logging configuration for Country model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => CountryLog::class,
            'foreign_key' => 'country_id',
            'name_field' => 'name',
            'model_name' => 'Country',
            'fields_to_log' => ['name', 'code', 'created_by', 'updated_by', 'deleted_by'],
            'exclude_fields' => [],
            'system_remarks' => [
                'created' => 'New country created: {name}',
                'updated' => 'Country updated: {name}',
                'deleted' => 'Country deleted: {name}',
                'restored' => 'Country restored: {name}',
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
     * Get the user who created the country
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the country
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the country
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Override getIgnoredFieldsForLogging to include country-specific fields
     * This helps prevent logging issues
     */
    protected function getIgnoredFieldsForLogging(): array
    {
        return [];
    }
}
