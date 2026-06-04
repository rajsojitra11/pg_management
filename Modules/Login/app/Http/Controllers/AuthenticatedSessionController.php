<?php

namespace Modules\Login\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Login\Http\Requests\LoginRequest;
use Modules\Login\Http\Requests\LogoutRequest;
use Modules\Year\Models\Year;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // $databaseName = DB::connection()->getDatabaseName();
        // dd("Current database name is: " . $databaseName);
        // exit;

        return view('login::login');
        // return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Always set session year to DB default on login
        // Auto-update DB default if FY has changed
        $currentFY = getCurrentFiscalYear();
        $defaultYear = Year::where('set_default', 1)->first();

        if ($defaultYear && $defaultYear->name !== $currentFY) {
            $currentFYYear = Year::where('name', $currentFY)->first();
            if ($currentFYYear) {
                Year::query()->update(['set_default' => 0]);
                Year::where('id', $currentFYYear->id)->update(['set_default' => 1]);
                $defaultYear = $currentFYYear;
            }
        }

        if ($defaultYear) {
            // Check role-based year access and fallback to nearest allowed year
            $allowedIds = getUserAllowedYearIds();
            if (is_array($allowedIds) && ! in_array($defaultYear->id, $allowedIds)) {
                $allowedYear = Year::whereIn('id', $allowedIds)->orderBy('full', 'desc')->first();
                if ($allowedYear) {
                    $defaultYear = $allowedYear;
                }
            }

            session([
                'year_id' => $defaultYear->id,
                'year' => $defaultYear->name,
            ]);
            session()->save();
        }

        // Clear expired parameter if present to prevent persistent session expired message
        if (request()->has('expired')) {
            return redirect()->route('dashboard');
        }

        // return redirect()->intended(RouteServiceProvider::HOME);
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(LogoutRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $request->merge(['_skip_logout_event_log' => true]);
        }

        $responseData = [
            'success' => __('user::message.success.logged_out'),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            $jsonResponse = response()->json([
                'success' => true,
                'message' => $responseData['success'],
                'redirect' => route('login'),
            ], 200);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $jsonResponse;
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with($responseData);
    }
}
