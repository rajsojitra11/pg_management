<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccessType
{
    public function handle(Request $request, Closure $next, string $type)
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $roles = $user->roles;

        foreach ($roles as $role) {
            if ($type === 'web' && $role->isWebAccessible()) {
                return $next($request);
            }
            if ($type === 'mobile' && $role->isMobileAccessible()) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden: no access to this application.'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => 'Your account does not have access to the web application.']);
    }
}
