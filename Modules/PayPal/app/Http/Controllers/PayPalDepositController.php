<?php

namespace Modules\PayPal\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;

class PayPalDepositController extends Controller
{
    public function createOrder($amount=10, $currency="USD")
    {
        $client = $this->getClient();

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = $this->buildRequestBody($currency, $amount);

        try {
            $response = $client->execute($request);

            // Extract the approval URL
            $approvalUrl = $response->result->links[1]->href;

            return redirect($approvalUrl);
        } catch (HttpException $e) {
            // Handle errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function captureOrder(Request $request)
    {
        $client = $this->getClient();

        $request = new OrdersCaptureRequest($request->input('orderID'));

        try {
            $response = $client->execute($request);

            // Handle the captured order response
            return response()->json($response->result);
        } catch (HttpException $e) {
            // Handle errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function buildRequestBody($currency, $amount)
    {
        // Customize this method to build your order request body
        return [
            // Add your order details here
            'intent' => 'CAPTURE',
            'application_context' => [
                'brand_name' => config('app.name'),
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
            ],
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ],
            ],
        ];
    }

    protected function getClient()
    {
        return new \PayPalHttp\HttpClient(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        );
    }
}
