<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\Google2faController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\MiscController;
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

Route::group(['prefix'  => 'v1/locations'], function(){
    Route::get('countries',         [MiscController::class, 'countries'])->name('countries');
    Route::get('states',            [MiscController::class, 'states'])->name('states');
    Route::get('states/{countryId}',[MiscController::class, 'states'])->name('state');
    Route::get('cities/{stateId}',  [MiscController::class, 'city'])->name('cities');
});

Route::group(['prefix'  => 'v1/auth'], function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/social', [AuthController::class, 'socialLogin']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('send-verification-otp', [AuthController::class, 'sendVerificationOtp']);
    Route::post('verify-otp', [MiscController::class, 'verifyOtp']);

    // magic authentication routes
    Route::post('login/magic',                  [MagicLinkController::class, 'sendMagicLink']);
    Route::post('login/magic-login',            [MagicLinkController::class ,'loginWithMagicLink']);
    // Route::post('register/magic',               [MagicLinkController::class, 'sendMagicCode']);
    Route::post('register/complete/{token}',    [MagicLinkController::class ,'completeRegistration']);

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password-with-otp', [AuthController::class, 'resetPasswordWithOtp']);
});


Route::middleware(['auth:api'])->prefix('v1')->name('api.')->group(function () {

    Route::group(['middleware' => 'google2fa'], function () {
        Route::post('generate-2fa-secret', [Google2faController::class, 'generateSecret']);
        Route::post('enable-2fa', [Google2faController::class, 'enable2fa']);
        Route::post('verify-2fa', [Google2faController::class, 'verify2fa']);
        Route::post('disable-2fa', [Google2faController::class, 'disable2fa']);
    });
    
    Route::get('auth/refresh-token', [AuthController::class, 'refresh']);

    Route::put('profile', [AuthController::class, 'update']);
    Route::get('profile', [AuthController::class, 'profile']);

    Route::get('user-meta', [UserMetaController::class, 'index']);
    Route::get('user-meta/{id}', [UserMetaController::class, 'show']);
    Route::post('user-meta', [UserMetaController::class, 'store']);
    Route::put('user-meta/{id}', [UserMetaController::class, 'update']);
    Route::delete('user-meta/{id}', [UserMetaController::class, 'destroy']);


    Route::group(['prefix' =>  'wallet'], function () {
        Route::group(['prefix' =>  'deposits'], function () {
            Route::get('/',      [DepositController::class, 'index']);
            Route::post('new',      [DepositController::class, 'store']);
        });

        Route::post('zeenah-transfer', [WalletController::class, 'zeenahTransfer']);
        Route::get('withdrawals',   [WalletController::class, 'withdrawals']);
        Route::get('balance',       [WalletController::class, 'balance']);
    });
});