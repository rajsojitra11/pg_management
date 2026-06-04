<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionExtensionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process for authenticated users
        if (Auth::check()) {
            $this->addSessionMetadata($request);
        }

        $response = $next($request);

        // Add session timing headers for JavaScript
        if (Auth::check()) {
            $this->addSessionHeaders($response);
        }

        return $response;
    }

    /**
     * Add session metadata for tracking
     */
    protected function addSessionMetadata(Request $request): void
    {
        $sessionLifetime = config('session.lifetime', 120) * 60; // Convert to seconds
        $currentTime = time();

        // Store session timing data
        session([
            'session_meta' => [
                'started_at' => session('session_meta.started_at', $currentTime),
                'last_activity' => $currentTime,
                'lifetime_seconds' => $sessionLifetime,
                'expires_at' => $currentTime + $sessionLifetime,
                'user_id' => Auth::id(),
                'extended_count' => session('session_meta.extended_count', 0),
            ],
        ]);
    }

    /**
     * Add session timing headers for JavaScript consumption
     */
    protected function addSessionHeaders(Response $response): void
    {
        $sessionMeta = session('session_meta', []);

        if (! empty($sessionMeta)) {
            $response->headers->set('X-Session-Expires-At', $sessionMeta['expires_at'] ?? 0);
            $response->headers->set('X-Session-Lifetime', $sessionMeta['lifetime_seconds'] ?? 0);
            $response->headers->set('X-Session-Warning-Time', 300); // 5 minutes warning
            $response->headers->set('X-Session-Current-Time', time());
        }
    }

    /**
     * Extend session lifetime
     */
    public static function extendSession(Request $request): array
    {
        if (! Auth::check()) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        try {
            $sessionLifetime = config('session.lifetime', 120) * 60;
            $currentTime = time();
            $newExpiryTime = $currentTime + $sessionLifetime;

            // Update session metadata
            $currentMeta = session('session_meta', []);
            $extendedCount = ($currentMeta['extended_count'] ?? 0) + 1;

            session([
                'session_meta' => array_merge($currentMeta, [
                    'last_activity' => $currentTime,
                    'expires_at' => $newExpiryTime,
                    'extended_count' => $extendedCount,
                    'last_extended_at' => $currentTime,
                ]),
            ]);

            // Touch the session to update last_activity in database
            $request->session()->save();

            return [
                'success' => true,
                'message' => 'Session extended successfully',
                'new_expiry' => $newExpiryTime,
                'current_time' => $currentTime,
                'extended_count' => $extendedCount,
            ];
        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Failed to extend session',
            ];
        }
    }

    /**
     * Get current session status
     */
    public static function getSessionStatus(): array
    {
        if (! Auth::check()) {
            return ['authenticated' => false];
        }

        $sessionMeta = session('session_meta', []);
        $currentTime = time();

        $expiresAt = $sessionMeta['expires_at'] ?? 0;
        $timeRemaining = max(0, $expiresAt - $currentTime);
        $warningTime = 300; // 5 minutes

        return [
            'authenticated' => true,
            'current_time' => $currentTime,
            'expires_at' => $expiresAt,
            'time_remaining' => $timeRemaining,
            'time_remaining_minutes' => round($timeRemaining / 60, 1),
            'is_warning' => $timeRemaining <= $warningTime && $timeRemaining > 0,
            'is_expired' => $timeRemaining <= 0,
            'warning_threshold' => $warningTime,
            'extended_count' => $sessionMeta['extended_count'] ?? 0,
            'user_id' => Auth::id(),
        ];
    }
}
