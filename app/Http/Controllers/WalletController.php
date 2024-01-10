<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Deposit;
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
}
