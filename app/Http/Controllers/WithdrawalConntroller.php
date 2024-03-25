<?php

namespace App\Http\Controllers;

use App\Models\Gateways;
use App\Models\Withdraw;
use App\Notifications\WithdrawalNotification;
use App\Services\PayoutService;
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
            $validate = $request->validate([
                'beneficiary_id' => 'required',
                'amount' => 'required'
            ]);

            $is_beneficiary = Beneficiary::where(['user_id' => active_user(), 'id' => $request->beneficiary_id])->first();
            if (!$is_beneficiary) {
                return get_error_response(['error' => "Beneficiary not found"]);
            }
        
            $gateway = Gateways::where([
                // 'payout' => true,
                'slug' => $is_beneficiary->mode
            ])->whereJsonContains('payout_currencies', $is_beneficiary->currency)->first();

            if(!$gateway) {
                return get_error_response(['error' => "The choosen withdrawal method is invalid or currently unavailable"]);
			}
            $validate['user_id'] = auth()->id();
            $validate['raw_data'] = $request->all();
            $validate['gateway'] = $is_beneficiary->mode;
            $validate['currency'] = $is_beneficiary->currency;
            $create = Withdraw::create($validate);
            if ($create) {
                $payout = new PayoutService();
                $checkout = $payout->makePayment($create->id, $is_beneficiary->mode);
                $create->raw_data = $checkout;
                $create->save();
                user()->notify(new WithdrawalNotification($create));
                return get_success_response($checkout);
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
