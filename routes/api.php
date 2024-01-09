<?php

use App\Http\Controllers\UserMetaController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:sanctum')->prefix('v1')->group([], function() {
    Route::get('user-meta', [UserMetaController::class, 'index']);
    Route::get('user-meta/{id}', [UserMetaController::class, 'show']);
    Route::post('user-meta', [UserMetaController::class, 'store']);
    Route::put('user-meta/{id}', [UserMetaController::class, 'update']);
    Route::delete('user-meta/{id}', [UserMetaController::class, 'destroy']);
});