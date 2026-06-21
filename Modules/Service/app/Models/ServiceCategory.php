<?php

namespace Modules\Service\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Service\Database\Factories\ServiceCategoryFactory;

class ServiceCategory extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): ServiceCategoryFactory
    {
        return ServiceCategoryFactory::new();
    }

    protected $table = 'service_categories';

    protected $fillable = [
        'service_category_name',
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
            'log_model' => ServiceCategoryLog::class,
            'foreign_key' => 'service_category_id',
            'name_field' => 'service_category_name',
            'model_name' => 'ServiceCategory',
            'system_remarks' => [
                'created' => 'New service category created: {service_category_name}',
                'updated' => 'Service category updated: {service_category_name}',
                'deleted' => 'Service category deleted: {service_category_name}',
                'restored' => 'Service category restored: {service_category_name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'service_category_name';
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'service_category_id');
    }
}
