<?php

namespace Modules\Subscription\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Subscription\Database\Factories\SubscriptionFactory;
use Modules\User\Models\User;

class Subscription extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    protected $table = 'subscriptions';

    protected $fillable = [
        'subscriber_name',
        'email',
        'phone',
        'plan_type',
        'start_date',
        'end_date',
        'status',
        'amount',
        'payment_status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => SubscriptionLog::class,
            'foreign_key' => 'subscription_id',
            'name_field' => 'subscriber_name',
            'model_name' => 'Subscription',
            'system_remarks' => [
                'created' => 'New subscription created: {subscriber_name}',
                'updated' => 'Subscription updated: {subscriber_name}',
                'deleted' => 'Subscription deleted: {subscriber_name}',
                'restored' => 'Subscription restored: {subscriber_name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'subscriber_name';
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
}
