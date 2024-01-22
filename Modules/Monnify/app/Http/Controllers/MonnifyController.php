<?php

namespace Modules\Monnify\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Monnify\App\Services\MonnifyService;

class MonnifyController extends Controller
{
    public function createCheckout(int $quoteId, float $amount, string $currency)
    {
        $moniffy = new MonnifyService;
        $checkoutUrl = $moniffy->createCheckoutUrl($amount, $currency);

        updateSendMoneyRawData($quoteId, $checkoutUrl);
        return $checkoutUrl;
    }

    public function verifyTrans($transRef)
    {
        try {
            $ref = urlencode($transRef);
            $moniffy = new MonnifyService;
            $response = $moniffy->verifyTrans($ref);
            return $response;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }
}
