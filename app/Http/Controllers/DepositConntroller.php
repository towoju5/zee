<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Notifications\DepositNotification;
use Illuminate\Http\Request;

class DepositConntroller extends Controller
{
    public function index()
    {
        try {
            $payouts = Deposit::where('user_id', auth()->id())->paginate(per_page());
            return get_success_response($payouts);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $valitdate = $request->validate([
                'gateway' => 'required',
                'amount' => 'required',
                'currency' => 'required',
            ]);

            $valitdate['user_id'] = auth()->id();
            $valitdate['meta'] = $request->all();

            if($create = Deposit::create($valitdate)) {
                user()->notify(new DepositNotification($create));
                return get_success_response($create);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
