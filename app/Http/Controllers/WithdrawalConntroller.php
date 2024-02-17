<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use App\Notifications\WithdrawalNotification;
use Illuminate\Http\Request;
use Modules\Beneficiary\app\Models\Beneficiary;

class WithdrawalConntroller extends Controller
{
    public function index()
    {
        try {
            $payouts = Withdraw::where('user_id', auth()->id())->with('beneficiary')->paginate(per_page());
            return get_success_response($payouts);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $valitdate = $request->validate([
                'beneficiary_id' => 'required',
                'amount' => 'required',
            ]);

            $is_beneficiary = Beneficiary::where(['user_id' => auth()->id(), 'id' => $request->beneficiary_id])->count();
            if($is_beneficiary < 1) {
                return get_error_response(['error'  => "Invalid beneficiary"]);
            }

            $valitdate['user_id'] = auth()->id();
            $valitdate['data'] = $request->all();

            if($create = Withdraw::create($valitdate)) {
                user()->notify(new WithdrawalNotification($create));
                return get_success_response($create);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
