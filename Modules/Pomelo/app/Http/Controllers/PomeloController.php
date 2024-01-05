<?php

namespace Modules\Pomelo\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Pomelo\app\Services\PomeloPayServices;

class PomeloController extends Controller
{
    public $pomelo_services;

    public function __construct()
    {
        $this->pomelo_services = new PomeloPayServices();
    }

    public function makePayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required',
                'currency' => 'currency'
            ]);
            $checkout = $this->pomelo_services->init($request->amount, $request->currency);
            return get_success_response($checkout);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function webhook(Request $request, $orderId)
    {
        // Usage example
        $headers = [
            'X-Signature-Nonce' => 'your_nonce',
            'X-Signature-Timestamp' => 'your_timestamp',
            'X-Signature' => 'received_signature'
        ];

        $apiKey = 'your_api_key';
        $isValidSignature = $this->pomelo_services->verify_webhook($headers, $apiKey);
        var_dump($isValidSignature);
    }
}
