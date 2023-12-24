<?php

namespace Modules\Advcash\app\Services;

use GuzzleHttp\Client;

class AdvCashService
{
    protected $apiKey;
    protected $apiPassword;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.advcash.api_key');
        $this->apiPassword = config('services.advcash.api_password');
        $this->apiUrl = 'https://api.advcash.com/v1/';
    }

    public function initiatePayment($amount, $currency, $description)
    {
        $client = new Client();

        $response = $client->post($this->apiUrl . 'createPayment', [
            'json' => [
                'access_token' => $this->apiKey,
                'amount' => $amount,
                'currency' => $currency,
                'note' => $description,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function handleCallback($callbackData)
    {
        // Implement your logic to handle the callback data
        // Verify the callback data against your records and mark the transaction as completed
        // Update your database, etc.

        return ['status' => 'success'];
    }
}
