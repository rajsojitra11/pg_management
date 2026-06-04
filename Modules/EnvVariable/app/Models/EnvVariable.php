<?php

namespace Modules\EnvVariable\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\EnvVariable\Database\Factories\EnvVariableFactory;
use Modules\User\Models\User;
use Symfony\Component\Process\Process;

class EnvVariable extends Model
{
    use HasActivityLogging, HasFactory, HasPublicId, SoftDeletes;

    /**
     * Suppression flag for the auto-operations triggered by the model's
     * created/updated/deleted boot listeners (cache clears, composer
     * dump-autoload, .env sync). Set to true during installer apply so the
     * installer can perform ONE final pass at the end instead of N passes
     * (one per row written by the seeder cascade). Encryption in the
     * `saving` listener is intentionally NOT gated on this flag — values
     * must always be persisted in their final form.
     */
    public static bool $skipAutoOperations = false;

    protected $table = 'env_variables';

    protected $fillable = [
        'key',
        'value',
        'type',
        'options',
        'category',
        'validation_rules',
        'description',
        'is_encrypted',
        'is_sensitive',
        'is_editable',
        'requires_restart',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_encrypted' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_editable' => 'boolean',
            'requires_restart' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(EnvVariableLog::class, 'env_variable_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    protected function getNameField(): string
    {
        return 'key';
    }

    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => EnvVariableLog::class,
            'foreign_key' => 'env_variable_id',
            'name_field' => 'key',
            'model_name' => 'Environment Variable',
            'system_remarks' => [
                'create' => __('envvariable::message.system_env_variable_created'),
                'update' => __('envvariable::message.system_env_variable_updated'),
                'delete' => __('envvariable::message.system_env_variable_deleted'),
            ],
        ];
    }

    public function getDecryptedValueAttribute()
    {
        if ($this->is_encrypted && $this->value) {
            try {
                return decrypt($this->value);
            } catch (Exception $e) {
                return $this->value;
            }
        }

        return $this->value;
    }

    // Note: encryption of `value` happens in the `saving` boot listener so it
    // runs after mass-assignment has populated `is_encrypted`. A setValueAttribute
    // mutator can't be used because Eloquent fill() iterates fillable in order,
    // so `value` arrives before `is_encrypted` and the flag would still be false.

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    protected static function newFactory(): EnvVariableFactory
    {
        return EnvVariableFactory::new();
    }

    /**
     * Boot the model and register event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Encrypt `value` here (not in a setValueAttribute mutator) because
        // mass-assignment fills `value` before `is_encrypted`, leaving the flag
        // false at mutator-time. Idempotent: skips re-encrypting an already
        // encrypted payload, and skips when `value` wasn't touched.
        static::saving(function ($envVariable) {
            if (! $envVariable->is_encrypted || ! $envVariable->isDirty('value')) {
                return;
            }
            $raw = $envVariable->getAttributes()['value'] ?? null;
            if ($raw === null || $raw === '') {
                return;
            }
            try {
                decrypt($raw);
                // Already encrypted — leave as-is.
            } catch (Exception $e) {
                $envVariable->attributes['value'] = encrypt($raw);
            }
        });

        static::created(function ($envVariable) {
            if (self::$skipAutoOperations) {
                return;
            }
            $envVariable->handleAutoOperations('created');
        });

        static::updated(function ($envVariable) {
            if (self::$skipAutoOperations) {
                return;
            }
            $envVariable->handleAutoOperations('updated');
        });

        static::deleted(function ($envVariable) {
            if (self::$skipAutoOperations) {
                return;
            }
            $envVariable->handleAutoOperations('deleted');
        });
    }

    /**
     * Handle automatic operations after model events
     */
    protected function handleAutoOperations(string $event): void
    {
        // Test-environment guard — see syncToEnvFile() and EnvFileService for
        // the same precedent. Without this, every model write in EnvVariableTest
        // shells out to `composer dump-autoload` (5–30s each) and clears the
        // route/view caches mid-suite, which corrupts later tests because those
        // caches live outside the RefreshDatabase transaction.
        if (app()->environment('testing')) {
            return;
        }

        try {
            if ($this->requires_restart || in_array($event, ['created', 'deleted'])) {
                $this->clearAllCaches();
                $this->runComposerDumpAutoload();
            }

            $this->syncToEnvFile();
        } catch (Exception $e) {
            Log::error("Failed to run auto-operations for {$event} event: ".$e->getMessage(), [
                'key' => $this->key,
                'event' => $event,
            ]);
        }
    }

    /**
     * Clear all Laravel caches
     */
    public function clearAllCaches(): bool
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear all caches: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Clear only config cache
     */
    public function clearConfigCache(): bool
    {
        try {
            Artisan::call('config:clear');

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear config cache: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Clear only application cache
     */
    public function clearApplicationCache(): bool
    {
        try {
            Artisan::call('cache:clear');

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear application cache: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Clear routes and views cache
     */
    public function clearRoutesAndViews(): bool
    {
        try {
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear routes and views cache: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Run composer dump-autoload
     */
    public function runComposerDumpAutoload(): bool
    {
        try {
            $process = new Process(['composer', 'dump-autoload']);
            $process->setWorkingDirectory(base_path());
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            } else {

                return false;
            }
        } catch (Exception $e) {

            return false;
        }
    }

    /**
     * Sync environment variables to .env file
     */
    public static function syncToEnvFile(): bool
    {
        // Test-environment guard — see EnvFileService::updateEnvFile() for
        // the same rationale. Without this, every EnvVariableTest run
        // re-pollutes .env with TEST_* + faker-Latin keys.
        if (app()->environment('testing')) {
            return true;
        }

        try {
            $envFile = base_path('.env');
            $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

            $variables = static::active()->orderBy('sort_order')->get();

            foreach ($variables as $variable) {
                $key = $variable->key;
                $value = $variable->decrypted_value ?? '';

                // Escape value if it contains spaces or special characters
                if (preg_match('/[\s#]/', $value)) {
                    $value = '"'.str_replace('"', '\"', $value).'"';
                }

                $envLine = $key.'='.$value;

                // Replace existing line or add new one
                if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $envContent)) {
                    $envContent = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $envLine, $envContent);
                } else {
                    $envContent .= "\n".$envLine;
                }
            }

            $success = file_put_contents($envFile, $envContent) !== false;

            if ($success) {
            } else {
                Log::error('Failed to write to .env file');
            }

            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle post-update operations for variables that require restart
     */
    public function handlePostUpdate(): bool
    {
        $success = true;
        $operations = [];

        try {
            if ($this->requires_restart) {
                // Clear all caches
                if (! $this->clearAllCaches()) {
                    $success = false;
                    $operations[] = 'clear_caches_failed';
                } else {
                    $operations[] = 'clear_caches_success';
                }

                // Run composer dump-autoload
                if (! $this->runComposerDumpAutoload()) {
                    $success = false;
                    $operations[] = 'dump_autoload_failed';
                } else {
                    $operations[] = 'dump_autoload_success';
                }
            }

            // Always sync to .env file
            if (! static::syncToEnvFile()) {
                $success = false;
                $operations[] = 'sync_env_failed';
            } else {
                $operations[] = 'sync_env_success';
            }

            return $success;
        } catch (Exception $e) {

            return false;
        }
    }
}
