<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Bitnob\app\Http\Controllers\BitnobController;

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

Route::middleware(['auth:api'])->prefix('v1')->name('api.')->group(function () {
    Route::get('bitnob', fn (Request $request) => $request->user())->name('bitnob');    
    Route::post('bitnob/reg-user',              [BitnobController::class, 'reg_user']);
    Route::post('bitnob/create-card',           [BitnobController::class, 'createCard']);
    Route::post('bitnob/topup-card/{cardId}',   [BitnobController::class, 'topupCard']);
    Route::get('bitnob/get-card/{cardId}',      [BitnobController::class, 'getCard']);
    Route::get('bitnob/transactions/{cardId}',  [BitnobController::class, 'transactions']);

    // supported actions are freeze and unfreeze
    Route::post('bitnob/freeze-unfreeze/{action}/{cardId}', [BitnobController::class, 'freeze_unfreeze']);
});
