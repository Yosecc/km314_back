<?php

use App\Http\Controllers\Api\PushDeviceController;
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

// Route::get('/', function () {
//     return view('welcome');
// });

// Facebook routes - deshabilitadas temporalmente
// Route::group(['prefix' => 'auth/facebook', 'middleware' => 'auth'], function () {
//     Route::get('/', [\App\Http\Controllers\SocialController::class, 'redirectToProvider'])->name('auth.facebook');
//     Route::get('/callback', [\App\Http\Controllers\SocialController::class, 'handleProviderCallback']);
// });
// Route::get('webhook/facebook_webhook', [\App\Http\Controllers\SocialController::class, 'facebook_webhook']);
// Route::post('webhook/facebook_webhook', [\App\Http\Controllers\SocialController::class, 'facebook_webhook_post']);
Route::get('/factura/pdf/{id}', [\App\Http\Controllers\InvoicePdfController::class, 'show'])->name('factura.pdf');
Route::get('/factura/preview/{key}', [\App\Http\Controllers\InvoicePdfController::class, 'preview'])->name('invoice.preview');
Route::get('/terminos-y-condiciones', [\App\Http\Controllers\HomeController::class, 'getTerminosCondicionesFormControl'])->name('terminos-y-condiciones');
Route::get('/quick-access/{code}', [\App\Http\Controllers\QuickAccessController::class, 'index'])->name('quick-access');
Route::get('/package-receptions/files/{file}', \App\Http\Controllers\PackageReceptionFileController::class)
    ->middleware('auth')
    ->name('package-receptions.files.download');
Route::get('/formulario-publico/{token}', [\App\Http\Controllers\PublicFormControlController::class, 'show'])
    ->middleware('throttle:30,1')->name('form-control-public.show');
Route::post('/formulario-publico/{token}', [\App\Http\Controllers\PublicFormControlController::class, 'store'])
    ->middleware('throttle:10,1')->name('form-control-public.store');

Route::get('/firebase-messaging-sw.js', function () {
    $firebase = config('firebase.web');
    abort_unless(filled($firebase['api_key'] ?? null), 404);

    return response()
        ->view('firebase-messaging-service-worker', compact('firebase'))
        ->header('Content-Type', 'application/javascript; charset=UTF-8')
        ->header('Service-Worker-Allowed', '/')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
});

Route::middleware('auth')->post('/push/devices/web', [PushDeviceController::class, 'store'])
    ->name('push.devices.web');
