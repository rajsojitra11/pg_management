<?php

namespace Modules\Currency\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Currency\Database\Factories\CurrencyFactory;
use Modules\User\Models\User;

class Currency extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }

    protected $table = 'currencies';

    protected $fillable = [
        'currency_name',
        'currency_symbol',
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
     * Override logging configuration for Currency model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => CurrencyLog::class,
            'foreign_key' => 'currency_id',
            'name_field' => 'currency_name',
            'model_name' => 'Currency',
            'system_remarks' => [
                'created' => 'New currency created: {currency_name}',
                'updated' => 'Currency updated: {currency_name}',
                'deleted' => 'Currency deleted: {currency_name}',
                'restored' => 'Currency restored: {currency_name}',
            ],
        ];
    }

    /**
     * Override the name field for logging
     */
    protected function getNameField(): string
    {
        return 'currency_name';
    }

    /**
     * Get the user who created the currency
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the currency
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the currency
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
