<?php

namespace Modules\Flutterwave\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use PhpParser\Node\Stmt\Return_;

class FlutterwaveController extends Controller
{
    public function makePayment($amount, $currency="NGN")
    {
        try {
            //This generates a payment reference
            $reference = Flutterwave::generateReference();
            $user = auth()->user();
            // Enter the details of the payment
            $data = [
                'payment_options' => 'card,banktransfer',
                'amount' => $amount,
                'email' => $user->email,
                'tx_ref' => $reference,
                'currency' => $currency,
                // 'redirect_url' => route('callback'),
                'customer' => [
                    'email' => $user->email,
                    "phone_number" => $user->phone,
                    "name" => $user->name
                ],

                "customizations" => [
                    "title" => getenv('APP_NAME'),
                    "description" => "20th October"
                ]
            ];

            $payment = Flutterwave::initializePayment($data);


            if ($payment['status'] !== 'success') {
                // notify something went wrong
                return $payment;
            }

            // return the payment link
            return $payment['data']['link'];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function validatePayment($transactionId)
    {
        try {
            // $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionId);
            return $data;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }
}
