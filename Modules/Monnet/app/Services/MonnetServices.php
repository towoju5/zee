<?php

namespace Modules\Monnet\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Beneficiary\app\Models\Beneficiary;

class MonnetServices
{
    public function __construct()
    {
        //
    }

    public function makePayment()
    {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function payout($beneficiaryId)
    {
        try {
            $data = self::buildPayoutPayload($beneficiaryId);
            $send = self::api_call("POST", "payouts", $data);
            return $send;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function payout_status($payoutId)
    {
        try {
            $send = self::api_call("GET", "payouts/$payoutId");
            return $send;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function buildPayinPayload($amount, $currency, $method)
    {
        try {
            $request = request();
            $user = $request->user();
            $data = [
                'payinMerchantID' => '00',
                'payinAmount' => $amount,
                'payinCurrency' => $currency,
                'payinMerchantOperationNumber' => '0000',
                'payinMethod' => 'BankTransfer',
                'payinVerification' => 'string',
                'payinCustomerName' => $user->name,
                'payinCustomerLastName' => $user->lastName,
                'payinCustomerEmail' => $user->email,
                'payinCustomerPhone' => $user->phoneNumber,
                'payinCustomerTypeDocument' => 'DNI',
                'payinCustomerDocument' => '00000000',
                'payinRegularCustomer' => $user->name,
                'payinCustomerID' => $user->id,
                'payinDiscountCoupon' => 'string',
                'payinLanguage' => 'EN',
                'payinExpirationTime' => '000',
                'payinDateTime' => date('YYYY-MM-DD'),
                'payinTransactionOKURL' => 'https://test.com',
                'payinTransactionErrorURL' => 'https://test.com',
                'payinFilterBy' => 'string',
                'payinCustomerAddress' => 'string',
                'payinCustomerCity' => 'string',
                'payinCustomerRegion' => 'string',
                'payinCustomerCountry' => 'Peru',
                'payinCustomerZipCode' => '0000',
                'payinCustomerShippingName' => 'string',
                'payinCustomerShippingPhone' => '0000',
                'payinCustomerShippingAddress' => 'string',
                'payinCustomerShippingCity' => 'string',
                'payinCustomerShippingRegion' => 'string',
                'payinCustomerShippingCountry' => 'Peru',
                'payinCustomerShippingZipCode' => '0000',
                'payinProductID' => '0000',
                'payinProductDescription' => 'string',
                'payinProductAmount' => '0000',
                'payinProductSku' => 'string',
                'payinProductQuantity' => '0000',
                'URLMonnet' => 'https://cert.monnetpayments.com/api-payin/v1/online-payments',
                'typePost' => 'json',
            ];
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function buildPayoutPayload($beneficiaryId)
    {
        $request = request();
        $customer = Beneficiary::whereId($beneficiaryId)->whereUserId(active_user())->first();
        $arr = [
            'country' => $request->country,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'orderId' => uuid(),
            'description' => $request->description,
            'beneficiary' => [
                "customerId" => $customer->id,
                "userName" => $customer->name,
                'name' => $customer->firstName,
                'lastName' => $customer->lastName,
                'email' => $customer->email,
                'document' => [
                    'type' => $customer->document_type,
                    'number' => $customer->document_number,
                ],
                'address' => [
                    'street' => $customer->address->street,
                    'houseNumber' => $customer->address->houseNumber,
                    'additionalInfo' => $customer->address->additionalInfo,
                    'city' => $customer->address->city,
                    'province' => $customer->address->province,
                    'zipCode' => $customer->address->zipCode,
                ],
            ],
            'destination' => [
                'bankAccount' => [
                    'bankCode' => $customer->destination->bankAccount->bank_code,
                    'accountType' => $customer->destination->bankAccount->accountType,
                    'accountNumber' => $customer->destination->bankAccount->accountNumber,
                    'alias' => $customer->destination->bankAccount->alias,
                    'cbu' => $customer->destination->bankAccount->cbu,
                    'cci' => $customer->destination->bankAccount->cci,
                    'clave' => $customer->destination->bankAccount->clave,
                    'location' => [
                        'street' => $customer->destination->bankAccount->location->street,
                        'houseNumber' => $customer->destination->bankAccount->location->houseNumber,
                        'additionalInfo' => $customer->destination->bankAccount->location->additionalInfo,
                        'city' => $customer->destination->bankAccount->location->city,
                        'province' => $customer->destination->bankAccount->location->province,
                        'country' => $customer->destination->bankAccount->location->country,
                        'zipCode' => $customer->destination->bankAccount->location->zipCode,
                    ],
                ],
            ]
        ];
        return array_filter($arr);
    }

    public function api_call(string $method="GET", string $endpoint="", array $payload=[])
    {
        $monnet_api = getenv("MONNET_PAYOUT_URL");
        $merchantId = getenv("MONNET_MERCHANT_ID");
        $apiKey = getenv("MONNET_API_TOKEN");
        $apiSecret = getenv("MONNET_API_SECRET");
        $timestamp = time();
        $httpMethod = $method;
        $endpoint = "$monnet_api/v1/$merchantId/$endpoint";
        $requestBody = json_encode($payload);
        $hashedBody = hash('sha256', $requestBody);
        $stringToSign = $httpMethod . $endpoint . $timestamp . $hashedBody;
        $signature = hash_hmac('sha256', $stringToSign, $apiSecret);
        $headers = [
            'Content-Type: application/json',
            'monnet-api-key: ' . $apiKey,
        ];
        $endpoint .= '?signature=' . $signature;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
        echo $response;
    }
}
