<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance()
    {
        try {
            $balances = Balance::whereUserId(active_user())->get();
            return get_success_response($balances);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function deposits()
    {
        try {
            $deposits = Deposit::whereUserId(active_user())->with('transaction')->get();
            return get_success_response($deposits);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function withdrawals()
    {
        try {
            $payouts = Deposit::whereUserId(active_user())->with('transaction')->get();
            return get_success_response($payouts);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function zeenahTransfer(Request $request)
    {
        try {
            $validate = $request->validate([
                'email' => 'required',
                'amount' => 'required',
                'currency' => 'required',
            ]);

            // check sender balance
            $user = $request->user();
            $sender_balance = Balance::whereUserId($user->id)->where('currency', $request->currency)->first();
            if ($sender_balance < $request->amount) {
                return get_error_response(['error' => 'Insufficient wallet balance']);
            }
            $receiver = User::whereEmail($request->email)->first();
            if (!$receiver) {
                return get_error_response(['error' => 'Invalid receiver provided']);
            }
            
            $receiver_balance = Balance::whereUserId($user->id)->where('currency', $request->currency)->first();
            $receiver_balance->balance = floatval($receiver_balance->balance + $request->amount);
            $receiver_balance->save();

            $validate['sender_id'] = $user->id;
            $validate['receiver_id'] = $receiver->id;
            $validate['user_id'] = $user->id;
            Transaction::create($validate);
            $payouts = Deposit::whereUserId(active_user())->with('transaction')->get();
            return get_success_response($payouts);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
