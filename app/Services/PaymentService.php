<?php

namespace App\Services;

use App\Models\Gateways;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Advcash\app\Http\Controllers\AdvcashController;
use Modules\BinancePay\app\Http\Controllers\BinancePayController;
use Modules\CoinPayments\app\Http\Controllers\CoinPaymentsController;
use Modules\Flow\app\Http\Controllers\FlowController;
use Modules\Flow\app\Services\FlowServices;
use Modules\Flutterwave\app\Http\Controllers\FlutterwaveController;
use Modules\Monnet\app\Http\Controllers\MonnetController;
use Modules\Monnet\app\Services\MonnetServices;
use Modules\Monnify\app\Http\Controllers\MonnifyController;
use Modules\PayPal\app\Http\Controllers\PayPalDepositController;
use Modules\PayPal\app\Providers\PayPalServiceProvider;
use Modules\Pomelo\app\Http\Controllers\PomeloController;
use Modules\SendMoney\app\Models\SendMoney;
use Modules\SendMoney\app\Models\SendQuote;

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
    public function makePayment(SendMoney $send, $gateway)
    {
        try {
            $quote = SendQuote::find($send->quote_id);
            $amount = $quote->send_amount;
            $currency = $quote->send_currency;
            if($gateway == 'wallet') { 
                $user = User::find(active_user());
                if($user->hasWallet($currency)) {   
                    $walletBalance = $user->getWallet($currency);
                    if($walletBalance < $amount) {
                        if(getenv('APP_DEBUG') == true) {
                            Log::info("Insuficient balance for $user->id transaction amount  $amount on ".now());
                        }
                        return false;
                    }
                    return true;
                }
            }
            $paymentMethods = Gateways::whereStatus(SELF::ACTIVE)->get();
            foreach ($paymentMethods as $methods) {
                if($gateway == $methods->slug && gateways($methods->slug) == true) {
                    $model = strtolower($methods->slug);
                    return self::$model($send->id, $amount, $currency);
                }
            }
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function local_payment($amount, $currency, $gateway)
    {
        //
    }

    public function binance_pay($quoteId, $amount, $currency)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init($quoteId, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function advcash($quoteId, $amount, $currency)
    {
        // try {
        //     $advcash = new AdvcashController();
        //     $init = $advcash->init($quoteId, $amount, $currency);
        //     return $init;
        // } catch (\Throwable $th) {
        //     return ['error' => $th->getMessage()];
        // }
    }

    public function flutterwave($quoteId, $amount, $currency)
    {
        try {
            $flutterwave = new FlutterwaveController();
            $init = $flutterwave->makePayment($quoteId, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function monnify($quoteId, $amount, $currency)
    {
        try {
            $monnify = new MonnifyController();
            $init = $monnify->createCheckout($quoteId, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function coinpayment($quoteId, $amount, $currency) : object | array
    {
        $coinpayment = new CoinPaymentsController();
        $checkout = $coinpayment->makePayment($quoteId, $amount, $currency);
        return $checkout;
    }

    public function paypal($quoteId, $amount, $currency) : string
    {
        $paypal = new PayPalDepositController();
        $checkout = $paypal->createOrder($quoteId, $amount, $currency);
        return $checkout;
    }

    public function monnet($quoteId, $amount, $currency)
    {
        if(!in_array($currency, ["COP", "PEN", "USD", "CLP", "ARS", "MXN"])) {
            return ["error" => "Unknown currency selected"];
        }
        $monnet = new MonnetServices();
        $checkout = $monnet->payin($quoteId, $amount, $currency);
        return $checkout;
    }

    public function flow($quoteId, $amount, $currency)
    {
        if(!in_array($currency, ["CLP"])) {
            return ["error" => "Unknown currency selected"];
        }
        $flow = new FlowController();
        $checkout = $flow->makePayment($quoteId, $amount, $currency);
        return $checkout;
    }

    public function pomelo($quoteId, $amount, $currency)
    {
        if(!in_array($currency, ["CLP"])) {
            return ["error" => "Unknown currency selected"];
        }
        $flow = new PomeloController();
        $checkout = $flow->makePayment($quoteId, $amount, $currency);
        return $checkout;
    }

    public function transak($quoteId, $amount, $currency)
    {
        //
    }
}

