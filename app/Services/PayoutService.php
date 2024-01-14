<?php

namespace App\Services;

use App\Models\Gateways;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\BinancePay\app\Http\Controllers\BinancePayController;
use Modules\CoinPayments\app\Http\Controllers\CoinPaymentsController;
use Modules\Flutterwave\app\Http\Controllers\FlutterwaveController;
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
    public function makePayment($quoteId, $gateway) : bool|string
    {
        try {
            $paymentMethods = Gateways::whereStatus(SELF::ACTIVE)->get();
            foreach ($paymentMethods as $methods) {
                if($gateway == $methods->slug && gateways($methods->slug) == true) {
                    $model = strtolower($methods->slug);
                    return self::$model($quoteId);
                }
            }
            return false;
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function binance_pay($quoteId)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init($quoteId);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function advcash($quoteId)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->withdrawal(request());
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function flutterwave($quoteId)
    {
        try {
            $flutterwave = new FlutterwaveController();
            $init = $flutterwave->makePayment($quoteId);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function monnify($quoteId)
    {
        try {
            $monnify = new MonnifyController();
            $init = $monnify->createCheckout($quoteId);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function coinpayment($quoteId) : object | array
    {
        $coinpayment = new CoinPaymentsController();
        $checkout = $coinpayment->makePayment($quoteId);
        return $checkout;
    }

    public function paypal($quoteId) : object|array
    {
        $paypal = new PayoutController();
        $checkout = $paypal->init($quoteId);
        return $checkout;
    }
}

