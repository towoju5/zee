<?php

namespace Modules\Monnify\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MonnifyService;

class MonnifyController extends Controller
{
    protected $monnifyService;

    public function __construct(MonnifyService $monnifyService = null)
    {
        $this->monnifyService = $monnifyService;
    }

    public function createCheckout(int $quoteId, float $amount, string $currency)
    {
        $customerEmail = auth()->user()->email;
        $paymentReference = uuid(); 

        $checkoutUrl = $this->monnifyService->createCheckoutUrl($customerEmail, $amount, $paymentReference);

        updateSendMoneyRawData($quoteId, $checkoutUrl);
        return $response;
        // Redirect the user to the checkout URL
        return redirect($checkoutUrl);
    }
}
