<?php

namespace App\Http\Controllers;

use App\Mail\MagicLinkEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class MagicLinkController extends Controller
{
    public function sendMagicLink(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
            ]);
    
            // if ($this->hasTooManyLoginAttempts($request)) {
            //     $this->fireLockoutEvent($request);
            //     return $this->sendLockoutResponse($request);
            // }
            // $this->incrementLoginAttempts($request);
    
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return get_error_response(['email' => 'We could not find a user with that email address.'], 401);
            }
            $token = rand(10111, 99999);
            $user->login_token = $token;
            $user->login_token_created_at = now();
            $user->save();
            $magicLink = url('/login/magic/' . $token);
            Mail::to($user)->send(new MagicLinkEmail($magicLink, $token));
            return get_success_response(['msg' => 'We have sent you a magic link. Please check your email.']);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function loginWithMagicLink(Request  $request)
    {
        $token = $request->token;
        $user = User::where('login_token', $token)
            ->where('login_token_created_at', '>=', now()->subMinutes(500))
            ->first();
        if (!$user) {
            return get_error_response(['error' => 'The magic link/code is invalid or has expired.']);
        }
        Auth::login($user);
        $user->login_token = null;
        $user->login_token_created_at = null;
        $user->save();
        $token = auth()->login($user);
        if ($token === false) {
            return get_error_response(['error' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return get_success_response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}
