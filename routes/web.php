<?php

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

Route::group(['prefix' => 'auth/facebook', 'middleware' => 'auth'], function () {
    Route::get('/', [\App\Http\Controllers\SocialController::class, 'redirectToProvider'])->name('auth.facebook');
    Route::get('/callback', [\App\Http\Controllers\SocialController::class, 'handleProviderCallback']);
});
Route::get('webhook/facebook_webhook', [\App\Http\Controllers\SocialController::class, 'facebook_webhook']);
Route::post('webhook/facebook_webhook', [\App\Http\Controllers\SocialController::class, 'facebook_webhook_post']);
Route::get('/factura/pdf/{id}', [\App\Http\Controllers\InvoicePdfController::class, 'show'])->name('factura.pdf');
Route::get('/factura/preview/{key}', [\App\Http\Controllers\InvoicePdfController::class, 'preview'])->name('invoice.preview');
