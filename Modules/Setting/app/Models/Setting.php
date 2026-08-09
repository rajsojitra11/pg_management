<?php

namespace Modules\Setting\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\City\Models\City;
use Modules\Country\Models\Country;
use Modules\Setting\Database\Factories\SettingFactory;
use Modules\State\Models\State;
use Modules\User\Models\User;

class Setting extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    protected $table = 'settings';

    protected $fillable = ['id', 'company_name', 'tag_line', 'favicon', 'logo', 'logo_dark', 'gst_number', 'pancard_number', 'tan_number', 'email', 'mobile', 'address', 'country_id', 'state_id', 'city_id', 'year_display_format', 'created_by', 'updated_by', 'deleted_by'];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Override logging configuration for Setting model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => SettingLog::class,
            'foreign_key' => 'setting_id',
            'name_field' => 'company_name',
            'model_name' => 'Setting',
            'system_remarks' => [
                'created' => 'New setting created: {company_name}',
                'updated' => 'Setting updated: {company_name}',
                'deleted' => 'Setting deleted: {company_name}',
                'restored' => 'Setting restored: {company_name}',
            ],
        ];
    }

    /**
     * Override the name field for logging
     */
    protected function getNameField(): string
    {
        return 'company_name';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id')->withTrashed();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id')->withTrashed();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id')->withTrashed();
    }

    /**
     * Get the user who created the setting
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the setting
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the setting
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
