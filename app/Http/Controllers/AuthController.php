<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Socialite\Facades\Socialite;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;
use Modules\Currencies\app\Models\Currency;

class AuthController extends Controller implements UpdatesUserProfileInformation
{
    public function login(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        $credentials = ['email' => $request->email, 'password' => $request->password];

        $token = auth()->attempt($credentials);

        $user = User::where('email', $request->email)->first();
        
        if ($token === false) {
            return get_error_response(['error' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return get_success_response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function register(Request $request)
    {
        // Validate the incoming request data
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
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        // Create a new user
        $validator['password'] = bcrypt($request->password);
        $validator['raw_data'] = $request->all();
        $user = User::create($validator);

        if($user) {
            try {
                $currencies = Currency::all();
                foreach($currencies as $k => $v) {
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

        // You can customize the response as needed
        return get_success_response($user, 201);
    }

    public function sendVerificationOtp(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        // Generate a random 6-digit OTP
        $otp = mt_rand(100001, 999999);

        // Save the OTP in the user's record
        $user = User::where('email', $request->input('email'))->first();
        $user->verification_otp = $otp;
        $user->save();

        sendOtpEmail($user->email, $otp);

        return get_success_response(['message' => 'OTP sent successfully'], 200);
    }

    public function forgotPassword(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        // Generate a random 6-digit OTP
        $otp = mt_rand(100000, 999999);

        // Save the OTP in the user's record (you might want to store it securely, depending on your application)
        $user = User::where('email', $request->input('email'))->first();
        $user->verification_otp = $otp;
        $user->save();

        // Send the OTP to the user's email
        sendOtpEmail($user->email, $otp);

        return get_success_response(['message' => 'OTP sent successfully'], 200);
    }

    public function resetPasswordWithOtp(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        // Retrieve the user by email
        $user = User::where('email', $request->input('email'))->first();
        if ($user && $user->verification_otp == $request->input('otp')) {
            // Reset the user's password
            $user->update(['password' => bcrypt($request->input('password')), 'verification_otp' => null]);

            return get_success_response(['message' => 'Password reset successfully'], 200);
        }

        return get_error_response(['error' => 'Invalid OTP'], 422);
    }

    public function profile(Request $request)
    {
        try {
            $profile = $request->user();
            return get_success_response($profile);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function update(ProfileRequest $request, $user = null)
    {
        $user = User::findorfail(active_user());
        $user->update(array_filter([
            "name"          =>  $request->name,
            "bussinessName" =>  $request->bussinessName,
            "firstName"     =>  $request->firstName,
            "lastName"      =>  $request->lastName,
            "phoneNumber"   =>  $request->phoneNumber,
            "city"          =>  $request->city,
            "state"         =>  $request->state,
            "country"       =>  $request->country,
            "zipCode"       =>  $request->zipCode,
            "street"        =>  $request->street,
            "additionalInfo"=>  $request->additionalInfo,
            "houseNumber"   =>  $request->houseNumber,
            "idNumber"      =>  $request->idNumber,
            "idType"        =>  $request->idType,
            "idIssuedAt"    =>  $request->idIssuedAt,
            "idExpiryDate"  =>  $request->idExpiryDate,
            "idIssueDate"   =>  $request->idIssueDate,
        ]));

        if($request->hasFile('verificationDocument')) {
            $user->update([
                'verificationDocument' => save_image("$request->name/document/", $request->verificationDocument)
            ]);
        }

        if ($request->has('photo')) {
            $user->updateProfilePhoto($request->photo);
        }

        // You can customize the response as needed
        return get_success_response(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    public function socialLogin(Request $request, $social=null)
    { 
        $user = User::updateOrCreate([
            'email' => $request->email,
        ], [
            'name' => $request->name,
            'email' => $request->email,
            'profile_photo_path' => $request->photo,
            'raw_data' => $request->all()
        ]);
    
        $token = Auth::login($user);
        if ($token === false) {
            return get_error_response(['error' => 'Unauthorized'], 401);
        }
        return $this->respondWithToken($token);
    }
}
