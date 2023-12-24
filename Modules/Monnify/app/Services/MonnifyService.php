<?php
namespace App\Services;

use GuzzleHttp\Client;

class MonnifyService
{
    protected $apiUrl;
    protected $apiKey;
    protected $contractCode;

    public function __construct()
    {
        $this->apiUrl = config('services.monnify.api_url');
        $this->apiKey = config('services.monnify.api_key');
        $this->contractCode = config('services.monnify.contract_code');
    }

    public function createCheckoutUrl($amount, $currency)
    {
        $client = new Client();
        $user = auth()->user();
        $customerEmail = $user->email;
        $paymentReference = uuid(8);

        $response = $client->post($this->apiUrl . '/v1/merchant/checkout', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiKey),
                'Content-Type' => 'application/json',
            ],
            
            'json' => [
                'contractCode' => $this->contractCode,
                'amount' => $amount,
                'paymentReference' => $paymentReference,
                'currencyCode' => $currency,
                'customerEmail' => $customerEmail,
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        return $responseData['data']['checkoutUrl'] ?? null;
    }
}
