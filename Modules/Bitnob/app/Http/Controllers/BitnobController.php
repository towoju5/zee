<?php

namespace Modules\Bitnob\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BitnobController extends Controller
{
    public function reg_user(Request $request)
    {
        try {
            $user = $request->user();
            $data = [
                'customerEmail'     => $user->email,
                'idNumber'          => $user->idNumber,
                'idType'            => $user->idType,
                'firstName'         => $user->firstName,
                'lastName'          => $user->lastName,
                'phoneNumber'       => $user->phoneNumber,
                'city'              => $user->city,
                'state'             => $user->state,
                'country'           => $user->country,
                'zipCode'           => $user->zipCode,
                'line1'             => $user->street,
                'houseNumber'       => $user->houseNumber,
                'idImage'           => $user->verificationDocument,
            ];
            $result = app('bitnob')->regUser($data);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }

    public function createCard(Request $request)
    {
        try {
            $data = [
                'customerEmail' => $request->user()->email,
                'cardBrand'     => 'visa', // cardBrand should be "visa" or "mastercard"
                'cardType'      => 'virtual',
                'reference'     => uuid(),
                'amount'        => $request->amount,
            ];
            $result = app('bitnob')->create($data);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }

    public function topupCard(Request $request, $cardId)
    {
        try {
            $arr = [
                'cardId'    => $cardId,
                'reference' => uuid(),
                'amount'    => $request->amount,
            ];
            $result = app('bitnob')->topup($arr);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }

    public function freeze_unfreeze($action, $cardId)
    {
        /**
         * Freeze or unfreeze card
         */
        try {
            if($action != 'freeze' AND $action != 'unfreeze') return get_error_response(['error' => 'Invalid action type']);
            $result = app('bitnob')->action($action, $cardId);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }

    public function getCard($cardId)
    {
        try {
            $result = app('bitnob')->getCard($cardId);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }

    public function transactions(Request $request, $cardId)
    {
        try {
            $result = app('bitnob')->getTransaction($cardId);
            if($result) {
                return get_success_response($result);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $result]);
        }
    }
}