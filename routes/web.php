<?php

use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/css/erp-config.css', function () {
    $css = view('css.erp-config')->render();

    return response($css)
        ->header('Content-Type', 'text/css')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('css.config');

Route::get('/db-info', function () {
    return response()->json([
        'domain' => request()->getHost(),
        'database_name' => DB::connection()->getDatabaseName(),
        'connection_name' => config('database.default'),
        'host' => config('database.connections.' . config('database.default') . '.host'),
    ]);
});
Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    echo 'Application cache has been cleared';

    return redirect()->back();
});

// Session management API routes
Route::middleware(['auth'])->group(function () {
    // ── Dropdown lookup endpoints (AJAX-driven typeahead) ──────────────
    // Return JSON [{value, label}] capped at limit param (max 50).
    Route::get('lookup/countries', [LookupController::class, 'countries'])->name('lookup.countries');
    Route::get('lookup/states', [LookupController::class, 'states'])->name('lookup.states');
    Route::get('lookup/cities', [LookupController::class, 'cities'])->name('lookup.cities');
    Route::get('lookup/currencies', [LookupController::class, 'currencies'])->name('lookup.currencies');
    Route::get('lookup/units', [LookupController::class, 'units'])->name('lookup.units');
    Route::get('lookup/years', [LookupController::class, 'years'])->name('lookup.years');
    Route::get('lookup/active-users', [LookupController::class, 'activeUsers'])->name('lookup.active-users');
    Route::get('lookup/pg-list', [LookupController::class, 'pgList'])->name('lookup.pg-list');
    Route::get('lookup/rooms-by-pg', [LookupController::class, 'roomsByPg'])->name('lookup.rooms-by-pg');


    // Impersonation routes
    Route::impersonate();
    Route::get('/api/impersonate/users', [ImpersonateController::class, 'users'])
        ->middleware('role:Super_Admin')
        ->name('impersonate.users');

    // Enhanced session management endpoints
    Route::post('/api/session/extend', [SessionController::class, 'extend'])->name('api.session.extend');
    Route::get('/api/session/status', [SessionController::class, 'status'])->name('api.session.status');
    Route::post('/api/session/heartbeat', [SessionController::class, 'heartbeat'])->name('api.session.heartbeat');
    Route::get('/api/session/warning', [SessionController::class, 'checkWarning'])->name('api.session.warning');
    Route::get('/api/session/config', [SessionController::class, 'config'])->name('api.session.config');

    // Legacy session status check for backward compatibility
    Route::get('/api/session-check', function () {
        if (Auth::check()) {
            return response()->json([
                'authenticated' => true,
                'user_id' => Auth::id(),
                'session_lifetime' => config('session.lifetime', 120),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return response()->json(['authenticated' => false], 401);
    })->name('api.session.check');
});

Route::redirect('/', '/login');
require __DIR__ . '/auth.php';
