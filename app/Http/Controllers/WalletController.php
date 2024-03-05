<?php

namespace App\Http\Controllers;

use App\Jobs\Transaction as JobsTransaction;
use App\Models\Balance;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
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
            $deposits = Deposit::whereUserId(active_user())->with('transaction')->paginate(per_page());
            return get_success_response($deposits);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Moved to withdrawal controller
     */
    // public function withdrawals()
    // {
    //     try {
    //         $payouts = Withdraw::whereUserId(active_user())->with('transaction')->paginate(per_page());
    //         return get_success_response($payouts);
    //     } catch (\Throwable $th) {
    //         return get_error_response(['error' => $th->getMessage()]);
    //     }
    // }

    public function zeenahTransfer(Request $request)
    {
        try {
            $validate = $request->validate([
                'email' => 'required',
                'amount' => 'required',
                'currency' => 'required',
            ]);

            $user = $request->user();
            if ($request->email == $user->email) {
                return get_error_response(['error' => "Sorry you can't transfer to yourself"]);
            }

            $receiver = User::whereEmail($request->email)->first();
            if (!$receiver) {
                return get_error_response(['error' => 'Invalid receiver provided']);
            }

            // check sender balance
            $sender_balance = Balance::whereUserId($user->id)->where('currency_symbol', $request->currency)->first();
            if ($sender_balance < $request->amount) {
                return get_error_response(['error' => 'Insufficient wallet balance']);
            }
            $receiver_balance = Balance::whereUserId($user->id)->where('currency_symbol', $request->currency)->first();
            $receiver_balance->balance = floatval($receiver_balance->balance + $request->amount);
            $receiver_balance->save();

            $validate['sender_id'] = $user->id;
            $validate['receiver_id'] = $receiver->id;
            $validate['user_id'] = $user->id;
            Withdraw::create($validate);
            JobsTransaction::dispatch($validate, 'zeenah_transfer');
            $payouts = Deposit::whereUserId(active_user())->with('transaction')->get();
            return get_success_response($payouts);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
