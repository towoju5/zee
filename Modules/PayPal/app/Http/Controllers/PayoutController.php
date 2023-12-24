<?php

namespace Modules\PayPal\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;
use Sample\PayPalClient;

class PayoutController extends Controller
{
    public function init(Request $request)
    {
        $user = $request->user();
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
}
