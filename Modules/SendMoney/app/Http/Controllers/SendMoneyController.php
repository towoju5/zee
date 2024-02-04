<?php

namespace Modules\SendMoney\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\Transaction;
use App\Models\Gateways;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Modules\SendMoney\app\Models\SendMoney;
use Modules\SendMoney\app\Models\SendQuote;
use Modules\SendMoney\app\Notifications\SendMoneyNotification;
use Modules\SendMoney\app\Notifications\SendMoneyQuoteNotification;

class SendMoneyController extends Controller
{
    public function get_quotes()
    {
        try {
			$quotes = SendQuote::whereUserId(active_user())->with('details')->latest()->paginate(10);
			return get_success_response($quotes);
		} catch (\Throwable $th) {
			get_error_response(['error'  => $th->getMessage()]);
		}
    }
    public function get_quote($id)
    {
        try {
			$quotes = SendQuote::whereUserId(active_user())->whereId($id)->first();
			return get_success_response($quotes);
		} catch (\Throwable $th) {
			get_error_response(['error'  => $th->getMessage()]);
		}
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
				$gateways = Gateways::where('payout', true)->whereJsonContains('supported_currencies', $request->currency)->get();
			}
	
			if($request->action == 'pay_by') {
				$gateways = Gateways::where('deposit', true)->whereJsonContains('supported_currencies', $request->currency)->get();
			}
			return get_success_response($gateways);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	}

	public function create_quote(Request $request)
	{
		try {
			$validate  = $request->validate([
				'action' 			=> 	'required',
				'send_amount' 		=> 	'required',
				'receive_amount' 	=> 	'required',
				'send_gateway'		=>	'required',
				'receive_gateway'	=>	'required',
				'send_currency'		=>	'required',
				'receive_currency' 	=>	'required',
				'beneficiary_id' 	=>	'required',
				'transfer_purpose' 	=>	'sometimes',
			]);

			$check_send_gateway = self::method_exists($request->send_gateway, $request->send_currency, 'deposit');
			$check_receive_gateway = self::method_exists($request->receive_gateway, $request->receive_currency, 'payout');

			if($check_send_gateway < 1 || $check_receive_gateway < 1) {
				return get_error_response(['error' => 'Unknown gateway or unsupported currency selected']);
			}

			$user = User::find(active_user());
			$validate['rate'] = null;
			$validate['user_id'] = active_user();
			$validate['total_amount'] = null;
			$validate['raw_data'] = $request->all();

			// calulate quote fees
			if(SendQuote::get()->count() < 1) {
				$validate['id'] = '2111';
			}

			// return $validate;

			if($send = SendQuote::create($validate)){
				// add transaction history
				// @dispatch(new SendMoneyQuoteNotification($send, 'send_money'));
				// @$user->notify(new SendMoneyNotification($send));
				return get_success_response($send);
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

			$user = User::find(active_user());
			$get_quote = SendQuote::whereId($request->quote_id)->first();
			if($get_quote){
				// $validate['user_id'] = active_user();
				$validate['status'] = 'pending';
				if($send = SendMoney::create($validate)){
					// add transaction history
					// dispatch(new Transaction($send, 'send_money'));
					// $user->notify(new SendMoneyNotification($send));
					$paymentUrl = (new PaymentService())->makePayment($send, $get_quote->send_gateway);
					if(is_array($paymentUrl) && isset($paymentUrl['error']) && $paymentUrl['error'] != "ok") {
						return get_error_response(['error' => $paymentUrl['error']]);
					}
					return get_success_response(['link' => $paymentUrl, 'quote' => $get_quote]);
				}  
				return get_error_response(['error' => 'Unable to process send request please contact support']);
			}
			return get_error_response(['error' => 'Transaction quote not found or expired']);
		} catch (\Throwable $th) {
			get_error_response(['error' => $th->getMessage()]);
		}
	} 

	public function complete_send_money($quoteId)
	{
		try {
			$send_money = SendMoney::whereQuoteId($quoteId)->first();
			$send_money->status = 'successful';
			$send_money->save();

			$quote = SendQuote::whereId($quoteId)->first();
			$quote->status = 'successful';
			$quote->save();

			// Inititate payout proccess
			$payout  = new PayoutService();
			$init_payout = $payout->makePayment($quoteId, $quote->receive_gateway);
		} catch (\Throwable $th) {
			//throw $th;
		}
	}

	/**
	 * method = payment gateway
	 * mode ['deposit', 'payout']
	 */
	public function method_exists($method, $currency, $mode)
	{
		$where = [
			'slug' => $method,
			$mode => true
		];
		$gate = Gateways::where($where)->whereJsonContains('supported_currencies', $currency)->count();
		return $gate;
	}
}  

