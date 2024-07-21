<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Lotes;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Expensas;
use App\Http\Controllers\Api\Servicios;
use App\Http\Controllers\Api\FormControl;
use App\Http\Controllers\Api\Solicitudes;
use App\Http\Controllers\Api\Authentication;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


 
Route::post('/sanctum/login',[Authentication::class,'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// middleware(['token_validate'])->
Route::middleware('auth:sanctum')->prefix('form_control')->group(function () {
    Route::post('store',[FormControl::class,'store']);
    Route::post('index',[FormControl::class,'index']);
});

Route::middleware('auth:sanctum')->prefix('solicitudes')->group(function () {
    Route::post('store',[Solicitudes::class,'store']);
    Route::get('index',[Solicitudes::class,'index']);
});

Route::middleware('auth:sanctum')->prefix('expensas')->group(function () {
    // Route::post('store',[Expensas::class,'store']);
    Route::get('index',[Expensas::class,'index']);
});

Route::middleware('auth:sanctum')->prefix('lotes')->group(function () {
    // Route::post('store',[Expensas::class,'store']);
    Route::get('index',[Lotes::class,'index']);
});

Route::middleware('auth:sanctum')->prefix('servicios')->group(function () {
    Route::get('combox',[Servicios::class,'combox']);
    Route::get('index',[Servicios::class,'index']);
});
