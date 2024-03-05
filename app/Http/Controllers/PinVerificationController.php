<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PinVerificationController extends Controller
{
    /**
     * Update or set new transaction pin for user
     * @param  string pin
     * @return json
     */
    public function updatePin(Request $request)
    {
        try {
            $request->validate([
                'pin' => 'required|max:4',
            ]);

            $user = User::find(auth()->id());
            $user->transaction_pin = bcrypt($request->pin);
            if ($user->save()) {
                return get_success_response(['msg' => "Transaction pin updated successfully"]);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Verify if stored pin == incoming pin
     * 
     * @param  string pin
     * @return json
     */
    public function verifyPin(Request $request)
    {
        try {
            $user = User::find(auth()->id());
            
            if (!password_verify($request->pin, $user->transaction_pin)) {
                return get_error_response(['error' => 'Invalid pin provided']);
            }
            return get_success_response(['msg' => "Transaction pin verified successfully"]);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
