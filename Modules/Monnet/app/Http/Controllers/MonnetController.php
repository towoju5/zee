<?php

namespace Modules\Monnet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
}
