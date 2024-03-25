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

/**
 * This class is responsible for generating 
 * deposit/checkout link for customers to make payment.
 * 
 * @category Wallet_Top_Up
 * @package  Null
 * @author   Emmanuel A Towoju <towojuads@gmail.com>
 * @license  MIT www.yativo.com/license
 * @link     www.yativo.com
 */


class DepositService
{
    const ACTIVE = true;
    /**
     * check if gateway is active, 
     * then make request to gateway and 
     * return payment url or charge status for wallet
     */
    public function makeDeposit(string $gateway, $currency, $amount, $send)
    {
        try {
            $user = user();
            $paymentMethods = Gateways::whereStatus(SELF::ACTIVE)->get();
            foreach ($paymentMethods as $methods) {
                if ($gateway == $methods->slug && gateways($methods->slug) == true) {
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

    public function binance_pay($deposit_id, $amount, $currency)
    {
        try {
            $binance = new BinancePayController();
            $init = $binance->init(null, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function advcash($deposit_id, $amount, $currency)
    {
        // try {
        //     $advcash = new AdvcashController();
        //     $init = $advcash->init(null, $amount, $currency);
        //     return $init;
        // } catch (\Throwable $th) {
        //     return ['error' => $th->getMessage()];
        // }
    }

    public function flutterwave($deposit_id, $amount, $currency)
    {
        try {
            $flutterwave = new FlutterwaveController();
            $init = $flutterwave->makePayment(null, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function monnify($deposit_id, $amount, $currency)
    {
        try {
            $monnify = new MonnifyController();
            $init = $monnify->createCheckout(null, $amount, $currency);
            return $init;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function coinpayment($deposit_id, $amount, $currency) : object | array
    {
        $coinpayment = new CoinPaymentsController();
        $checkout = $coinpayment->makePayment($deposit_id, $amount, $currency);
        return $checkout;
    }

    public function paypal($deposit_id, $amount, $currency) : string
    {
        $paypal = new PayPalDepositController();
        $checkout = $paypal->createOrder(null, $amount, $currency);
        return $checkout;
    }

    public function monnet($deposit_id, $amount, $currency)
    {
        if(!in_array($currency, ["COP", "PEN", "USD", "CLP", "ARS", "MXN"])) {
            return ["error" => "Unknown currency selected"];
        }
        $monnet = new MonnetServices();
        $checkout = $monnet->payin($deposit_id, $amount, $currency, 'DEPOSIT');
        return $checkout;
    }

    public function flow($deposit_id, $amount, $currency)
    {
        if(!in_array($currency, ["CLP"])) {
            return ["error" => "Unknown currency selected"];
        }
        $flow = new FlowController();
        $checkout = $flow->makePayment(null, $amount, $currency);
        return $checkout;
    }
}

