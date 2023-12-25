<?php

namespace App\Services;

use App\Models\Gateways;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\BinancePay\app\Http\Controllers\BinancePayController;
use Modules\CoinPayments\app\Http\Controllers\CoinPaymentsController;
use Modules\Flutterwave\app\Http\Controllers\FlutterwaveController;
use Modules\Monnify\app\Http\Controllers\MonnifyController;

/**
 * This class is responsible for generating 
 * deposit/checkout link for customers to make payment.
 */
class PaymentService
{
    const ACTIVE = true;
    /**
     * check if gateway is active, 
     * then make request to gateway and 
     * return payment url or charge status for wallet
     */
    public function makePayment($amount, $currency, $gateway) : bool|string
    {
        try {
            if($gateway == 'wallet') { 
                $user = User::find(auth()->user()->currentTeam->id);
                if($user->hasWallet($currency)) {   
                    $walletBalance = $user->getWallet($currency);
                    if($walletBalance < $amount) {
                        if(getenv('APP_DEBUG') == true) {
                            Log::info("Insuficient balance for $user->id transaction amount  $amount on ".now());
                        }
                        return false;
                    }
                }
            }
            $paymentMethods = Gateways::whereStatus(SELF::ACTIVE)->get();
            foreach ($paymentMethods as $methods) {
                if($gateway == $methods->slug && gateways($methods->slug) == true) {
                    $model = strtolower($methods->slug);
                    return self::$model($amount, $currency);
                }
            }
            return false;
        } catch (\Throwable $th) {
            return get_error_response(['eerror' => $th->getMessage()]);
        }
    }

    public function binance_pay($amount, $currency)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init($amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function advcash($amount, $currency)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init($amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function flutterwave($amount, $currency)
    {
        try {
            $flutterwave = new FlutterwaveController();
            $init = $flutterwave->makePayment($amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function monnify($amount, $currency)
    {
        try {
            $monnify = new MonnifyController();
            $init = $monnify->createCheckout($amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function coinpayment($amount, $currency) : object | array
    {
        $coinpayment = new CoinPaymentsController();
        $checkout = $coinpayment->makePayment($amount, $currency);
        return $checkout;
    }
}

