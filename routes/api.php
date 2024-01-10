<?php

use App\Http\Controllers\UserMetaController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:api'])->prefix('v1')->name('api.')->group(function () {
    Route::get('user-meta', [UserMetaController::class, 'index']);
    Route::get('user-meta/{id}', [UserMetaController::class, 'show']);
    Route::post('user-meta', [UserMetaController::class, 'store']);
    Route::put('user-meta/{id}', [UserMetaController::class, 'update']);
    Route::delete('user-meta/{id}', [UserMetaController::class, 'destroy']);


    Route::group(['prefix' =>  'wallet'], function () {
        Route::get('deposits',      [WalletController::class, 'deposits']);
        Route::get('withdrawals',   [WalletController::class, 'withdrawals']);
        Route::get('balance',       [WalletController::class, 'balance']);
    });
});