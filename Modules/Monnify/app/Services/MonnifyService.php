<?php
namespace Modules\Monnify\App\Services;

use Bhekor\LaravelMonnify\Classes\MonnifyIncomeSplitConfig;
use Bhekor\LaravelMonnify\Classes\MonnifyPaymentMethod;
use Bhekor\LaravelMonnify\Classes\MonnifyPaymentMethods;
use Bhekor\LaravelMonnify\Facades\Monnify;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class MonnifyService
{
    public function createCheckoutUrl($amount, $currency)
    {
        $user = auth()->user();
        $paymentReference = uuid(8);
        $paymentDescription = "Payment to " . getenv('APP_NAME');
        $redirectUrl = "";
        $monnifyPaymentMethods = null;
        $monnifyPaymentMethods = new MonnifyPaymentMethods(MonnifyPaymentMethod::CARD(), MonnifyPaymentMethod::ACCOUNT_TRANSFER());
        $incomeSplitConfig  = new \Bhekor\LaravelMonnify\Classes\MonnifyIncomeSplitConfig;

        $responseBody = Monnify::Transactions()
            ->initializeTransaction($amount, $user->name, $user->email, $paymentReference, $paymentDescription, $redirectUrl, $monnifyPaymentMethods);
        return $responseBody;
    }

    public function verifyTrans($transRef)
    {
        $ref = urlencode($transRef);
        $responseBody = Monnify::Transactions()->getTransactionStatus($transRef);
        return $responseBody;
    }
}
