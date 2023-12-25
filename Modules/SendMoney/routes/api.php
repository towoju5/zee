<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\SendMoney\app\Http\Controllers\SendMoneyController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1/sendmoney')->name('api.')->as('sendmoney.')->group(function () {
    Route::get('sendmoney', fn (Request $request) => $request->user())->name('sendmoney');
    Route::get('gateways',  [SendMoneyController::class, 'gateways'])->name('gateways');
    Route::post('/',        [SendMoneyController::class, 'send_money'])->name('process');
});
