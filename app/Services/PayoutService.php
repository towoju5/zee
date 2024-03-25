<?php

namespace App\Services;

use App\Models\Gateways;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Support\Facades\Log;
use Modules\BinancePay\app\Http\Controllers\BinancePayController;
use Modules\CoinPayments\app\Http\Controllers\CoinPaymentsController;
use Modules\Flutterwave\app\Http\Controllers\FlutterwaveController;
use Modules\Monnet\app\Services\MonnetServices;
use Modules\Monnify\app\Http\Controllers\MonnifyController;
use Modules\PayPal\app\Http\Controllers\PayoutController;
use Modules\PayPal\app\Http\Controllers\PayPalDepositController;
use Modules\PayPal\app\Providers\PayPalServiceProvider;

/**
 * This class is responsible for generating 
 * deposit/checkout link for customers to make payment.
 */
class PayoutService
{
    const ACTIVE = true;
    
    /**
     * check if gateway is active, 
     * then make request to gateway and 
     * return payment url or charge status for wallet
     */
    public function makePayment($quoteId, $gateway) 
    {
        try {
            $withdrawal = Withdraw::where("id", $quoteId)->first();
            $paymentMethods = Gateways::whereStatus(SELF::ACTIVE)->get();
            foreach ($paymentMethods as $methods) {
                if($gateway == $methods->slug && gateways($methods->slug) == true) {
                    $model = strtolower($methods->slug);
                    return self::$model($quoteId, $withdrawal->currency);
                }
            }
            return false;
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function binance_pay($quoteId, $currency=null)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init($quoteId, $currency=null);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function advcash($quoteId, $currency=null)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->withdrawal(request());
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function flutterwave($quoteId, $currency=null)
    {
        try {
            $flutterwave = new FlutterwaveController();
            $init = $flutterwave->makePayment($quoteId, $currency=null);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function monnify($quoteId, $currency=null)
    {
        try {
            $monnify = new MonnifyController();
            $init = $monnify->createCheckout($quoteId, $currency=null);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function coinpayment($quoteId, $currency=null) : object | array
    {
        $coinpayment = new CoinPaymentsController();
        $checkout = $coinpayment->makePayment($quoteId, $currency=null);
        return $checkout;
    }

    public function paypal($quoteId, $currency=null) : object|array
    {
        $paypal = new PayoutController();
        $checkout = $paypal->init($quoteId, $currency=null);
        return $checkout;
    }

    public function monnet($quoteId, $currency=null) : object|array
    {
        $request = request();
        $monnet = new MonnetServices();
        $beneficiaryId = $request->beneficiary_id;
        $checkout = $monnet->payout(
            $request->amount,
            $currency,
            $beneficiaryId
        );

        if (!isset($checkout['errors'])) {
            return $checkout;
        }
        return ['error' => $checkout["errors"]];
    }
}

