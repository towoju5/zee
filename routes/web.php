<?php

use Illuminate\Support\Facades\Route;
use Modules\ShuftiPro\app\Http\Controllers\ShuftiProController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:api',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::group([], function(){
    Route::get('shufti-pro/callback/{user_id}', [ShuftiProController::class, 'webhook'])->name('shuftipro.webhook');
});


Route::fallback(function() {
    return get_error_response(['error' => 'Please check your request, we\'re unable to match your request.', 'generated-strings' => uuid()]);
});