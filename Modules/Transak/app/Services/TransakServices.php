<?php

namespace app\Services;

class TransakServices
{
    public function get_order($orderId)
    {
        $client = new \GuzzleHttp\Client();

        $url = getenv('TRANSAK_BASE_URL')."/order/{$orderId}";
        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'access-token' => 'YOUR_ACCESS_TOKEN',
            ],
        ]);

        return $response->getBody();
    }

    public function create_url()
    {
        $request = request();
    }
}
