<?php

namespace Modules\Email\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Email\Database\Factories\EmailTemplateFactory;
use Modules\User\Models\User;

class EmailTemplate extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }

    protected $table = 'email_templates';

    protected $fillable = [
        'name',
        'subject',
        'body',
        'placeholders',
        'is_default',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => 'string',
        ];
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => EmailTemplateLog::class,
            'foreign_key' => 'email_template_id',
            'name_field' => 'name',
            'model_name' => 'EmailTemplate',
            'system_remarks' => [
                'created' => 'Email template created: {name}',
                'updated' => 'Email template updated: {name}',
                'deleted' => 'Email template deleted: {name}',
                'restored' => 'Email template restored: {name}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'name';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
