<?php

namespace Modules\PayPal\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SendMoney\app\Models\SendQuote;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;
// use Sample\PayPalClient;

class PayoutController extends Controller
{
    protected $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            )
        );
    }

    // paypal payout starts here
    public function init($quoteId)
    {
        $quote = SendQuote::whereId($quoteId)->first();
        $user = $get_user($quote->user_id);
        $requestBody = [
            'sender_batch_header' => [ 
                'email_subject' => 'Payout from $user->name via ' . getenv('APP_NAME'),
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'receiver' => $request->email,
                    'note' => $request->note ?? "Payout request from $user->name",
                    'sender_item_id' => uuid(8),
                    'amount' => [
                        'currency' => $request->currency,
                        'value' => $request->amount,
                    ],
                ],
            ],
        ];

        try {
            $request = new PayoutsPostRequest();
            $request->body = $requestBody;
            $client = PayPalClient::client();
            $response = $client->execute($request);
            if ($response) {
                print "Status Code: {$response->statusCode}\n";
                print "Status: {$response->result->batch_header->batch_status}\n";
                print "Batch ID: {$response->result->batch_header->payout_batch_id}\n";
                print "Links:\n";
                foreach ($response->result->links as $link) {
                    print "\t{$link->rel}: {$link->href}\tCall Type: {$link->method}\n";
                }
                // To toggle printing the whole response body comment/uncomment below line
                echo json_encode($response->result, JSON_PRETTY_PRINT), "\n";
            }
            return $response;
        } catch (\Throwable $e) {
            //Parse failure response
            echo $e->getMessage() . "\n";
            $error = json_decode($e->getMessage());
            echo $error->message . "\n";
            echo $error->name . "\n";
            echo $error->debug_id . "\n";
        }
    }

    public function payout_webhook(Request $request)
    {
        $webhookId = $request->header('Paypal-Transmission-Id');

        $event = WebhookEvent::createFromJson($request->getContent(), $this->apiContext);

        // Handle the event based on its type
        switch ($event->event_type) {
            case 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED':
                $this->handlePayoutSucceeded($event);
                break;
            // Add more cases for other event types as needed
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePayoutSucceeded(WebhookEvent $event)
    {
        // Extract information from the event
        $payoutItemId = $event->resource->id;
        $amount = $event->resource->amount->value;
        // Handle the payout success event, e.g., update your database
    }
}
