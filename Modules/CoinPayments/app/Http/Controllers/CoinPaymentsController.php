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
        $apiKey = getenv("COINPAYMENT_PRIVATE_KEY");
        $secretKey = getenv("COINPAYMENT_PUBLIC_KEY");
        $this->coinpayments = new CoinpaymentServices($apiKey, $secretKey);
    }

    public function makePayment(int $quoteId, float $amount, string $currency1='USD')
    {
        $request = request();
        $request->merge([
            "crypto" => "USDC"
        ]);
        
        $currency2 = $request->crypto;
        $buyer_email = $request->user()->email;
        $response = $this->coinpayments->CreateTransactionSimple($amount, $currency1, $currency2, $buyer_email);
        updateSendMoneyRawData($quoteId, $response);l
        return $response['result'] ?? [];
    }

    public function validatePayment($transactionId)
    {
        return $this->coinpayments->status($transactionId);
    }
}
