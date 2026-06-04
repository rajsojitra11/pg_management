<?php

namespace Modules\User\app\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Modules\User\Models\User;
use Modules\User\Models\UserActivityLog;

class LogUserAuthentication
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [$this, 'handleLogin']);
        $events->listen(Logout::class, [$this, 'handleLogout']);
        $events->listen(Failed::class, [$this, 'handleFailedLogin']);
    }

    public function handleLogin(Login $event): void
    {
        // Skip logging for seeder context
        if (static::isSeederContext()) {
            return;
        }

        // Cast to User model to access properties
        /** @var User $user */
        $user = $event->user;

        $agent = class_exists(Agent::class) ? new Agent : null;
        $effectiveTimestamp = now();

        // Log to user_logs table for authentication tracking
        $userLog = new UserActivityLog([
            'user_id' => $user->id,
            'user_id_acting_on' => $user->id,
            'activity' => 'login',
            'user_remark' => __('user::message.user_remark_login'),
            'system_remark' => __('user::message.system_user_login', ['name' => $user->name]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => null,
            'created_by' => $user->id,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    public function handleLogout(Logout $event): void
    {
        // Skip logging for seeder context
        if (static::isSeederContext()) {
            return;
        }

        // Skip if enhanced logging already handled in controller (prevents duplicate entries)
        if (request()->has('_skip_logout_event_log')) {
            return;
        }

        // Cast to User model to access properties
        /** @var User $user */
        $user = $event->user;

        $agent = class_exists(Agent::class) ? new Agent : null;
        $effectiveTimestamp = now();

        // Log to user_logs table
        $userLog = new UserActivityLog([
            'user_id' => $user->id,
            'user_id_acting_on' => $user->id,
            'activity' => 'logout',
            'user_remark' => __('user::message.user_remark_logout'),
            'system_remark' => __('user::message.system_user_logout', ['name' => $user->name]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => 'logout',
            'created_by' => $user->id,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    public function handleFailedLogin(Failed $event): void
    {
        // Skip logging for seeder context
        if (static::isSeederContext()) {
            return;
        }

        $agent = class_exists(Agent::class) ? new Agent : null;
        $effectiveTimestamp = now();
        $userId = null;
        $userName = 'Unknown';
        $identifier = 'Unknown';

        // Try to get user info if credentials contain email/username
        if (isset($event->credentials['email'])) {
            $identifier = $event->credentials['email'];
            $user = User::where('email', $event->credentials['email'])->first();
            if ($user) {
                $userId = $user->id;
                $userName = $user->name;
            }
        } elseif (isset($event->credentials['username'])) {
            $identifier = $event->credentials['username'];
            $user = User::where('username', $event->credentials['username'])->first();
            if ($user) {
                $userId = $user->id;
                $userName = $user->name;
            }
        }

        // Log failed login attempt to user_logs table
        $userLog = new UserActivityLog([
            'user_id' => $userId ?: 1, // Use system user if no user found
            'user_id_acting_on' => $userId ?: 1,
            'activity' => 'login_failed',
            'user_remark' => __('user::message.user_remark_login_failed'),
            'system_remark' => __('user::message.system_user_login_failed', ['identifier' => $identifier]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => false,
            'logout_reason' => null,
            'created_by' => $userId ?: 1,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    /**
     * Handle automatic logout (session timeout)
     */
    public static function logAutomaticLogout(int $userId, string $reason = 'session_timeout'): void
    {
        $agent = class_exists(Agent::class) ? new Agent : null;
        $user = User::find($userId);

        $effectiveTimestamp = now();

        $userLog = new UserActivityLog([
            'user_id' => $userId,
            'user_id_acting_on' => $userId,
            'activity' => 'logout',
            'user_remark' => __('user::message.user_remark_logout_automatic'),
            'system_remark' => __('user::message.system_user_logout_automatic', [
                'reason' => $reason,
                'name' => $user ? $user->name : 'Unknown User',
            ]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => $reason,
            'created_by' => $userId,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    /**
     * Handle password change logging
     */
    public static function logPasswordChange(int $userId, ?string $userRemark = null): void
    {
        $agent = class_exists(Agent::class) ? new Agent : null;
        $user = User::find($userId);

        $effectiveTimestamp = now();

        $userLog = new UserActivityLog([
            'user_id' => $userId,
            'user_id_acting_on' => $userId,
            'activity' => 'password_changed',
            'user_remark' => $userRemark ?: __('user::message.user_remark_password_changed'),
            'system_remark' => __('user::message.system_password_changed', [
                'name' => $user ? $user->name : 'Unknown User',
            ]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => null,
            'created_by' => $userId,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    /**
     * Handle user blocking logging
     */
    public static function logUserBlocked(int $userId, ?string $userRemark = null): void
    {
        $agent = class_exists(Agent::class) ? new Agent : null;
        $user = User::find($userId);

        $effectiveTimestamp = now();

        $userLog = new UserActivityLog([
            'user_id' => $userId,
            'user_id_acting_on' => $userId,
            'activity' => 'blocked',
            'user_remark' => $userRemark ?: __('user::message.user_remark_blocked'),
            'system_remark' => __('user::message.system_user_blocked', [
                'name' => $user ? $user->name : 'Unknown User',
            ]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => null,
            'created_by' => auth()->id() ?? 1,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    /**
     * Handle user unblocking logging
     */
    public static function logUserUnblocked(int $userId, ?string $userRemark = null): void
    {
        $agent = class_exists(Agent::class) ? new Agent : null;
        $user = User::find($userId);

        $effectiveTimestamp = now();

        $userLog = new UserActivityLog([
            'user_id' => $userId,
            'user_id_acting_on' => $userId,
            'activity' => 'unblocked',
            'user_remark' => $userRemark ?: __('user::message.user_remark_unblocked'),
            'system_remark' => __('user::message.system_user_unblocked', [
                'name' => $user ? $user->name : 'Unknown User',
            ]),
            'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
            'user_agent' => request()->header('User-Agent'),
            'device' => $agent ? $agent->device() : 'Unknown',
            'platform' => $agent ? $agent->platform() : 'Unknown',
            'browser' => $agent ? $agent->browser() : 'Unknown',
            'successful' => true,
            'logout_reason' => null,
            'created_by' => auth()->id() ?? 1,
        ]);

        $userLog->created_at = $effectiveTimestamp;
        $userLog->save();
    }

    /**
     * Handle user activation logging
     */
    public static function logUserActivated(int $userId, ?string $userRemark = null): void
    {
        try {

            $agent = class_exists(Agent::class) ? new Agent : null;
            $user = User::find($userId);

            if (! $user) {
                Log::error('LogUserActivated: User not found with ID: '.$userId);

                return;
            }

            $effectiveTimestamp = now();

            $userLog = new UserActivityLog([
                'user_id' => $userId,
                'user_id_acting_on' => $userId,
                'activity' => 'activated',
                'user_remark' => $userRemark ?: __('user::message.user_remark_activated'),
                'system_remark' => __('user::message.system_user_activated', [
                    'name' => $user->name,
                ]),
                'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
                'user_agent' => request()->header('User-Agent'),
                'device' => $agent ? $agent->device() : 'Unknown',
                'platform' => $agent ? $agent->platform() : 'Unknown',
                'browser' => $agent ? $agent->browser() : 'Unknown',
                'successful' => true,
                'logout_reason' => null,
                'created_by' => auth()->id() ?? 1,
            ]);

            $userLog->created_at = $effectiveTimestamp;
            $result = $userLog->save();

            if ($result) {
            } else {
                Log::error('LogUserActivated: Failed to save activity log');
            }
        } catch (Exception $e) {
            Log::error('LogUserActivated: Exception occurred: '.$e->getMessage());
            Log::error('LogUserActivated: Stack trace: '.$e->getTraceAsString());
            // Don't re-throw the exception to prevent breaking the main operation
        }
    }

    /**
     * Handle user deactivation logging
     */
    public static function logUserDeactivated(int $userId, ?string $userRemark = null): void
    {
        try {

            $agent = class_exists(Agent::class) ? new Agent : null;
            $user = User::find($userId);

            if (! $user) {
                Log::error('LogUserDeactivated: User not found with ID: '.$userId);

                return;
            }

            $effectiveTimestamp = now();

            $userLog = new UserActivityLog([
                'user_id' => $userId,
                'user_id_acting_on' => $userId,
                'activity' => 'deactivated',
                'user_remark' => $userRemark ?: __('user::message.user_remark_deactivated'),
                'system_remark' => __('user::message.system_user_deactivated', [
                    'name' => $user->name,
                ]),
                'ip_address' => request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                'location' => getLocationFromIp(request()->ip() ?: ($_SERVER['REMOTE_ADDR'] ?? null)),
                'user_agent' => request()->header('User-Agent'),
                'device' => $agent ? $agent->device() : 'Unknown',
                'platform' => $agent ? $agent->platform() : 'Unknown',
                'browser' => $agent ? $agent->browser() : 'Unknown',
                'successful' => true,
                'logout_reason' => null,
                'created_by' => auth()->id() ?? 1,
            ]);

            $userLog->created_at = $effectiveTimestamp;
            $result = $userLog->save();

            if ($result) {
            } else {
                Log::error('LogUserDeactivated: Failed to save activity log');
            }
        } catch (Exception $e) {
            Log::error('LogUserDeactivated: Exception occurred: '.$e->getMessage());
            Log::error('LogUserDeactivated: Stack trace: '.$e->getTraceAsString());
            // Don't re-throw the exception to prevent breaking the main operation
        }
    }

    /**
     * Static method to check if we're running in a seeder context.
     */
    public static function isSeederContext(): bool
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
}
