<?php

namespace Modules\Service\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Service\Database\Factories\ServiceFactory;

class Service extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }

    protected $table = 'services';

    protected $fillable = [
        'service_category_id',
        'service_name',
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
            'log_model' => ServiceLog::class,
            'foreign_key' => 'service_id',
            'name_field' => 'service_name',
            'model_name' => 'Service',
            'system_remarks' => [
                'created' => 'New service created: {service_name}',
                'updated' => 'Service updated: {service_name}',
                'deleted' => 'Service deleted: {service_name}',
                'restored' => 'Service restored: {service_name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'service_name';
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
