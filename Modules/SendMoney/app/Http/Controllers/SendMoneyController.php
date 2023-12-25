<?php

namespace Modules\SendMoney\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\Transaction;
use App\Models\Gateways;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\SendMoney\app\Models\SendMoney;
use Modules\SendMoney\app\Models\SendQuote;
use Modules\SendMoney\app\Notifications\SendMoneyNotification;
use Modules\SendMoney\app\Notifications\SendMoneyQuoteNotification;

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

	public function get_quote(Request $request)
	{
		try {
			$validate  = $request->validate([
				'action' 	=> 'required',
				'send_amount' 		=> 'required',
				'receive_amount' 	=> 'required',
				'send_gateway'		=>	'required',
				'receive_gateway'	=>	'required',
				'send_currency'		=>	'required',
				'receive_currency' 	=>	'required',
				'transfer_purpose' 	=>	'required',
			]);

			$user = User::find(auth()->user()->currentTeam->id);
			$validate['rate'] = null;
			$validate['user_id'] = active_user();
			$validate['total_amount'] = null;
			$validate['raw_data'] = $request->all();

			// calulate quote fees
			
			if($send = SendQuote::create($validate)){
				// add transaction history
				@dispatch(new SendMoneyQuoteNotification($send, 'send_money'));
				$user->notify(new SendMoneyNotification($send));
				return get_success_response($validate);
			}
			return get_error_response(['error' => 'Unable to process send request please contact support']);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	}

	public function send_money(Request $request)
	{
		try {
			$validate  = $request->validate([
				'quote_id' 	=> 'required'
			]);

			$user = User::find(auth()->user()->currentTeam->id);
			$get_quote = SendQuote::whereId($request->quote_id)->first();
			if($get_quote){
				if($send = SendMoney::create($validate)){
					// add transaction history
					dispatch(new Transaction($send, 'send_money'));
					$user->notify(new SendMoneyNotification($send));
					return get_success_response($validate);
				}
				return get_error_response(['error' => 'Unable to process send request please contact support']);
			}
			return get_error_response(['error' => 'Transaction quote not found or expired']);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	}
}

