<?php

namespace Modules\Monnet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Beneficiary\app\Models\Beneficiary;
use Modules\Monnet\app\Services\MonnetServices;
use Modules\Monnify\App\Services\MonnifyService;

class MonnetController extends Controller
{
    public function payin_webhook(Request $request)
    {
        return http_response_code(200);
    }

    public function payout_webhook(Request $request)
    {
        return http_response_code(200);
    }
    
    public function success(Request $request)
    {
        return http_response_code(200);
    }

    public function failed(Request $request)
    {
        return http_response_code(200);
    }

    public function payout(Request $request)
    {
        $monnet = new MonnetServices();
        $beneficiaryId = $request->beneficiary_id;

        $beneficiary = Beneficiary::whereId($beneficiaryId)->whereUserId(auth()->id())->first();
        if (!$beneficiary) {
            return get_error_response(['error' => "Beneficiary not found"]);
        }

        $checkout = $monnet->payout($request->amount, $request->currency, $beneficiaryId );
        return $checkout;
    }
}
