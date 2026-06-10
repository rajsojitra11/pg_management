<?php

namespace Modules\User\Models;

use App\Traits\HasActivityLogging;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate as ImpersonateTrait;
use Laravel\Sanctum\HasApiTokens;
use Modules\User\app\Listeners\LogUserAuthentication;
use Modules\User\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasActivityLogging, HasApiTokens, HasFactory, HasPublicId, HasRoles, ImpersonateTrait, Notifiable, SoftDeletes;

    public function canImpersonate(): bool
    {
        return $this->hasRole('Super_Admin');
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('Super_Admin');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'name_prefix',
        'email',
        'password',
        'mobile',
        'username',
        'menu_style',
        'theme',
        'status',
        'manager_id',
        'head_id',
        'is_blocked',
        // Set by the installer when creating role users with the shared default
        // password — login flow forces a reset on first attempt while it's set.
        'force_password_change_at',
        // REQUIRED: User tracking fields
        'created_by',     // User who created the record
        'updated_by',     // User who last updated the record
        'deleted_by',     // User who deleted the record
    ];

    /**
     * Fields to ignore when checking for meaningful changes in activity logging
     * These fields won't trigger update logs when they change
     */
    protected array $ignoredFieldsForLogging = [
        'login_attempts',
        'last_login_at',
        'last_activity_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the current guard type (api or web)
     *
     * @return string
     */
    protected static function getGuardType()
    {
        // Check if the current guard is 'api' or 'web'
        if (auth()->guard('web')->check()) {
            return 'web';
        }

        // Default to 'web' if not API
        return 'api';
    }

    /**
     * User profile relationship
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /**
     * Get the user who created the user
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the user
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the user
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Direct manager assigned during user create/edit. Used by AccessGate
     * for record-level edit / delete overrides.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Department/functional head assigned during user create/edit. Provides
     * a second-tier override (e.g. when manager is unavailable).
     */
    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    // ── Org hierarchy (user_hierarchies table) ──────────────────────────
    // The hierarchy is many-to-many: a user can have multiple parents
    // (matrixed reporting). Helpers below treat any parent in the chain
    // as a valid manager for permission/edit-override purposes.

    public function hierarchyEntries()
    {
        return $this->hasMany(UserHierarchy::class, 'user_id');
    }

    public function subordinateLinks()
    {
        return $this->hasMany(UserHierarchy::class, 'parent_id');
    }

    /**
     * Direct managers (parent_ids in user_hierarchies for this user).
     */
    public function managers()
    {
        return $this->belongsToMany(
            User::class,
            'user_hierarchies',
            'user_id',
            'parent_id'
        )->whereNull('user_hierarchies.deleted_at');
    }

    /**
     * Direct subordinates (users who have THIS user as a parent_id).
     */
    public function subordinates()
    {
        return $this->belongsToMany(
            User::class,
            'user_hierarchies',
            'parent_id',
            'user_id'
        )->whereNull('user_hierarchies.deleted_at');
    }

    /**
     * All ancestor user ids (immediate manager + manager's manager + …).
     * Walks the user_hierarchies chain breadth-first; cycle-safe.
     */
    public function getAllManagerIds(): array
    {
        $visited = [];
        $queue = [$this->id];

        while (! empty($queue)) {
            $batch = $queue;
            $queue = [];
            $parents = UserHierarchy::whereIn('user_id', $batch)
                ->whereNull('deleted_at')
                ->pluck('parent_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
            foreach ($parents as $pid) {
                if (! in_array($pid, $visited, true) && $pid !== $this->id) {
                    $visited[] = $pid;
                    $queue[] = $pid;
                }
            }
        }

        return $visited;
    }

    /**
     * All descendant user ids (immediate children + their children + …).
     * Walks the user_hierarchies table breadth-first; cycle-safe.
     */
    public function getAllSubordinateIds(): array
    {
        $visited = [];
        $queue = [$this->id];

        while (! empty($queue)) {
            $batch = $queue;
            $queue = [];
            $children = UserHierarchy::whereIn('parent_id', $batch)
                ->whereNull('deleted_at')
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
            foreach ($children as $cid) {
                if (! in_array($cid, $visited, true) && $cid !== $this->id) {
                    $visited[] = $cid;
                    $queue[] = $cid;
                }
            }
        }

        return $visited;
    }

    /**
     * True when $this is anywhere up the hierarchy chain above $userId.
     * Use this for "manager-can-override" gates — e.g. Item edit override.
     */
    public function isAncestorOf(int $userId): bool
    {
        if ($userId === $this->id) {
            return false;
        }
        $target = static::find($userId);
        if (! $target) {
            return false;
        }

        return in_array($this->id, $target->getAllManagerIds(), true);
    }

    /**
     * Override logging configuration for User model
     */
    protected function getLoggingConfig(): array
    {
        return [
            'log_model' => UserActivityLog::class,
            'foreign_key' => 'user_id_acting_on', // User changes target this user
            'name_field' => 'name',
            'model_name' => 'User',
            'system_remarks' => [
                'created' => __('user::message.system_user_created', ['name' => '{name}']),
                'updated' => __('user::message.system_user_updated', ['name' => '{name}']),
                'deleted' => __('user::message.system_user_deleted', ['name' => '{name}']),
                'restored' => __('user::message.system_user_restored', ['name' => '{name}']),
                'activated' => __('user::message.system_user_activated', ['name' => '{name}']),
                'deactivated' => __('user::message.system_user_deactivated', ['name' => '{name}']),
                'blocked' => __('user::message.system_user_blocked', ['name' => '{name}']),
                'unblocked' => __('user::message.system_user_unblocked', ['name' => '{name}']),
                'password_changed' => __('user::message.system_password_changed', ['name' => '{name}']),
            ],
        ];
    }

    /**
     * Override the name field for logging
     */
    protected function getNameField(): string
    {
        return 'name';
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(function ($user) {
            // Check if password was changed
            if ($user->isDirty('password')) {
                LogUserAuthentication::logPasswordChange(
                    $user->id,
                    'Password updated by user'
                );
            }
        });
    }
}
