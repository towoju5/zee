<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Generate deposit link for selected payment gateway.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'gateway' => 'required',
                'amount' => 'required',
                'currency' => 'required'
            ]);

            // record deposit info into the DB
            $deposit = new Deposit();
            $deposit->user_id = active_user();
            $deposit->amount = $request->amount;
            $deposit->gateway = $request->gateway;
            $deposit->currency = $request->currency;
            if($deposit->save()){
                // add transaction history
                // now call the payment endpoint
                $payment = new PaymentService();
                $callback = $payment->makePayment($request->amount, $request->currency, $request->gateway);
                return get_success_response($callback);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
