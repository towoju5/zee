<?php

namespace Modules\Advcash\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Advcash\app\Services\AdvCashService;

class AdvcashController extends Controller
{
    protected $advCashService;

    public function __construct(AdvCashService $advCashService)
    {
        $this->advCashService = $advCashService;
    }

    public function initiatePayment(Request $request)
    {
        $amount = $request->input('amount');
        $currency = $request->input('currency', 'USD');
        $description = $request->input('description', 'Payment description');

        $paymentResponse = $this->advCashService->initiatePayment($amount, $currency, $description);

        // Redirect the user to the payment gateway or handle the response as needed
        // Example: return a response with payment details
        return response()->json($paymentResponse);
    }

    public function handleCallback(Request $request)
    {
        $callbackData = $request->all();

        $callbackResponse = $this->advCashService->handleCallback($callbackData);

        // Return a response to the payment gateway
        return response()->json($callbackResponse);
    }
}
