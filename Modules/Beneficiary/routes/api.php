<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Beneficiary\app\Http\Controllers\BeneficiaryController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('beneficiary', fn (Request $request) => $request->user())->name('beneficiary');
    Route::get('beneficiaries', [BeneficiaryController::class, 'index']);
    Route::post('beneficiaries', [BeneficiaryController::class, 'store']);
    Route::get('beneficiaries/{id}', [BeneficiaryController::class, 'show']);
    Route::put('beneficiaries/{id}', [BeneficiaryController::class, 'update']);
    Route::delete('beneficiaries/{id}', [BeneficiaryController::class, 'destroy']);

});
