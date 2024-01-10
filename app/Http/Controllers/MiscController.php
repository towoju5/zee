<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MiscController extends Controller
{
    public function verifyOtp(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return get_error_response(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->input('email'))->first();
        if ($user && $user->verification_otp == $request->input('otp')) {
            $user->update(['is_verified' => true, 'verification_otp' => null]);

            return get_success_response(['message' => 'OTP verified successfully'], 200);
        }

        return get_error_response(['error' => 'Invalid OTP'], 422);
    }

}
