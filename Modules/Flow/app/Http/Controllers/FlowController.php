<?php

namespace Modules\Flow\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Flow\app\Services\FlowServices;
use Throwable;

class FlowController extends Controller
{
    public function makePayment($quoteId, $amount, $currency)
    {
        try {
            $user = auth()->user();
            $optional = [];
            $optional = json_encode($optional);

            //Prepara el arreglo de datos
            $params = [
                "commerceOrder" => $quoteId,
                "subject" => "Wallet topup by {$user->name}",
                "currency" => "CLP", //$currency,
                "amount" => $amount,
                "email" => "cliente@gmail.com",
                "paymentMethod" => 9,
                "urlConfirmation" => route('callback.flow', [$user->id, $quoteId]),
                "urlReturn" => route('callback.flow', [$user->id, $quoteId]),
                "optional" => $optional,
            ];
            $serviceName = "payment/create";
            $flowApi = new FlowServices;
            $response = $flowApi->send($serviceName, $params, "POST");
            // $redirect = $response["url"] . "?token=" . $response["token"];
            updateSendMoneyRawData($quoteId, $response);
            return $response;
        } catch (Throwable $e) {
            echo $e->getCode() . " - " . $e->getMessage();
        }
    }

}
