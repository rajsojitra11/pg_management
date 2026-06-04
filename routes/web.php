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
        'host' => config('database.connections.'.config('database.default').'.host'),
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
    Route::get('lookup/clients', [LookupController::class, 'clients'])->name('lookup.clients');
    Route::get('lookup/papers', [LookupController::class, 'papers'])->name('lookup.papers');
    Route::get('lookup/paper-finishes', [LookupController::class, 'paperFinishes'])->name('lookup.paper-finishes');
    Route::get('lookup/paper-gsms', [LookupController::class, 'paperGsms'])->name('lookup.paper-gsms');
    Route::get('lookup/sheet-sizes', [LookupController::class, 'sheetSizes'])->name('lookup.sheet-sizes');
    Route::get('lookup/machines', [LookupController::class, 'machines'])->name('lookup.machines');
    Route::get('lookup/vendors', [LookupController::class, 'vendors'])->name('lookup.vendors');
    Route::get('lookup/job-sizes', [LookupController::class, 'jobSizes'])->name('lookup.job-sizes');
    Route::get('lookup/printing-formats', [LookupController::class, 'printingFormats'])->name('lookup.printing-formats');
    Route::get('lookup/plate-details', [LookupController::class, 'plateDetails'])->name('lookup.plate-details');
    Route::get('lookup/paper-coatings', [LookupController::class, 'paperCoatings'])->name('lookup.paper-coatings');
    Route::get('lookup/printings', [LookupController::class, 'printings'])->name('lookup.printings');
    Route::get('lookup/post-press', [LookupController::class, 'postPress'])->name('lookup.post-press');
    Route::get('lookup/post-press-categories', [LookupController::class, 'postPressCategories'])->name('lookup.post-press-categories');

    // Job-card prefill lookups (used by PrintingJobDetail, PlateDetailForm, LaminationOrder, UvOrder)
    Route::get('lookup/order-forms', [LookupController::class, 'orderForms'])->name('lookup.order-forms');
    Route::get('lookup/order-forms/{id}/printing-job-prefill', [LookupController::class, 'orderFormPrintingJobPrefill'])
        ->whereNumber('id')->name('lookup.order-forms.printing-job-prefill');
    Route::get('lookup/order-forms/{id}/plate-detail-prefill', [LookupController::class, 'orderFormPlateDetailPrefill'])
        ->whereNumber('id')->name('lookup.order-forms.plate-detail-prefill');
    Route::get('lookup/order-forms/{id}/lamination-prefill', [LookupController::class, 'orderFormLaminationPrefill'])
        ->whereNumber('id')->name('lookup.order-forms.lamination-prefill');
    Route::get('lookup/order-forms/{id}/uv-prefill', [LookupController::class, 'orderFormUvPrefill'])
        ->whereNumber('id')->name('lookup.order-forms.uv-prefill');

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
require __DIR__.'/auth.php';
