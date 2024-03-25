<?php

namespace App\Http\Controllers;

use App\Models\Gateways;
use Illuminate\Http\Request;

class PaymentGatewaysController extends Controller
{
    public function index()
    {
        try {
            $gateways = Gateways::get('requirements');
            return get_success_response($gateways);
        } catch (\Throwable $th) {
            if(getenv('APP_DEBUG') == true) {
                return get_error_response($th->getTrace());
            }
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $gateway = Gateways::whereId($id);
            if($gateway)
                return get_success_response($gateway);

            return get_error_response(['error'=> 'Payment Gateway not found']);
        } catch (\Throwable $th) {
            if(getenv('APP_DEBUG') == true) {
                return get_error_response($th->getTrace());
            }
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
