<?php

namespace Modules\Email\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Email\Database\Factories\EmailConfigFactory;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;

class EmailConfig extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): EmailConfigFactory
    {
        return EmailConfigFactory::new();
    }

    protected $table = 'email_configs';

    protected $fillable = [
        'pg_id',
        'sender_email',
        'sender_name',
        'subject_prefix',
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
            'log_model' => EmailConfigLog::class,
            'foreign_key' => 'email_config_id',
            'name_field' => 'id',
            'model_name' => 'EmailConfig',
            'system_remarks' => [
                'created' => 'Email config created for PG',
                'updated' => 'Email config updated',
                'deleted' => 'Email config deleted',
                'restored' => 'Email config restored',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'id';
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
