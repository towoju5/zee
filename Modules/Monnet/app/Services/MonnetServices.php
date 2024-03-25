<?php

namespace Modules\Monnet\app\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Beneficiary\app\Models\Beneficiary;

class MonnetServices
{
    public $baseUrl;
    public function __construct()
    {
        $this->baseUrl = 'https://cert.api.payout.monnet.io';
    }

    public function payout($amount, $currency, $beneficiaryId) 
    {
        try {
            $country_data = self::getPaymentData($currency);
            $apiSecret = getenv('MONNET_API_SECRET');
            $HTTPmethod =    "POST";
            $resourcePath   =  "/api/v1/125/payouts";
            $timestamp  =     "?timestamp=".time();
            $request = request();
            $description = $request->description ?? "Payout requesst";
            
            // $body = $this->buildPayout($country_data['country'], $amount, $currency, uuid(), $description, $beneficiaryId);
            $body = $this->buildPayoutPayload($beneficiaryId, $country_data['country']);

            $sample_hashedBody = hash('sha256', json_encode($body), false);
            $_data = $HTTPmethod.':'.$resourcePath.$timestamp.':'.$sample_hashedBody;
            $signature = hash_hmac('sha256', $_data, $apiSecret);
            // return $body; exit;
            $endpoint = $this->baseUrl.$resourcePath.$timestamp.'&signature='.$signature;
            $payoutDataOther = $body;
            $response = Http::withHeaders([
                'monnet-api-key' => getenv('MONNET_API_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payoutDataOther)->json();

            Log::info(json_encode(['request' => $body, 'response' => $response]));

            return to_array($response);
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function payoutStatus($payoutId = null)
    {
        try {
            $apiSecret = getenv('MONNET_API_SECRET');
            $HTTPmethod = "GET";
            $resourcePath = '/api/v1/125/payouts/'.$payoutId;
            $timestamp  =  "?timestamp=".time();
            $body = ''; //request()->post();
            $sample_hashedBody = hash('sha256', '', false);
            $_data = $HTTPmethod.':'.$resourcePath.$timestamp.':'.$sample_hashedBody;
            $signature = hash_hmac('sha256', $_data, $apiSecret);
            $endpoint = $this->baseUrl.$resourcePath.$timestamp.'&signature='.$signature;
        
            $response = Http::withHeaders([
                'monnet-api-key' => getenv('MONNET_API_TOKEN'),
                'Content-Type' => 'application/json',
            ])->get($endpoint)->json();
        
            Log::info(json_encode(['request' => $body, 'response' => $response]));
            return $response;          
        } catch (\Throwable $th) {
            if(getenv("APP_DEBUG")) {
                return ["error"=> $th->getTrace()];
            }
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

        // if(!in_array($payinMethod, ['TCTD', 'BankTransfer'])) return false;

        $verificationString = self::generateVerificationString($merchantId, $txn, $amount, $currency, $key);
        $data = [
            'payinMerchantID' => $merchantId,
            'payinAmount' => $amount,
            'payinCurrency' => $currency,
            'payinMerchantOperationNumber' => $txn,
            'payinMethod' => 'TCTD',
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
            'payinTransactionOKURL' => "https://cert.monnetpayments.com/api-payin/v3/online-payments", //route("callback.monnet.success", [$user->id, $txn]),
            'payinTransactionErrorURL' => "https://cert.monnetpayments.com/api-payin/v3/online-payments", //route("callback.monnet.failed", [$user->id, $txn]),
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

    public function payin($quoteId, $amount, $currency, $type='send_money')
    {
        try {
            $data = self::buildPayinPayload($amount, $currency);
            $request = Http::post(getenv("MONNET_PAYIN__URL"), $data)->json();
            if (strtolower($type) != 'deposit') {
                updateSendMoneyRawData(
                    $quoteId, 
                    [
                        'user_request' =>  $data,
                        'gateway_response' => to_array($request)
                    ]
                );
            } else {
                updateDepositRawData(
                    $quoteId, 
                    [
                        'user_request' =>  $data,
                        'gateway_response' => to_array($request)
                    ]
                );
            }

            $response = to_array($request);
            // echo json_encode($response, JSON_PRETTY_PRINT); exit;

            Log::info(json_encode(['country' => $currency, ['payload' => $data, 'response' => $response]]));

            return $response['url'];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function buildPayoutPayload($beneficiaryId, $country)
    {
        $request = request();
        $customer = Beneficiary::whereId($beneficiaryId)->whereUserId(active_user())->first();
        $arr = [
            'country' => $country,
            'amount' => $request?->amount,
            'currency' => $customer?->currency,
            'orderId' => uuid(),
            'description' => $request?->description ?? "Payout from " . getenv('APP_NAME'),
            'beneficiary' => [
                "userName" => $customer?->bussinessName ?? null,
                'name' => user()?->firstName ?? null,
                'lastName' => user()?->lastName ?? null,
                'email' => user()?->email ?? null,
                'document' => [
                    'type' => $customer?->beneficiary?->document?->type ?? null,
                    'number' => $customer?->beneficiary?->document?->number ?? null,
                ],
                'address' => [
                    'street' => $customer?->address?->street,
                    'houseNumber' => $customer?->houseNumber,
                    'additionalInfo' => $customer?->address?->additionalInfo,
                    'city' => $customer?->address?->city,
                    'province' => $customer?->address?->province,
                    'zipCode' => $customer?->address?->zipCode,
                ],
            ],
            'destination' => [
                'bankAccount' => [
                    'bankCode' => $customer?->payment_object?->bankAccount?->bankCode ?? null,
                    'accountType' => $customer?->payment_object?->bankAccount?->accountType ?? null,
                    'accountNumber' => $customer?->payment_object?->bankAccount?->accountNumber ?? null,
                    'alias' => $customer?->payment_object?->bankAccount?->alias ?? null,
                    'cbu' => $customer?->payment_object?->bankAccount?->cbu ?? null,
                    'cci' => $customer?->payment_object?->bankAccount?->cci ?? null,
                    'clabe' => $customer?->payment_object?->bankAccount?->clave ?? null,
                    'location' => [
                        'street' => $customer?->payment_object?->bankAccount?->location?->street ?? null,
                        'houseNumber' => $customer?->payment_object?->bankAccount?->location?->houseNumber ?? null,
                        'additionalInfo' => $customer?->payment_object?->bankAccount?->location?->additionalInfo ?? null,
                        'city' => $customer?->payment_object?->bankAccount?->location?->city ?? null,
                        'province' => $customer?->payment_object?->bankAccount?->location?->province ?? null,
                        'country' => $customer?->payment_object?->bankAccount?->location?->country ?? null,
                        'zipCode' => $customer?->payment_object?->bankAccount?->location?->zipCode ?? null,
                    ],
                ],
            ],
        ];
        return removeEmptyArrays($arr);
    }

    private function buildPayout($country, $amount, $currency, $orderId, $description, $beneficiaryId)
    {
        try {
            $customer = Beneficiary::whereId($beneficiaryId)->whereUserId(auth()->id())->first();
            if (!$customer) {
                return get_error_response(['error' => "Beneficiary not found"]);
            }

            $beneficiary = $customer['beneficiary'];
            $bank = $customer['payment_object']['bankAccount'];

            $bankAccount = [
                'bankCode' => $bank['bankCode'],
                'accountType' => $bank['accountType'],
            ];

            // Add appropriate bank account details based on the country's requirements
            $body = [
                'country' => $country,
                'amount' => $amount,
                'currency' => $currency,
                'orderId' => $orderId,
                'description' => $description,
                'beneficiary' => [
                    'name' => $beneficiary['name'],
                    'lastName' => $beneficiary['lastName'] ?? $beneficiary['name'],
                    'email' => $beneficiary['email'],
                    'document' => [
                        'type' => $beneficiary['document']['type'],
                        'number' => $beneficiary['document']['number'],
                    ],
                ],
                'destination' => [
                    'bankAccount' => $bankAccount,
                ],
            ];
            if ($currency == 'ARS') {
                $body['destination']['bankAccount']['cbu'] = $bank['cbu'];
            } elseif ($currency == 'MXN') {
                $body['destination']['bankAccount']['clabe'] = $bank['clave'];
            } elseif (!empty($bank['accountNumber'])) {
                $body['destination']['bankAccount']['accountNumber'] = $bank['accountNumber'];
            } elseif (isset($bank['accountType']) && $bank['accountType'] == 4) {
                // set document number as accountNumber
                $body['destination']['bankAccount']['accountNumber'] = $beneficiary['document']['number'];
            }
            // echo json_encode($body, JSON_PRETTY_PRINT); exit;
            return $body;
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
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
                    "country" =>    "CHL"
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
