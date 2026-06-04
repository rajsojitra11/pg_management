<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait HasActivityLogging
{
    /**
     * Cached device info to avoid repeated regex parsing within the same request.
     * Resets automatically between requests in PHP-FPM.
     */
    protected static ?array $cachedDeviceInfo = null;

    /**
     * Boot the activity logging trait
     * Sets up event listeners for all CRUD operations
     */
    protected static function bootHasActivityLogging()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        // Handle deleted_by field for soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::deleting(function ($model) {
                if (! $model->isForceDeleting()) {
                    $model->deleted_by = Auth::id();
                    $model->saveQuietly();
                }
            });

            // Handle deleted_by for force deletes (optional)
            static::forceDeleting(function ($model) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            });
        }

        // Handle creation logging
        static::created(function ($model) {
            $request = request();
            $userRemark = ($request && $request->filled('user_remark')) ? $request->user_remark : null;
            $model->logActivity('created', $userRemark, null, null, $model->getAttributes());
        });

        // Handle update logging
        static::updated(function ($model) {
            // Skip if this was just created (handled by created event above)
            if ($model->wasRecentlyCreated) {
                return;
            }

            // Additional check: if this is a web request and the model wasn't actually changed,
            // don't log it (prevents false logs from session data or form resubmissions)
            $request = request();
            if ($request && ! $request->isMethod('put') && ! $request->isMethod('patch')) {
                // Allow POST requests that have session-based action data (block/unblock/activate/deactivate)
                $hasSessionActionData = static::getSessionActionType($model) && static::getSessionActionReason($model);
                if (! $hasSessionActionData) {
                    // For POST requests with X-HTTP-Method-Override header
                    if (
                        ! $request->headers->has('X-HTTP-Method-Override') ||
                        ! in_array($request->headers->get('X-HTTP-Method-Override'), ['PUT', 'PATCH'])
                    ) {
                        return;
                    }
                }
            }

            $changes = $model->getChanges();
            $oldValues = $model->getOriginal();

            // Get fields to ignore when checking for meaningful changes
            $ignoredFields = (method_exists($model, 'getIgnoredFieldsForLogging')) ? $model->getIgnoredFieldsForLogging() : [];
            $defaultIgnoredFields = ['updated_at', 'remember_token'];
            $fieldsToIgnore = array_merge($defaultIgnoredFields, $ignoredFields);

            // Filter out ignored fields from changes
            $meaningfulChanges = array_diff_key($changes, array_flip($fieldsToIgnore));

            // Only log if there are meaningful changes
            if (empty($meaningfulChanges)) {
                return;
            }

            // Check for session-based action data (for specific module actions)
            $sessionActionReason = static::getSessionActionReason($model);
            $sessionActionType = static::getSessionActionType($model);

            if ($sessionActionReason && $sessionActionType) {
                $userRemark = $sessionActionReason;
                $activity = $sessionActionType;

                // Clear session data after use
                static::clearSessionActionData($model);
            } else {
                // Default update handling
                $request = request();
                $userRemark = ($request && $request->filled('user_remark')) ? $request->user_remark : null;
                $activity = 'updated';
            }

            $model->logActivity($activity, $userRemark, null, $oldValues, $model->getAttributes());
        });

        // Handle deletion logging
        static::deleting(function ($model) {
            // Check for deletion reason from session
            $deletionReason = static::getSessionDeletionReason($model);

            // Fallback: read user_remark from request (matches the updated event behavior)
            if (! $deletionReason) {
                $request = request();
                $deletionReason = ($request && $request->filled('user_remark')) ? $request->user_remark : null;
            }

            $userRemark = $deletionReason ?: null;

            // Log the deletion while the model still exists in the database
            $model->logActivity('deleted', $userRemark, null, $model->getAttributes(), null);

            // Clear the session key after use
            if ($deletionReason) {
                static::clearSessionDeletionReason($model);
            }
        });

        // Handle restoration logging (only for models with SoftDeletes)
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->logActivity('restored', 'Record restored', null, null, $model->getAttributes());
            });
        }
    }

    /**
     * Main logging method - handles all activity logging for any module
     */
    public function logActivity(
        string $activity,
        ?string $userRemark = null,
        ?string $systemRemark = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Carbon $createdAt = null
    ): void {
        try {
            $config = $this->getLoggingConfig();
            $logModelClass = $config['log_model'];

            // Validate log model exists
            if (! class_exists($logModelClass)) {
                throw new Exception("Log model class {$logModelClass} does not exist");
            }

            $userId = Auth::id() ?: 1;
            $entryDate = $createdAt ?: now();

            // Generate system remark if not provided
            if (! $systemRemark) {
                $systemRemark = $this->generateSystemRemark($activity, $config, $oldValues, $newValues);
            }

            // Override system remark for seeder context
            if (static::isSeederContext()) {
                $systemRemark = 'Initial Data Created By System Setup';
            }

            // Generate user remark if not provided
            if (! $userRemark) {
                $userRemark = $this->generateUserRemark($activity, $config);
            }

            // Get device/browser information
            $deviceInfo = $this->getDeviceInfo();

            // Prepare log data
            $logData = [
                'user_id' => $userId,
                $config['foreign_key'] => $this->getKey(), // Dynamic foreign key (user_id, currency_id, etc.)
                'activity' => $activity,
                'user_remark' => $userRemark,
                'system_remark' => $systemRemark,
                'old_values' => $this->enrichValuesWithFkLabels($oldValues),
                'new_values' => $this->enrichValuesWithFkLabels($newValues),
                'created_by' => $userId,
                'ip_address' => $deviceInfo['ip_address'],
                //'location' => $deviceInfo['location'] ?? 'Unknown',
                'user_agent' => $deviceInfo['user_agent'],
                'device' => $deviceInfo['device'],
                'platform' => $deviceInfo['platform'],
                'browser' => $deviceInfo['browser'],
            ];

            // Handle additional log metadata from models (for Supplier module)
            if (isset($this->logMetadata) && is_array($this->logMetadata)) {
                foreach ($this->logMetadata as $key => $value) {
                    $logData[$key] = $value;
                }
            }

            // Special handling for User model logs which has user_id_acting_on field
            if (class_basename($this) === 'User') {
                $logData['user_id_acting_on'] = $this->getKey(); // The user being acted upon
                $logData['successful'] = 1; // Default to successful
                $logData['logout_reason'] = null; // Default to null

                // Override logout reason if this is a logout activity
                if ($activity === 'logout') {
                    $logData['logout_reason'] = 'logout';
                }
            }

            // Create log entry
            $logEntry = new $logModelClass($logData);
            $logEntry->created_at = $entryDate;
            // Note: No updated_at for immutable audit logs
            $logEntry->save();
        } catch (Exception $e) {
            Log::error('Activity logging failed in HasActivityLogging trait', [
                'model' => get_class($this),
                'model_id' => $this->getKey() ?? 'unknown',
                'activity' => $activity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Get logging configuration for the current model
     * Can be implemented by each model that uses this trait
     * If not implemented, will use automatic configuration
     */
    protected function getLoggingConfig(): array
    {
        // Auto-generate config if not overridden
        $modelName = class_basename($this);
        $moduleName = $this->getModuleName();

        return [
            'log_model' => "\\Modules\\{$moduleName}\\Models\\{$modelName}Log",
            'foreign_key' => strtolower($modelName).'_id',
            'name_field' => $this->getNameField(),
            'model_name' => $modelName,
            'system_remarks' => $this->getDefaultSystemRemarks($modelName),
        ];
    }

    /**
     * Get the module name from the model namespace
     */
    protected function getModuleName(): string
    {
        $namespace = get_class($this);
        if (preg_match('/Modules\\\\([^\\\\]+)\\\\/', $namespace, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    /**
     * Get the primary name field for this model
     * Override in models to specify the correct field
     */
    protected function getNameField(): string
    {
        // Common name field patterns
        $nameFields = ['name', 'title', 'label', 'code', 'key', 'description'];

        foreach ($nameFields as $field) {
            if (isset($this->attributes[$field])) {
                return $field;
            }
        }

        return 'id'; // Fallback to ID
    }

    /**
     * Get default system remarks for a model
     */
    protected function getDefaultSystemRemarks(string $modelName): array
    {
        return [
            self::ACTIVITY_CREATED => "New {$modelName} created: {{$this->getNameField()}}",
            self::ACTIVITY_UPDATED => "{$modelName} updated: {{$this->getNameField()}}",
            self::ACTIVITY_DELETED => "{$modelName} deleted: {{$this->getNameField()}}",
            self::ACTIVITY_RESTORED => "{$modelName} restored: {{$this->getNameField()}}",
            self::ACTIVITY_FORCE_DELETED => "{$modelName} permanently deleted: {{$this->getNameField()}}",
            self::ACTIVITY_ACTIVATED => "{$modelName} activated: {{$this->getNameField()}}",
            self::ACTIVITY_DEACTIVATED => "{$modelName} deactivated: {{$this->getNameField()}}",
            self::ACTIVITY_UNBLOCKED => "{$modelName} unblocked: {{$this->getNameField()}}",
            self::ACTIVITY_LOGIN => "User logged in: {{$this->getNameField()}}",
            self::ACTIVITY_LOGOUT => "User logged out: {{$this->getNameField()}}",
            self::ACTIVITY_LOGIN_FAILED => "Failed login attempt: {{$this->getNameField()}}",
            self::ACTIVITY_ACCOUNT_BLOCKED => "Account blocked: {{$this->getNameField()}}",
            self::ACTIVITY_VIEWED => "{$modelName} viewed: {{$this->getNameField()}}",
            self::ACTIVITY_EXPORTED => "{$modelName} exported: {{$this->getNameField()}}",
            self::ACTIVITY_IMPORTED => "{$modelName} imported: {{$this->getNameField()}}",
            self::ACTIVITY_BULK_OPERATION => "Bulk operation on {$modelName}: {{$this->getNameField()}}",
            self::ACTIVITY_CACHE_CLEARED => "{$modelName} cache cleared: {{$this->getNameField()}}",
            self::ACTIVITY_NAME_UPDATED => "{$modelName} name updated: {{$this->getNameField()}}",
            self::ACTIVITY_SYMBOL_UPDATED => "{$modelName} symbol updated: {{$this->getNameField()}}",
            self::ACTIVITY_VALUE_UPDATED => "{$modelName} value updated: {{$this->getNameField()}}",
            self::ACTIVITY_PRIORITY_UPDATED => "{$modelName} priority updated: {{$this->getNameField()}}",
            self::ACTIVITY_RELATIONSHIP_UPDATED => "{$modelName} relationships updated: {{$this->getNameField()}}",
            self::ACTIVITY_PERMISSIONS_UPDATED => "{$modelName} permissions updated: {{$this->getNameField()}}",
            self::ACTIVITY_PERMISSIONS_SYNCED => "{$modelName} permissions synchronized: {{$this->getNameField()}}",
            self::ACTIVITY_SYNCHRONIZED => "{$modelName} synchronized: {{$this->getNameField()}}",
            self::ACTIVITY_MIGRATED => "{$modelName} migrated: {{$this->getNameField()}}",
            self::ACTIVITY_ARCHIVED => "{$modelName} archived: {{$this->getNameField()}}",
            self::ACTIVITY_UNARCHIVED => "{$modelName} unarchived: {{$this->getNameField()}}",
            self::ACTIVITY_LABEL_PRINTED => "{$modelName} label printed: {{$this->getNameField()}}",
        ];
    }

    /**
     * All supported activity types across the system
     */
    public const ACTIVITY_CREATED = 'created';

    public const ACTIVITY_UPDATED = 'updated';

    public const ACTIVITY_DELETED = 'deleted';

    public const ACTIVITY_RESTORED = 'restored';

    public const ACTIVITY_FORCE_DELETED = 'force_deleted';

    // Status Management Activities
    public const ACTIVITY_ACTIVATED = 'activated';

    public const ACTIVITY_DEACTIVATED = 'deactivated';

    public const ACTIVITY_UNBLOCKED = 'unblocked';

    // Authentication & Security Activities
    public const ACTIVITY_LOGIN = 'login';

    public const ACTIVITY_LOGOUT = 'logout';

    public const ACTIVITY_LOGIN_FAILED = 'login_failed';

    public const ACTIVITY_ACCOUNT_BLOCKED = 'account_blocked';

    // Data Management Activities
    public const ACTIVITY_VIEWED = 'viewed';

    public const ACTIVITY_EXPORTED = 'exported';

    public const ACTIVITY_IMPORTED = 'imported';

    public const ACTIVITY_BULK_OPERATION = 'bulk_operation';

    public const ACTIVITY_CACHE_CLEARED = 'cache_cleared';

    // Field-Specific Update Activities
    public const ACTIVITY_NAME_UPDATED = 'name_updated';

    public const ACTIVITY_SYMBOL_UPDATED = 'symbol_updated';

    public const ACTIVITY_VALUE_UPDATED = 'value_updated';

    public const ACTIVITY_PRIORITY_UPDATED = 'priority_updated';

    public const ACTIVITY_RELATIONSHIP_UPDATED = 'relationship_updated';

    public const ACTIVITY_PERMISSIONS_UPDATED = 'permissions_updated';

    public const ACTIVITY_PERMISSIONS_SYNCED = 'permissions_synced';

    // System Activities
    public const ACTIVITY_SYNCHRONIZED = 'synchronized';

    public const ACTIVITY_MIGRATED = 'migrated';

    public const ACTIVITY_ARCHIVED = 'archived';

    public const ACTIVITY_UNARCHIVED = 'unarchived';

    public const ACTIVITY_LABEL_PRINTED = 'label_printed';

    /**
     * Get all supported activities
     */
    public static function getAllActivities(): array
    {
        return [
            // Core CRUD
            self::ACTIVITY_CREATED,
            self::ACTIVITY_UPDATED,
            self::ACTIVITY_DELETED,
            self::ACTIVITY_RESTORED,
            self::ACTIVITY_FORCE_DELETED,

            // Status Management
            self::ACTIVITY_ACTIVATED,
            self::ACTIVITY_DEACTIVATED,
            self::ACTIVITY_UNBLOCKED,

            // Authentication & Security
            self::ACTIVITY_LOGIN,
            self::ACTIVITY_LOGOUT,
            self::ACTIVITY_LOGIN_FAILED,
            self::ACTIVITY_ACCOUNT_BLOCKED,

            // Data Management
            self::ACTIVITY_VIEWED,
            self::ACTIVITY_EXPORTED,
            self::ACTIVITY_IMPORTED,
            self::ACTIVITY_BULK_OPERATION,
            self::ACTIVITY_CACHE_CLEARED,

            // Field-Specific Updates
            self::ACTIVITY_NAME_UPDATED,
            self::ACTIVITY_SYMBOL_UPDATED,
            self::ACTIVITY_VALUE_UPDATED,
            self::ACTIVITY_PRIORITY_UPDATED,
            self::ACTIVITY_RELATIONSHIP_UPDATED,
            self::ACTIVITY_PERMISSIONS_UPDATED,
            self::ACTIVITY_PERMISSIONS_SYNCED,

            // System Activities
            self::ACTIVITY_SYNCHRONIZED,
            self::ACTIVITY_MIGRATED,
            self::ACTIVITY_ARCHIVED,
            self::ACTIVITY_UNARCHIVED,

            // Print Activities
            self::ACTIVITY_LABEL_PRINTED,
        ];
    }

    /**
     * Generate system remark based on activity and model data
     */
    protected function generateSystemRemark(string $activity, array $config, ?array $oldValues = null, ?array $newValues = null): string
    {
        $remarks = $config['system_remarks'] ?? [];
        $nameField = $config['name_field'] ?? 'name';
        $modelName = $config['model_name'] ?? class_basename($this);

        // Get the record name/identifier
        // Priority: logMetadata → newValues → oldValues → model attributes → ID
        $recordName = null;
        if (isset($this->logMetadata) && is_array($this->logMetadata) && isset($this->logMetadata[$nameField])) {
            $recordName = $this->logMetadata[$nameField];
        }
        if (! $recordName && $newValues && isset($newValues[$nameField])) {
            $recordName = $newValues[$nameField];
        }
        if (! $recordName && $oldValues && isset($oldValues[$nameField])) {
            $recordName = $oldValues[$nameField];
        }
        $recordName = $recordName ?? $this->getAttribute($nameField) ?? $this->getKey() ?? 'Unknown';

        // Check for activity-specific system remark template
        if (isset($remarks[$activity])) {
            $template = $remarks[$activity];

            // Replace placeholders with actual values
            // Note: Replace {id} with recordName (not getKey) since getDefaultSystemRemarks uses {id} as placeholder
            $template = str_replace('{id}', $recordName, $template);
            $template = str_replace('{'.$nameField.'}', $recordName, $template);
            $template = str_replace('{model_name}', $modelName, $template);

            // Replace relationship placeholders (e.g., {user.name})
            if (preg_match_all('/{([a-zA-Z_]+)\.([a-zA-Z_]+)}/', $template, $matches)) {
                foreach ($matches[0] as $index => $placeholder) {
                    $relationName = $matches[1][$index];
                    $relationField = $matches[2][$index];

                    // Try to access the relationship
                    if (method_exists($this, $relationName) && $this->$relationName) {
                        $relationValue = $this->$relationName->$relationField ?? $placeholder;
                        $template = str_replace($placeholder, $relationValue, $template);
                    }
                }
            }

            // Replace any other field placeholders from new values
            if ($newValues) {
                foreach ($newValues as $field => $value) {
                    $template = str_replace('{'.$field.'}', (string) ($value ?? ''), $template);
                }
            }

            return $template;
        }

        // Enhanced default system remarks based on comprehensive activity mapping
        switch ($activity) {
            // Core CRUD Operations
            case self::ACTIVITY_CREATED:
                return "New {$modelName} created: {$recordName}";
            case self::ACTIVITY_UPDATED:
                return "{$modelName} updated: {$recordName}";
            case self::ACTIVITY_DELETED:
                return "{$modelName} deleted: {$recordName}";
            case self::ACTIVITY_RESTORED:
                return "{$modelName} restored from deletion: {$recordName}";
            case self::ACTIVITY_FORCE_DELETED:
                return "{$modelName} permanently deleted: {$recordName}";

                // Status Management
            case self::ACTIVITY_ACTIVATED:
                return "{$modelName} status changed to active: {$recordName}";
            case self::ACTIVITY_DEACTIVATED:
                return "{$modelName} status changed to inactive: {$recordName}";
            case self::ACTIVITY_UNBLOCKED:
                return "{$modelName} unblocked and reactivated: {$recordName}";

                // Authentication & Security
            case self::ACTIVITY_LOGIN:
                return "User successfully logged in: {$recordName}";
            case self::ACTIVITY_LOGOUT:
                return "User logged out: {$recordName}";
            case self::ACTIVITY_LOGIN_FAILED:
                return "Failed login attempt for: {$recordName}";
            case self::ACTIVITY_ACCOUNT_BLOCKED:
                return "Account blocked due to multiple failed attempts: {$recordName}";

                // Data Management
            case self::ACTIVITY_VIEWED:
                return "{$modelName} record accessed/viewed: {$recordName}";
            case self::ACTIVITY_EXPORTED:
                return "{$modelName} data exported: {$recordName}";
            case self::ACTIVITY_IMPORTED:
                return "{$modelName} data imported: {$recordName}";
            case self::ACTIVITY_BULK_OPERATION:
                return "Bulk operation performed on {$modelName}: {$recordName}";
            case self::ACTIVITY_CACHE_CLEARED:
                return "Cache cleared for {$modelName}: {$recordName}";

                // Field-Specific Updates
            case self::ACTIVITY_NAME_UPDATED:
                return "{$modelName} name updated: {$recordName}";
            case self::ACTIVITY_SYMBOL_UPDATED:
                return "{$modelName} symbol updated: {$recordName}";
            case self::ACTIVITY_VALUE_UPDATED:
                return "{$modelName} value updated: {$recordName}";
            case self::ACTIVITY_PRIORITY_UPDATED:
                return "{$modelName} priority updated: {$recordName}";
            case self::ACTIVITY_RELATIONSHIP_UPDATED:
                return "{$modelName} relationships updated: {$recordName}";
            case self::ACTIVITY_PERMISSIONS_UPDATED:
                return "{$modelName} permissions updated: {$recordName}";
            case self::ACTIVITY_PERMISSIONS_SYNCED:
                return "{$modelName} permissions synchronized: {$recordName}";

                // System Activities
            case self::ACTIVITY_SYNCHRONIZED:
                return "{$modelName} data synchronized: {$recordName}";
            case self::ACTIVITY_MIGRATED:
                return "{$modelName} data migrated: {$recordName}";
            case self::ACTIVITY_ARCHIVED:
                return "{$modelName} archived: {$recordName}";
            case self::ACTIVITY_UNARCHIVED:
                return "{$modelName} unarchived: {$recordName}";
            case self::ACTIVITY_LABEL_PRINTED:
                return "{$modelName} label printed: {$recordName}";

                // Default fallback
            default:
                return "{$modelName} {$activity}: {$recordName}";
        }
    }

    /**
     * Generate user remark based on activity
     */
    protected function generateUserRemark(string $activity, array $config): string
    {
        $modelName = $config['model_name'] ?? class_basename($this);

        switch ($activity) {
            // Core CRUD Operations
            case self::ACTIVITY_CREATED:
                return "New {$modelName} created";
            case self::ACTIVITY_UPDATED:
                return "{$modelName} information updated";
            case self::ACTIVITY_DELETED:
                return "{$modelName} removed";
            case self::ACTIVITY_RESTORED:
                return "{$modelName} restored";
            case self::ACTIVITY_FORCE_DELETED:
                return "{$modelName} permanently deleted";

                // Status Management
            case self::ACTIVITY_ACTIVATED:
                return "{$modelName} activated";
            case self::ACTIVITY_DEACTIVATED:
                return "{$modelName} deactivated";
            case self::ACTIVITY_UNBLOCKED:
                return "{$modelName} unblocked";

                // Authentication & Security
            case self::ACTIVITY_LOGIN:
                return 'User login successful';
            case self::ACTIVITY_LOGOUT:
                return 'User logged out';
            case self::ACTIVITY_LOGIN_FAILED:
                return 'Login attempt failed';
            case self::ACTIVITY_ACCOUNT_BLOCKED:
                return 'Account blocked for security';

                // Data Management
            case self::ACTIVITY_VIEWED:
                return "{$modelName} accessed";
            case self::ACTIVITY_EXPORTED:
                return "{$modelName} data exported";
            case self::ACTIVITY_IMPORTED:
                return "{$modelName} data imported";
            case self::ACTIVITY_BULK_OPERATION:
                return "Bulk operation on {$modelName}";
            case self::ACTIVITY_CACHE_CLEARED:
                return "{$modelName} cache cleared";

                // Field-Specific Updates
            case self::ACTIVITY_NAME_UPDATED:
                return "{$modelName} name changed";
            case self::ACTIVITY_SYMBOL_UPDATED:
                return "{$modelName} symbol changed";
            case self::ACTIVITY_VALUE_UPDATED:
                return "{$modelName} value changed";
            case self::ACTIVITY_PRIORITY_UPDATED:
                return "{$modelName} priority changed";
            case self::ACTIVITY_RELATIONSHIP_UPDATED:
                return "{$modelName} relationships changed";
            case self::ACTIVITY_PERMISSIONS_UPDATED:
                return "{$modelName} permissions changed";
            case self::ACTIVITY_PERMISSIONS_SYNCED:
                return "{$modelName} permissions synchronized";

                // System Activities
            case self::ACTIVITY_SYNCHRONIZED:
                return "{$modelName} synchronized";
            case self::ACTIVITY_MIGRATED:
                return "{$modelName} migrated";
            case self::ACTIVITY_ARCHIVED:
                return "{$modelName} archived";
            case self::ACTIVITY_UNARCHIVED:
                return "{$modelName} unarchived";
            case self::ACTIVITY_LABEL_PRINTED:
                return "{$modelName} label printed";

                // Default fallback
            default:
                return "{$modelName} {$activity}";
        }
    }

    /**
     * Get device/browser information from request
     */
    protected function getDeviceInfo(): array
    {
        // Check if we're in seeder context (don't cache seeder context)
        if (static::isSeederContext()) {
            return [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Data Creator',
                'device' => 'Server',
                'platform' => 'Server',
                'browser' => 'Server',
            ];
        }

        // Return cached device info if already parsed in this request
        if (static::$cachedDeviceInfo !== null) {
            return static::$cachedDeviceInfo;
        }

        $request = request();
        $userAgent = $request ? $request->userAgent() : '';

        // Simple device/browser detection
        $device = 'Unknown';
        $platform = 'Unknown';
        $browser = 'Unknown';

        if (! empty($userAgent)) {
            // Detect platform
            if (preg_match('/Windows NT/i', $userAgent)) {
                $platform = 'Windows';
            } elseif (preg_match('/Mac OS X/i', $userAgent)) {
                $platform = 'macOS';
            } elseif (preg_match('/Linux/i', $userAgent)) {
                $platform = 'Linux';
            } elseif (preg_match('/iPhone/i', $userAgent)) {
                $platform = 'iOS';
                $device = 'iPhone';
            } elseif (preg_match('/iPad/i', $userAgent)) {
                $platform = 'iOS';
                $device = 'iPad';
            } elseif (preg_match('/Android/i', $userAgent)) {
                $platform = 'Android';
                $device = preg_match('/Mobile/i', $userAgent) ? 'Android Phone' : 'Android Tablet';
            }

            // Detect browser
            if (preg_match('/Chrome/i', $userAgent) && ! preg_match('/Edg/i', $userAgent)) {
                $browser = 'Chrome';
            } elseif (preg_match('/Firefox/i', $userAgent)) {
                $browser = 'Firefox';
            } elseif (preg_match('/Safari/i', $userAgent) && ! preg_match('/Chrome/i', $userAgent)) {
                $browser = 'Safari';
            } elseif (preg_match('/Edg/i', $userAgent)) {
                $browser = 'Microsoft Edge';
            } elseif (preg_match('/Opera/i', $userAgent) || preg_match('/OPR/i', $userAgent)) {
                $browser = 'Opera';
            } elseif (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
                $browser = 'Internet Explorer';
            }

            // Set device if not already detected
            if ($device === 'Unknown') {
                if (preg_match('/Mobile/i', $userAgent)) {
                    $device = 'Mobile Device';
                } elseif (preg_match('/Tablet/i', $userAgent)) {
                    $device = 'Tablet';
                } else {
                    $device = 'Desktop';
                }
            }
        }

        $ip = clientIp($request);

        static::$cachedDeviceInfo = [
            'ip_address' => $ip,
            'location' => $ip ? getLocationFromIp($ip) : 'Unknown',
            'user_agent' => $userAgent,
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
        ];

        return static::$cachedDeviceInfo;
    }

    /**
     * Get session action reason for the current model
     */
    protected static function getSessionActionReason(Model $model): ?string
    {
        $modelName = strtolower(class_basename($model));

        return session("{$modelName}_action_reason_{$model->getKey()}");
    }

    /**
     * Get session action type for the current model
     */
    protected static function getSessionActionType(Model $model): ?string
    {
        $modelName = strtolower(class_basename($model));

        return session("{$modelName}_action_type_{$model->getKey()}");
    }

    /**
     * Get session deletion reason for the current model
     */
    protected static function getSessionDeletionReason(Model $model): ?string
    {
        $modelName = strtolower(class_basename($model));

        return session("{$modelName}_deletion_reason_{$model->getKey()}");
    }

    /**
     * Clear session action data for the current model
     */
    protected static function clearSessionActionData(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        session()->forget([
            "{$modelName}_action_reason_{$model->getKey()}",
            "{$modelName}_action_type_{$model->getKey()}",
        ]);
    }

    /**
     * Clear session deletion reason for the current model
     */
    protected static function clearSessionDeletionReason(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        session()->forget("{$modelName}_deletion_reason_{$model->getKey()}");
    }

    /**
     * Manual logging method for custom activities
     */
    public function logCustomActivity(
        string $activity,
        ?string $userRemark = null,
        ?string $systemRemark = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Carbon $createdAt = null
    ): void {
        $this->logActivity($activity, $userRemark, $systemRemark, $oldValues, $newValues, $createdAt);
    }

    /**
     * Backward compatibility method for direct controller logging
     * Supports the logAction pattern used in Setting and MenuMaster modules
     */
    public function logAction(
        string $activity,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userRemark = null,
        ?string $systemRemark = null,
        ?Carbon $createdAt = null
    ): void {
        $this->logActivity($activity, $userRemark, $systemRemark, $oldValues, $newValues, $createdAt);
    }

    /**
     * Support for activity logging pattern (BaseModel compatibility)
     * Creates specific module log entries
     */
    public function logToActivityLog(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            // Log to specific module log if available
            if (method_exists($this, 'getLoggingConfig')) {
                $this->logActivity($action, null, null, $oldValues, $newValues);
            }
        } catch (Exception $e) {
            Log::error('Activity logging failed in HasActivityLogging trait', [
                'model' => get_class($this),
                'model_id' => $this->getKey(),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if the current model supports activity logging
     */
    public function hasActivityLogging(): bool
    {
        return true;
    }

    /**
     * Get the log model class for this model
     */
    public function getLogModelClass(): string
    {
        $config = $this->getLoggingConfig();

        return $config['log_model'];
    }

    /**
     * Get all logs for this model instance
     */
    public function activityLogs()
    {
        $config = $this->getLoggingConfig();
        $logModelClass = $config['log_model'];
        $foreignKey = $config['foreign_key'];

        if (class_exists($logModelClass)) {
            return $logModelClass::where($foreignKey, $this->getKey())->orderBy('created_at', 'desc');
        }

        return collect();
    }

    /**
     * Check if we're running in a seeder context
     */
    protected static function isSeederContext(): bool
    {
        // Check if we're running artisan commands
        if (app()->runningInConsole()) {
            // Get command line arguments to check for seeding commands
            $argv = $_SERVER['argv'] ?? [];
            $command = implode(' ', $argv);

            // Check for various seeding commands
            return str_contains($command, 'db:seed') ||
                str_contains($command, 'migrate:fresh') ||
                str_contains($command, 'migrate:refresh') ||
                str_contains($command, 'db:fresh') ||
                str_contains($command, '--seed');
        }

        return false;
    }

    /**
     * Per-model cache of enrichable FK columns.
     * Maps `Class\Name` => [ fkColumn => ['related' => ClassName, 'method' => methodName] ].
     */
    private static array $enrichableFkCache = [];

    /**
     * FK columns we deliberately don't enrich — they're already represented
     * by `user_id` / `created_by` columns on the log row itself, so duplicating
     * a label adds noise without audit value.
     */
    private static array $skipFkColumns = ['created_by', 'updated_by', 'deleted_by'];

    /**
     * Enrich a values array with `<fk>_label` keys for every detected
     * `belongsTo` foreign key on this model. Lookups use `withTrashed()` when
     * the related model uses SoftDeletes, so audit rows still resolve labels
     * for entities deleted after the event was logged.
     *
     * Run lazily per save event; cached per class for the request lifecycle.
     * Best-effort: any lookup failure is swallowed (audit row still saved).
     *
     * @param  array<string,mixed>|null  $values
     * @return array<string,mixed>|null
     */
    protected function enrichValuesWithFkLabels(?array $values): ?array
    {
        if (! is_array($values) || empty($values)) {
            return $values;
        }

        $fkMap = $this->getEnrichableForeignKeys();
        if (empty($fkMap)) {
            return $values;
        }

        foreach ($fkMap as $fkColumn => $meta) {
            // Audit-grade: emit FK + label even if the model didn't explicitly
            // set the column. `Model::create([...])` only stamps attributes you
            // passed, so a nullable FK left out of the create-array is absent
            // from `getAttributes()` even though the DB column holds null.
            // Without this branch, seeder/tinker rows show no provenance at
            // all for unset FKs — readers can't tell "deliberately blank" from
            // "column doesn't exist on the model".
            if (! array_key_exists($fkColumn, $values)) {
                $values[$fkColumn] = null;
                $values[$fkColumn.'_label'] = null;

                continue;
            }
            if (array_key_exists($fkColumn.'_label', $values)) {
                continue; // already enriched
            }
            $rawId = $values[$fkColumn];
            if ($rawId === null || $rawId === '') {
                $values[$fkColumn.'_label'] = null;

                continue;
            }
            try {
                /** @var class-string<Model> $relatedClass */
                $relatedClass = $meta['related'];
                $query = $relatedClass::query();
                if (in_array(SoftDeletes::class, class_uses_recursive($relatedClass), true)) {
                    $query->withTrashed();
                }
                $related = $query->find($rawId);
                if ($related) {
                    // `getNameField()` is `protected` on most models; use reflection
                    // so we can read the configured name column without forcing
                    // every model to make the method public.
                    $nameField = 'name';
                    if (method_exists($related, 'getNameField')) {
                        try {
                            $refl = new \ReflectionMethod($related, 'getNameField');
                            $refl->setAccessible(true);
                            $candidate = $refl->invoke($related);
                            if (is_string($candidate) && $candidate !== '') {
                                $nameField = $candidate;
                            }
                        } catch (\Throwable $e) {
                            // fall back to 'name'
                        }
                    }
                    $label = $related->{$nameField} ?? null;
                    if ($label === null || $label === '') {
                        $label = '#'.$related->getKey();
                    }
                    $values[$fkColumn.'_label'] = $label;
                }
            } catch (\Throwable $e) {
                // Best-effort — don't fail the audit log over enrichment.
            }
        }

        // Second pass: morphTo polymorphic relations. The model has both a
        // *_type column (storing class/alias) and a *_id column. We resolve
        // the class via the type column, then look up the id, same as BelongsTo.
        $morphMap = $this->getEnrichableMorphTos();
        foreach ($morphMap as $cfg) {
            $idCol = $cfg['id_column'];
            $typeCol = $cfg['type_column'];

            if (! array_key_exists($idCol, $values) || ! array_key_exists($typeCol, $values)) {
                continue;
            }
            if (array_key_exists($idCol.'_label', $values)) {
                continue;
            }
            $rawId = $values[$idCol];
            $rawType = $values[$typeCol];
            if ($rawId === null || $rawId === '' || $rawType === null || $rawType === '') {
                $values[$idCol.'_label'] = null;

                continue;
            }
            try {
                $morphClass = Relation::getMorphedModel((string) $rawType)
                    ?? $rawType;
                if (! is_string($morphClass) || ! class_exists($morphClass)) {
                    continue;
                }
                $query = $morphClass::query();
                if (in_array(SoftDeletes::class, class_uses_recursive($morphClass), true)) {
                    $query->withTrashed();
                }
                $related = $query->find($rawId);
                if ($related) {
                    $nameField = 'name';
                    if (method_exists($related, 'getNameField')) {
                        try {
                            $refl = new \ReflectionMethod($related, 'getNameField');
                            $refl->setAccessible(true);
                            $candidate = $refl->invoke($related);
                            if (is_string($candidate) && $candidate !== '') {
                                $nameField = $candidate;
                            }
                        } catch (\Throwable $e) {
                        }
                    }
                    $label = $related->{$nameField} ?? null;
                    if ($label === null || $label === '') {
                        $label = '#'.$related->getKey();
                    }
                    $values[$idCol.'_label'] = $label;
                    // Surface the resolved class basename so auditors see what kind
                    // of entity the polymorphic reference resolved to.
                    $values[$typeCol.'_label'] = class_basename($morphClass);
                }
            } catch (\Throwable $e) {
                // Best-effort.
            }
        }

        return $values;
    }

    /**
     * Per-class cache of polymorphic morphTo relations.
     */
    private static array $enrichableMorphCache = [];

    /**
     * Detect morphTo relations declared on this model.
     *
     * @return array<int, array{id_column:string, type_column:string, method:string}>
     */
    private function getEnrichableMorphTos(): array
    {
        $class = static::class;
        if (array_key_exists($class, self::$enrichableMorphCache)) {
            return self::$enrichableMorphCache[$class];
        }

        $map = [];
        $morphFqcn = MorphTo::class;

        try {
            $reflection = new \ReflectionClass($this);
            $fileSourceCache = [];

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $declaringClass = $method->getDeclaringClass()->getName();
                if (! str_starts_with($declaringClass, 'Modules\\') && ! str_starts_with($declaringClass, 'App\\')) {
                    continue;
                }
                if ($method->getNumberOfRequiredParameters() > 0
                    || $method->isStatic() || $method->isAbstract()
                    || $method->isConstructor() || $method->isDestructor()) {
                    continue;
                }
                $rt = $method->getReturnType();
                $isTyped = $rt instanceof \ReflectionNamedType && $rt->getName() === $morphFqcn;

                $looksLikeMorph = $isTyped;
                if (! $looksLikeMorph) {
                    $f = $method->getFileName();
                    if ($f && is_readable($f)) {
                        if (! isset($fileSourceCache[$f])) {
                            $fileSourceCache[$f] = @file($f) ?: [];
                        }
                        $body = implode('', array_slice(
                            $fileSourceCache[$f],
                            (int) $method->getStartLine() - 1,
                            (int) $method->getEndLine() - (int) $method->getStartLine() + 1
                        ));
                        if (str_contains($body, 'morphTo(') || str_contains($body, '->morphTo(')) {
                            $looksLikeMorph = true;
                        }
                    }
                }
                if (! $looksLikeMorph) {
                    continue;
                }

                try {
                    $relation = $method->invoke($this);
                    if (! $relation instanceof MorphTo) {
                        continue;
                    }
                    $map[] = [
                        'id_column' => $relation->getForeignKeyName(),
                        'type_column' => $relation->getMorphType(),
                        'method' => $method->getName(),
                    ];
                } catch (\Throwable $e) {
                    // skip
                }
            }
        } catch (\Throwable $e) {
            // fall through with empty map
        }

        self::$enrichableMorphCache[$class] = $map;

        return $map;
    }

    /**
     * Build (and cache) the map of enrichable foreign-key columns for this
     * model class. Detection: any public method that returns a typed
     * `BelongsTo` relation. Skip-list excludes audit-only FKs.
     *
     * @return array<string, array{related:class-string<Model>, method:string}>
     */
    private function getEnrichableForeignKeys(): array
    {
        $class = static::class;
        if (array_key_exists($class, self::$enrichableFkCache)) {
            return self::$enrichableFkCache[$class];
        }

        $fkMap = [];
        $belongsToFqcn = BelongsTo::class;

        try {
            $reflection = new \ReflectionClass($this);
            // Cache file contents per file path so source-parsing is cheap.
            $fileSourceCache = [];

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                // Only methods declared in app code (not framework parents)
                $declaringClass = $method->getDeclaringClass()->getName();
                if (! str_starts_with($declaringClass, 'Modules\\') && ! str_starts_with($declaringClass, 'App\\')) {
                    continue;
                }
                if ($method->getNumberOfRequiredParameters() > 0) {
                    continue;
                }
                if ($method->isStatic() || $method->isAbstract() || $method->isConstructor() || $method->isDestructor()) {
                    continue;
                }

                // Decide whether to invoke based on either:
                //   (a) typed BelongsTo return type (declared intent), or
                //   (b) source body contains `belongsTo(` (matches Eloquent relation idiom)
                // Methods failing both are skipped — invoking arbitrary public methods
                // on a model can have side effects (Artisan calls, subprocesses, etc.).
                $returnType = $method->getReturnType();
                $isTypedBelongsTo = $returnType instanceof \ReflectionNamedType
                    && $returnType->getName() === $belongsToFqcn;

                $looksLikeRelation = $isTypedBelongsTo;
                if (! $looksLikeRelation) {
                    $file = $method->getFileName();
                    if ($file && is_readable($file)) {
                        if (! isset($fileSourceCache[$file])) {
                            $fileSourceCache[$file] = @file($file) ?: [];
                        }
                        $lines = $fileSourceCache[$file];
                        $start = (int) $method->getStartLine();
                        $end = (int) $method->getEndLine();
                        if ($start > 0 && $end >= $start) {
                            $body = implode('', array_slice($lines, $start - 1, $end - $start + 1));
                            if (str_contains($body, 'belongsTo(') || str_contains($body, '->belongsTo(')) {
                                $looksLikeRelation = true;
                            }
                        }
                    }
                }
                if (! $looksLikeRelation) {
                    continue;
                }

                try {
                    $relation = $method->invoke($this);
                    if (! $relation instanceof BelongsTo) {
                        continue;
                    }
                    $fkColumn = $relation->getForeignKeyName();
                    if (in_array($fkColumn, self::$skipFkColumns, true)) {
                        continue;
                    }
                    if (! isset($fkMap[$fkColumn])) {
                        $fkMap[$fkColumn] = [
                            'related' => get_class($relation->getRelated()),
                            'method' => $method->getName(),
                            'typed' => $isTypedBelongsTo,
                        ];
                    }
                } catch (\Throwable $e) {
                    // Method threw — skip silently.
                }
            }
        } catch (\Throwable $e) {
            // Fall through with empty map
        }

        self::$enrichableFkCache[$class] = $fkMap;

        return $fkMap;
    }
}
