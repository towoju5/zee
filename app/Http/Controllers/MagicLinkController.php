<?php

namespace App\Http\Controllers;

use App\Mail\MagicLinkEmail;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Currencies\app\Models\Currency;
use Tymon\JWTAuth\Facades\JWTAuth;

class MagicLinkController extends Controller
{
    public function sendMagicLink(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
            ]);
            $success = [];
            $success['msg'] = 'We have sent you a One time login O.T.P, Please check your email.';
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                $user = new User();
                $user->email = $request->email;
            } else {
                $success['is_registered'] = true;
            }
            $token = rand(10111, 99999);
            $user->login_token = $token;
            $user->login_token_created_at = now();
            $user->save();
            $magicLink = url('/login/magic/' . $token);
            Mail::to($user)->send(new MagicLinkEmail($magicLink, $token));
            return get_success_response($success);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function loginWithMagicLink(Request $request)
    {
        $token = $request->token;
        $user = User::where('login_token', $token)
            ->where('login_token_created_at', '>=', now()->subMinutes(500))
            ->first();
        if (!$user) {
            return get_error_response(['error' => 'The One time login O.T.P, is invalid or has expired.']);
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

    public function completeRegistration(Request $request, $token)
    {
        try {
            $validator = $request->validate([
                'name' => 'required',
                'bussinessName' => 'required',
                'idNumber' => 'nullable|string',
                'idType' => 'nullable|string',
                'firstName' => 'nullable|string',
                'lastName' => 'nullable|string',
                'phoneNumber' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'country' => 'nullable|string',
                'zipCode' => 'nullable|string',
                'street' => 'nullable|string',
                'additionalInfo' => 'nullable|string',
                'houseNumber' => 'nullable|string',
                'verificationDocument' => 'nullable|string',
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

            $user = User::where('login_token', $token)->where('email', $request->email)
                ->where('login_token_created_at', '>=', now()->subMinutes(5))
                ->first();
            if (!$user) {
                return get_error_response(['error' => 'The One time login O.T.P, is invalid or has expired.']);
            }

            // Create a new user
            $validator['password'] = bcrypt(uuid());
            $validator['raw_data'] = $request->all();
            $userData = $user->update($validator);

            if ($user) {
                try {
                    $currencies = Currency::all();
                    foreach ($currencies as $k => $v) {
                        Balance::create([
                            "user_id" => $user->id,
                            "currency_name" => $v->currency_name,
                            "currency_code" => $v->wallet,
                            "main_balance" => $v->main_balance,
                            "ledger_balance" => $v->ledger_balance,
                            "currency_symbol" => $v->currency_icon,
                        ]);
                    }
                } catch (\Throwable $th) {
                    Log::error(json_encode(['error_creating_balance' => $th->getMessage()]));
                }
            }
            return get_success_response($user, 201);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
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
