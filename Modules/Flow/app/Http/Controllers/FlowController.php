<?php

namespace Modules\Flow\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\SendMoney\app\Models\SendQuote;

class FlowController extends Controller
{
    public function makePayment($quoteId, $amount, $currency)
    {
        $bearerToken = "";
        $url = "payment/create";
        $quote = SendQuote::whereId($quoteId)->first();
        $user  = User::find($quote->user_id);
        $data = [
            "apiKey" => "{{apiKey}}",
            "subject" => "Pago de prueba POSTMAN",
            "currency" => $quote->send_currency,
            "amount" => $quote->send_amount,
            "email" => $user->email,
            "urlConfirmation" => "http://flowosccomerce.tuxpan.com/csepulveda/api2/pay/confirmPay.php",
            "urlReturn" => "http://flowosccomerce.tuxpan.com/csepulveda/api2/pay/resultPay.php",
            "s" => $this->sign_request()
        ];
        $send_request = Http::withToken($bearerToken)->post($url, $data)->json();
        return $send_request;
    }

    private function sign_request($keys=null, $params=null)
    {
        $toSign = "";
        foreach ($keys as $key) {
            $toSign .= $key . $params[$key];
        };
    }

    public function run_call($url, $params)
    {
        $url = 'https://www.flow.cl/api';
        // Agrega a la url el servicio a consumir
        $url = $url . '/payment/create';
        $params["s"] = $signature;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                throw new \Exception($error, 1);
            }
            $info = curl_getinfo($ch);

            if(!in_array($info['http_code'], [200, 400, 401])) {
                throw new \Exception('Unexpected error occurred. HTTP_CODE: '.$info['http_code'] , $info['http_code']);
            }

            return $info;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }
}
