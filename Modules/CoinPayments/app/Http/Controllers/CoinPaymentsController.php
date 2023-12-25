<?php

namespace Modules\CoinPayments\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\CoinPayments\app\Services\CoinpaymentServices;

class CoinPaymentsController extends Controller
{
    public $coinpayments;

    public function __construct()
    {
        $this->coinpayments = new CoinpaymentServices();
    }

    public function makePayment($amount, $currency1='USD')
    {
        $request = request();
        $currency2 = $request->crypto;
        $buyer_email = $request->user()->email;
        return $this->coinpayments->CreateTransactionSimple($amount, $currency1, $currency2, $buyer_email);
    }

    public function validatePayment($transactionId)
    {
        return $this->coinpayments->status($transactionId);
    }
}
