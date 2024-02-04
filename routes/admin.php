<?php

use App\Http\Controllers\Admin\AuthController;
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


Route::get('panel', function(){
    return to_route('laratrust.roles.index');
});


Route::group([], function(){
    Route::get('login', [AuthController::class, 'showAdminLoginForm'])->name('login');
    Route::post('process-login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('admin')->group(function() {
    //
});
