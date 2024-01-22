<?php
namespace Modules\Monnet\app\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Modules\Beneficiary\app\Models\Beneficiary;

class MonnetServices
{
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

    public function buildPayinPayload($amount, $currency="PEN")
    {
        $request = request();
        $user = $request->user();
        $payment_data = self::getPaymentData($currency);
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
            $request =  Http::post(getenv("MONNET_PAYIN__URL"), $data)->json();
            updateSendMoneyRawData($quoteId, $data);
            $response =  to_array($request);
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
            ],
        ];
        return array_filter($arr);
    }

    public function api_call(string $method = "GET", string $endpoint = "", array $payload = [])
    {
        $monnet_api = getenv("MONNET_PAYOUT_URL");
        $merchantId = getenv("MONNET_MERCHANT_ID");
        $apiKey = getenv("MONNET_PERU");
        $apiSecret = getenv("MONNET_API_SECRET");
        $timestamp = time();
        $httpMethod = $method;
        $endpoint = "https://cert.payin.api.monnetpayments.com/api-payin/v3/online-payments";
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
        var_dump($response); exit;
    }

    private function generateVerificationString($payinMerchantID, $payinMerchantOperationNumber, $payinAmount, $payinCurrency, $KeyMonnet)
    {
        $concatenatedString = $payinMerchantID . $payinMerchantOperationNumber . $payinAmount . $payinCurrency . $KeyMonnet;
        $verificationString = openssl_digest($concatenatedString,'sha512');

        return $verificationString;
    }

    public function webhook(Request $request)
    {}

    public function getPaymentData($currency) 
    {
        switch ($currency) {
            case 'COP':
                $data  = [
                    "merchantId" => getenv("MONNET_COLUMBIA_ID"),
                    "merchantKey"=> getenv("MONNET_COLUMBIA"),
                ];
                break;
            
            case 'PEN':
                $data  = [
                    "merchantId" => getenv("MONNET_PERU_ID"),
                    "merchantKey"=> getenv("MONNET_PERU"),
                    // "region" => "Lima"
                ];
                break;
            
            case 'USD':
                $data  = [
                    "merchantId" => getenv("MONNET_ECUADO_ID"),
                    "merchantKey"=> getenv("MONNET_ECUADO"),
                ];
                break;
            
            case 'CLP':
                $data  = [
                    "merchantId" => getenv("MONNET_CHILE_ID"),
                    "merchantKey"=> getenv("MONNET_CHILE"),
                ];
                break;
            
            case 'ARS':
                $data  = [
                    "merchantId" => getenv("MONNET_ARGENTINA_ID"),
                    "merchantKey"=> getenv("MONNET_ARGENTINA"),
                ];
                break;
            
            case 'MXN':
                $data  = [
                    "merchantId" => getenv("MONNET_MEXICO_ID"),
                    "merchantKey"=> getenv("MONNET_MEXICO"),
                ];
                break;
            
            default:
                // DEFFAULT TO USD
                $data  = [
                    "merchantId" => getenv("MONNET_ECUADO_ID"),
                    "merchantKey"=> getenv("MONNET_ECUADO"),
                ];
                break;
        }

        return $data;
    }
}
