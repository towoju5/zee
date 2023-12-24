<?php

namespace Modules\Monnify\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MonnifyService;

class MonnifyController extends Controller
{
    protected $monnifyService;

    public function __construct(MonnifyService $monnifyService)
    {
        $this->monnifyService = $monnifyService;
    }

    public function createCheckout()
    {
        $customerEmail = 'customer@example.com';
        $amount = 1000; // Adjust as needed
        $paymentReference = 'your_unique_reference'; // Adjust as needed

        $checkoutUrl = $this->monnifyService->createCheckoutUrl($customerEmail, $amount, $paymentReference);

        // Redirect the user to the checkout URL
        return redirect($checkoutUrl);
    }
}
