<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

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
        if ($token === false) {
            return response()->json(['error' => 'Unauthorized'], 401);
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
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'businessName' => 'required',
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

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        // Create a new user
        $user = User::create([
            'name' => $request->input('name'),
            'bussinessName' => $request->input('businessName'),
            'id_number' => $request->input('idNumber'),
            'id_type' => $request->input('idType'),
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'phone_number' => $request->input('phoneNumber'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'country' => $request->input('country'),
            'zip_code' => $request->input('zipCode'),
            'street' => $request->input('street'),
            'additional_info' => $request->input('additionalInfo'),
            'house_number' => $request->input('houseNumber'),
            'verification_document' => save_image('customer-documents', $request->input('verificationDocument')) ?? null,
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        // You can customize the response as needed
        return get_success_response(['message' => 'User registered successfully', 'user' => $user], 201);
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

    public function update(ProfileRequest $request, User $user)
    {
        $user->update([
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
            "verificationDocument"  =>  $request->verificationDocument,
        ]);

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        // You can customize the response as needed
        return get_success_response(['message' => 'Profile updated successfully', 'user' => $user]);
    }
}
