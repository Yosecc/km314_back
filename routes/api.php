<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FormControl;
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
    return $request->user()->with(['owner']);
});

// middleware(['token_validate'])->
Route::middleware('auth:sanctum')->prefix('form_control')->group(function () {

    Route::post('store',[FormControl::class,'store']);
    Route::post('index',[FormControl::class,'index']);


});
