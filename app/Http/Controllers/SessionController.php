<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SessionExtensionMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Extend the current session
     */
    public function extend(Request $request): JsonResponse
    {
        $result = SessionExtensionMiddleware::extendSession($request);

        return response()->json($result, $result['success'] ? 200 : 401);
    }

    /**
     * Get current session status
     */
    public function status(): JsonResponse
    {
        $status = SessionExtensionMiddleware::getSessionStatus();

        return response()->json($status);
    }

    /**
     * Heartbeat endpoint for session activity tracking
     */
    public function heartbeat(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['authenticated' => false], 401);
        }

        // Update last activity timestamp
        $currentTime = time();
        $sessionMeta = session('session_meta', []);

        session([
            'session_meta' => array_merge($sessionMeta, [
                'last_activity' => $currentTime,
                'heartbeat_count' => ($sessionMeta['heartbeat_count'] ?? 0) + 1,
            ]),
        ]);

        // Get updated status
        $status = SessionExtensionMiddleware::getSessionStatus();

        return response()->json(array_merge($status, [
            'heartbeat' => true,
            'timestamp' => $currentTime,
        ]));
    }

    /**
     * Check if session is about to expire and needs warning
     */
    public function checkWarning(): JsonResponse
    {
        $status = SessionExtensionMiddleware::getSessionStatus();

        if (! $status['authenticated']) {
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json([
            'needs_warning' => $status['is_warning'],
            'time_remaining' => $status['time_remaining'],
            'time_remaining_minutes' => $status['time_remaining_minutes'],
            'is_expired' => $status['is_expired'],
        ]);
    }

    /**
     * Get session configuration for JavaScript
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'lifetime' => config('session.lifetime', 120) * 60, // in seconds
            'warning_time' => 300, // 5 minutes
            'heartbeat_interval' => 60, // 1 minute
            'auto_extend_threshold' => 600, // 10 minutes
            'driver' => config('session.driver'),
            'authenticated' => Auth::check(),
        ]);
    }
}
