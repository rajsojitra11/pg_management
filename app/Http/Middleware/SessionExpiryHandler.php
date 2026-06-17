<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Modules\User\Listeners\LogUserAuthentication;
use Modules\User\Models\User;
use Modules\User\Models\UserActivityLog;
use Symfony\Component\HttpFoundation\Response;

class SessionExpiryHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Store authentication state before request
        $wasAuthenticated = Auth::check();
        $userId = $wasAuthenticated ? Auth::id() : null;

        $response = $next($request);

        // Check if user was authenticated but is no longer after request processing
        if ($wasAuthenticated && ! Auth::check() && $userId) {
            // Only log automatic logout if this is NOT a manual logout request
            // Manual logouts are handled by the Logout event listener
            $isManualLogout = $request->is('logout') ||
                $request->routeIs('logout') ||
                str_contains($request->getPathInfo(), '/logout') ||
                ($request->method() === 'POST' && str_contains(strtolower($request->getRequestUri()), 'logout')) ||
                $request->has('_skip_logout_event_log');

            if (! $isManualLogout) {
                $this->logAutomaticLogout($userId, 'session_expired');

                // Add header to indicate session expired for frontend detection
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Session expired',
                        'authenticated' => false,
                        'action' => 'redirect',
                        'redirect_url' => route('login').'?expired=1',
                    ], 419)->header('X-Session-Expired', 'true');
                } else {
                    // For regular requests, redirect to login with session expired message
                    return redirect()->route('login')->with('status', 'Your session has expired. Please login again.');
                }
            }
        }

        return $response;
    }

    /**
     * Log automatic logout with appropriate timestamp
     */
    private function logAutomaticLogout(int $userId, string $reason): void
    {
        try {
            $effectiveTimestamp = now();

            // Get user information
            $user = User::find($userId);
            $agent = class_exists(Agent::class) ? new Agent : null;

            // Create logout log entry
            $userLog = new UserActivityLog([
                'user_id' => $userId,
                'user_id_acting_on' => $userId,
                'activity' => 'logout',
                'user_remark' => __('user::message.user_remark_logout_automatic'),
                'system_remark' => __('user::message.system_user_logout_automatic', [
                    'reason' => $reason,
                    'name' => $user ? $user->name : 'Unknown User',
                ]),
                'ip_address' => clientIp(),
                'location' => getLocationFromIp(clientIp()),
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
        } catch (\Exception $e) {

            // Fallback to the original method if our enhanced logging fails
            try {
                LogUserAuthentication::logAutomaticLogout($userId, $reason);
            } catch (\Exception $fallbackError) {
                Log::critical('Fallback automatic logout logging also failed', [
                    'user_id' => $userId,
                    'original_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage(),
                ]);
            }
        }
    }
}
