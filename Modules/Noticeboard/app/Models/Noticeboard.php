<?php

namespace Modules\Noticeboard\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Noticeboard\Database\Factories\NoticeboardFactory;
use Modules\PgManagement\Models\PgManagement;
use Modules\User\Models\User;

class Noticeboard extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    protected static function newFactory(): NoticeboardFactory
    {
        return NoticeboardFactory::new();
    }

    protected $table = 'noticeboards';

    protected $fillable = [
        'user_id',
        'pg_id',
        'title',
        'image',
        'description',
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
            'log_model' => NoticeboardLog::class,
            'foreign_key' => 'noticeboard_id',
            'name_field' => 'title',
            'model_name' => 'Noticeboard',
            'system_remarks' => [
                'created' => 'New notice created: {title}',
                'updated' => 'Notice updated: {title}',
                'deleted' => 'Notice deleted: {title}',
                'restored' => 'Notice restored: {title}',
            ],
        ];
    }

    protected function getNameField(): string
    {
        return 'title';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pg()
    {
        return $this->belongsTo(PgManagement::class, 'pg_id');
    }
}
