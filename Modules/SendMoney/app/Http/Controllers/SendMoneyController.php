<?php

namespace Modules\SendMoney\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\Transaction;
use App\Models\Gateways;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\SendMoney\app\Models\SendMoney;
use Modules\SendMoney\app\Notifications\SendMoneyNotification;

class SendMoneyController extends Controller
{
    public function currencies()
    {
        //
    }

	public function gateways(Request $request)
	{
		/**
		 * @param currency : Currency the customer is sending in
		 * @param action : Which action is customer performing Ex: deliver_by(receiver received by) or pay_by(charge sender)
		 */
		try {
			$request->validate([
				'currency'	=>  'required',
				'action'	=> 'required'
			]);
	
			if($request->action == 'deliver_by') {
				$gateways = Gateways::where('payout', true)->get();
			}
	
			if($request->action == 'pay_by') {
				$gateways = Gateways::where('deposit', true)->get();
			}
			return get_success_response($gateways);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	}

	public function send_money(Request $request)
	{
		try {
			$validate  = $request->validate([
				'amount' 	=> 'required',
				'action' 	=> 'required',
				'gateway'	=>	'required',
				'send_currency'		=> 'require',
				'receive_currency' 	=> 'required',
				'transfer_purpose' 	=> 'required',
			]);

			$user = User::find(auth()->user()->currentTeam->id);
			$validate['rate'] = null;
			$validate['user_id'] = $user->id;
			$validate['total_amount'] = null;
			// $validate['total_amount'] = null;
			$validate['raw_data'] = $request->all();
			
			if($send = SendMoney::create($validate)){
				// add transaction history
				dispatch(new Transaction($send, 'send_money'));
				$user->notify(new SendMoneyNotification($send));
				return get_success_response($validate);
			}
			return get_error_response(['error' => 'Unable to process send request please contact support']);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	}
}

