<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use App\Notifications\WithdrawalNotification;
use Illuminate\Http\Request;
use Modules\Beneficiary\app\Models\Beneficiary;
use Modules\Monnet\app\Services\MonnetServices;

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
                'gateway' => 'required',
                'currency' => 'required',
            ]);

            $is_beneficiary = Beneficiary::where(['user_id' => active_user(), 'id' => $request->beneficiary_id])->count();
            if ($is_beneficiary < 1) {
                return get_error_response(['error' => "Invalid beneficiary"]);
            }
            $valitdate['user_id'] = auth()->id();
            $valitdate['raw_data'] = $request->all();

            if ($create = Withdraw::create($valitdate)) {
                $monnet = new MonnetServices();
                $beneficiaryId = $request->beneficiary_id;

                $beneficiary = Beneficiary::whereId($beneficiaryId)
                                ->whereUserId(auth()->id())->first();
                if (!$beneficiary) {
                    return get_error_response(['error' => "Beneficiary not found"]);
                }

                $checkout = $monnet->payout(
                    $request->amount,
                    $request->currency,
                    $beneficiaryId
                );
                if ($checkout) {
                    user()->notify(new WithdrawalNotification($create));
                    return get_success_response($checkout);
                }
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function getWithdrawalStatus($payoutsId)
    {
        try {
            $monnet = new MonnetServices();
            return $monnet->payoutStatus($payoutsId);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
