<?php

namespace Modules\Monnet\app\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Beneficiary\app\Models\Beneficiary;

class MonnetServices
{
    public function payout($amount, $currency, $beneficiaryId = null)
    {
        try {
            $beneficiary = Beneficiary::whereId($beneficiaryId)->whereUserId(auth()->id())->first();
            if(!$beneficiary) {
                return get_error_response(['error' => "Beneficiary not found"]);
            }

            $country_data = self::getPaymentData($currency);
            $customer  = (object)[];
            $apiSecret = "yHVNUu6tLqJH8xiSppn9Gg8yAUOhY15xWQfuw3L4Jis=";
            $HTTPmethod =    "POST";
            $resourcePath   =  "/api/v1/125/payouts";
            $timestamp  =     "?timestamp=".time();
            $request = request();
            
            if(in_array($currency, ['ARS'])) {
                $body = $this->buildPayout($country_data['country'], $amount, $currency, 'R123456', 'FreeTextFreeTextFreeTextFreeText', '002', '1', null, '002123456789123456');
                // $body = $this->buildPayout('ARG', $amount, $currency, uuid(), $request->description ?? 'Payout request', $customer->bankCode, $customer->accountType, null, $customer->accountNumber);
            } else if(in_array($currency, ['MXN'])) {
                $body = $this->buildPayout($country_data['country'], $amount, $currency, 'R123456', 'FreeTextFreeTextFreeTextFreeText', '002', '1', null, null, '002123456789123456');
                // $body = $this->buildPayout('MEX', $amount, $currency, uuid(), $request->description ?? 'Payout request', $customer->bankCode, $customer->accountType, null, null, $customer->accountNumber);
            } else {
                $body = $this->buildPayout($country_data['country'], $amount, $currency, uuid(8), 'FreeTextFreeTextFreeTextFreeText', '001', '1', '00000000000');
                $body = $this->buildPayout('PER', 100000, 'PEN', 'R123456', 'FreeTextFreeTextFreeTextFreeText', '001', '1', '00000000000');
            }
            $sample_hashedBody = hash('sha256', json_encode($body), false);
            $_data = $HTTPmethod.':'.$resourcePath.$timestamp.':'.$sample_hashedBody;
            $signature = hash_hmac('sha256', $_data, $apiSecret);
            // return $body; exit;
            $endpoint = 'https://cert.api.payout.monnet.io'.$resourcePath.$timestamp.'&signature='.$signature;
            $payoutDataOther = $body;
            $response = Http::withHeaders([
                'monnet-api-key' => 'G9daslndjmf2XZtbyeboxIwtq1OopE7nji28jRdt4P4=',
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payoutDataOther)->json();

            // Log::info(json_encode($payoutDataOther));
            // Log::info($body);
            // Log::info($_data);

            return get_success_response($response);
            
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

    public function buildPayinPayload($amount, $currency = "PEN")
    {
        $request = request();
        $user = $request->user();
        $payment_data = self::getPaymentData($currency);
        // echo json_encode($payment_data); exit;
        $txn = uuid();
        $amount = convertIntToDecimal($amount);
        $key = $payment_data['merchantKey'];
        $merchantId = $payment_data['merchantId'];

        $verificationString = self::generateVerificationString($merchantId, $txn, $amount, $currency, $key);
        $data = [
            'payinMerchantID' => $merchantId,
            'payinAmount' => $amount,
            'payinCurrency' => $currency,
            'payinMerchantOperationNumber' => $txn,
            'payinMethod' => 'BankTransfer',
            'payinVerification' => $verificationString,
            'payinCustomerName' => $user->name,
            'payinCustomerLastName' => $user->lastName,
            'payinCustomerEmail' => $user->email,
            'payinCustomerPhone' => $user->phoneNumber,
            'payinCustomerTypeDocument' => "PP", //$user->idType,
            'payinCustomerDocument' => $user->idNumber,
            'payinRegularCustomer' => $user->name,
            'payinCustomerID' => $user->id,
            'payinLanguage' => 'EN',
            'payinExpirationTime' => 30,
            'payinDateTime' => date("Y-m-d"),
            'payinTransactionOKURL' => route("callback.monnet.success", [$user->id, $txn]),
            'payinTransactionErrorURL' => route("callback.monnet.failed", [$user->id, $txn]),
            'payinCustomerAddress' => $user->street,
            'payinCustomerCity' => $user->city,
            'payinCustomerRegion' => $user->state,
            'payinCustomerCountry' => $user->country,
            'payinCustomerZipCode' => $user->zipCode,
            'payinCustomerShippingName' => $user->name,
            'payinCustomerShippingPhone' => $user->phoneNumber,
            'payinCustomerShippingAddress' => $user->street,
            'payinCustomerShippingCity' => $user->city,
            'payinCustomerShippingRegion' => $user->state,
            'payinCustomerShippingCountry' => $user->country,
            'payinCustomerShippingZipCode' => $user->zipCode,
            'payinProductID' => $txn,
            'payinProductDescription' => 'Wallet top up on ' . getenv('APP_NAME'),
            'payinProductAmount' => convertIntToDecimal($amount),
            'payinProductSku' => $txn,
            'payinProductQuantity' => 1,
            'URLMonnet' => "https://cert.monnetpayments.com/api-payin/v3/online-payments",
            'typePost' => 'json',
        ];
        return $data;
    }

    public function payin($quoteId, $amount, $currency)
    {
        try {
            $data = self::buildPayinPayload($amount, $currency);
            return $request = Http::post(getenv("MONNET_PAYIN__URL"), $data)->json();
            updateSendMoneyRawData($quoteId, [
                'user_request' =>  $data,
                'gateway_response' => to_array($request)
            ]);
            $response = to_array($request);
            return $response["url"];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
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
            'description' => $request->description ?? "Payout from " . getenv('APP_NAME'),
            'beneficiary' => [
                "customerId" => $customer->id,
                "userName" => $customer->name,
                'name' => $customer->firstName,
                'lastName' => $customer->lastName,
                'email' => $customer->email,
                'document' => [
                    'type' => $customer->idType,
                    'number' => $customer->document_number,
                ],
                'address' => [
                    'street' => $customer->address->street,
                    'houseNumber' => $customer->houseNumber,
                    'additionalInfo' => $customer->address->additionalInfo,
                    'city' => $customer->address->city,
                    'province' => $customer->address->province,
                    'zipCode' => $customer->address->zipCode,
                ],
            ],
            'destination' => [
                'bankAccount' => [
                    'bankCode' => $customer->destination->bankAccount->bank_code ?? null,
                    'accountType' => $customer->destination->bankAccount->accountType ?? null,
                    'accountNumber' => $customer->destination->bankAccount->accountNumber ?? null,
                    'alias' => $customer->destination->bankAccount->alias ?? null,
                    'cbu' => $customer->destination->bankAccount->cbu ?? null,
                    'cci' => $customer->destination->bankAccount->cci ?? null,
                    'clave' => $customer->destination->bankAccount->clave ?? null,
                    'location' => [
                        'street' => $customer->destination->bankAccount->location->street ?? null,
                        'houseNumber' => $customer->destination->bankAccount->location->houseNumber ?? null,
                        'additionalInfo' => $customer->destination->bankAccount->location->additionalInfo ?? null,
                        'city' => $customer->destination->bankAccount->location->city ?? null,
                        'province' => $customer->destination->bankAccount->location->province ?? null,
                        'country' => $customer->destination->bankAccount->location->country ?? null,
                        'zipCode' => $customer->destination->bankAccount->location->zipCode ?? null,
                    ],
                ],
            ],
        ];
        return array_filter($arr);
    }

    /**
     * 
     * // Example usage:
     * // For Argentina
     * $payoutDataArg = $this->buildPayout('ARG', 100000, 'ARS', 'R123456', 'FreeTextFreeTextFreeTextFreeText', '002', '1', null, '002123456789123456');
     * // For Mexico
     * $payoutDataMex = $this->buildPayout('MEX', 100000, 'MXN', 'R123456', 'FreeTextFreeTextFreeTextFreeText', '002', '1', null, null, '002123456789123456');
     * // For other countries
     * $payoutDataOther = $this->buildPayout('PER', 100000, 'PEN', 'R123456', 'FreeTextFreeTextFreeTextFreeText', '001', '1', '00000000000');
     */
    private function buildPayout($country, $amount, $currency, $orderId, $description, $bankCode, $accountType, $accountNumber = null, $cbu = null, $clave = null, $extraDetails = [])
    {
        // var_dump($cbu); exit;
        // return [
        //     'country' => 'PER',
        //     'currency' => 'PEN',
        //     'amount' => 50,
        //     'orderId' => uuid(8),
        //     'beneficiary' => [
        //       'name' => 'jose',
        //       'lastName' => 'fernández',
        //       'email' => 'jose.valdes@calimaco.com',
        //       'document' => [
        //         'type' => 1,
        //         'number' => 25323925,
        //       ],
        //     ],
        //     'destination' => [
        //       'bankAccount' => [
        //         'bankCode' => '003',
        //         'accountType' => 2,
        //         'cci' => '00389898767676767273',
        //       ],
        //     ],
        // ];
        $bankAccount = [
            'bankCode' => $bankCode,
            'accountType' => $accountType,
        ];

        // Add appropriate bank account details based on the country's requirements
        $body = [
            'country' => $country,
            'amount' => $amount,
            'currency' => $currency,
            'orderId' => $orderId,
            'description' => $description,
            'beneficiary' => [
                'name' => 'Sergio',
                'lastName' => 'test',
                'email' => 'test@test.com',
                'document' => [
                    'type' => 1,
                    'number' => '33446836928',
                ],
            ],
            'destination' => [
                'bankAccount' => $bankAccount,
            ],
        ];
        if ($currency === 'ARS' && $cbu !== null) {
            $body['destination']['bankAccount']['cbu'] = $cbu;
        } elseif ($currency === 'MXN' && $clave !== null) {
            $body['destination']['bankAccount']['clave'] = $clave;
        } elseif ($accountNumber !== null) {
            $body['destination']['bankAccount']['accountNumber'] = $accountNumber;
        }


        return response()->json($body);  exit;

        return $body;
    }

    // private function buildPeruPayout()
    // {
    //     $body = [
    //         'country' => 'PER',
    //         'amount' => 100000,
    //         'currency' => 'PEN',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '001',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildMexicoPayout()
    // {
    //     // if(!in_array())
    //     $body = [
    //         'country' => 'MEX',
    //         'amount' => 100000,
    //         'currency' => 'MXN',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '002',
    //                 'clave' => '002123456789123456',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildHondurasayout()
    // {
    //     $body = [
    //         'country' => 'HND',
    //         'amount' => 100000,
    //         'currency' => 'HNL',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '0101',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildArgentinaPayout()
    // {
    //     $body = [
    //         'country' => 'ARG',
    //         'amount' => 100000,
    //         'currency' => 'ARS',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '002',
    //                 'accountType' => '1',
    //                 'cbu' => '002123456789123456',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildChilePayout()
    // {
    //     $body = [
    //         'country' => 'CHL',
    //         'amount' => 100000,
    //         'currency' => 'CLP',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '100',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildColombiaPayout()
    // {
    //     $body = [
    //         'country' => 'COL',
    //         'amount' => 100000,
    //         'currency' => 'COP',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '001',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildEcuadorPayout()
    // {
    //     $body = [
    //         'country' => 'ECU',
    //         'amount' => 100000,
    //         'currency' => 'USD',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '100',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    // private function buildGuatemalaPayout()
    // {
    //     $body = [
    //         'country' => 'GTM',
    //         'amount' => 100000,
    //         'currency' => 'GTQ',
    //         'orderId' => 'R123456',
    //         'description' => 'FreeTextFreeTextFreeTextFreeText',
    //         'beneficiary' => [
    //             'name' => 'Sergio',
    //             'lastName' => 'test',
    //             'email' => 'test@test.com',
    //             'document' => [
    //                 'type' => 1,
    //                 'number' => '33446836',
    //             ],
    //         ],
    //         'destination' => [
    //             'bankAccount' => [
    //                 'bankCode' => '0101',
    //                 'accountType' => '1',
    //                 'accountNumber' => '00000000000',
    //             ],
    //         ],
    //     ];
    // }

    public function api_call(string $method = "GET", string $endpoint = "", array $payload = [], $type = 'payin')
    {
        $monnet_api = getenv("MONNET_PAYOUT_URL");
        $merchantId = getenv("MONNET_MERCHANT_ID");
        $apiKey = getenv("MONNET_PERU");
        $apiSecret = getenv("MONNET_API_SECRET");
        $timestamp = time();
        $httpMethod = $method;
        if ($type == "payin") {
            $endpoint = "https://cert.payin.api.monnetpayments.com/api-payin/v3/online-payments";
        } else {
            $endpoint = "https://cert.api.payout.monnet.io/v1/{$merchantId}/payouts";
        }
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
        var_dump($response);
        exit;
    }

    private function generateVerificationString($payinMerchantID, $payinMerchantOperationNumber, $payinAmount, $payinCurrency, $KeyMonnet)
    {
        $concatenatedString = $payinMerchantID . $payinMerchantOperationNumber . $payinAmount . $payinCurrency . $KeyMonnet;
        $verificationString = openssl_digest($concatenatedString, 'sha512');

        return $verificationString;
    }

    public function webhook(Request $request)
    {
    }

    public function getPaymentData($currency)
    {
        switch ($currency) {
            case 'COP':
                $data = [
                    "merchantId" => getenv("MONNET_COLUMBIA_ID"),
                    "merchantKey" => getenv("MONNET_COLUMBIA"),
                    "country" =>    "COL"
                ];
                break;

            case 'PEN':
                $data = [
                    "merchantId" => getenv("MONNET_PERU_ID"),
                    "merchantKey" => getenv("MONNET_PERU"),
                    "country" =>    "PER"
                ];
                break;

            case 'USD':
                $data = [
                    "merchantId" => getenv("MONNET_ECUADO_ID"),
                    "merchantKey" => getenv("MONNET_ECUADO"),
                    "country" =>    "USD"
                ];
                break;

            case 'CLP':
                $data = [
                    "merchantId" => getenv("MONNET_CHILE_ID"),
                    "merchantKey" => getenv("MONNET_CHILE"),
                    "country" =>    "CLP"
                ];
                break;

            case 'ARS':
                $data = [
                    "merchantId" => getenv("MONNET_ARGENTINA_ID"),
                    "merchantKey" => getenv("MONNET_ARGENTINA"),
                    "country" =>    "ARG"
                ];
                break;

            case 'MXN':
                $data = [
                    "merchantId" => getenv("MONNET_MEXICO_ID"),
                    "merchantKey" => getenv("MONNET_MEXICO"),
                    "country" =>    "MEX"
                ];
                break;

            default:
                // DEFFAULT TO USD
                $data = [
                    "merchantId" => getenv("MONNET_ECUADO_ID"),
                    "merchantKey" => getenv("MONNET_ECUADO"),
                    "country" =>    "USD"
                ];
                break;
        }

        return $data;
    }
}
