<?php

namespace Modules\Beneficiary\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Beneficiary\app\Models\Beneficiary;
use Modules\Monnify\App\Services\MonnifyService;

class BeneficiaryController extends Controller
{
    public array $data = [];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $per_page  = $request->per_page ?? 20;
            $query = Beneficiary::whereUserId(active_user())->paginate($per_page);
            if($query) {
                return get_success_response($query);
            }
            return get_error_response(['error' => 'Currently unable to retrieve beneficiaries']);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validate = $request->validate([
                "mode" => "required",
                "address" => "required",
                "nickname" => "required",
                "currency" => "required",
                "beneficiary" => "required",
                "destination" => "sometimes",
                "payment_object" => "sometimes",
            ]);

            $data['user_id']        = active_user();
            $data['nickname']       = $request->nickname;
            $data['mode']           = $request->mode;
            $data['currency']       = $request->currency;
            $data['address']        = $request->address;
            $data['beneficiary']    = $request->beneficiary;
            $data['payment_object'] = $request->payment_object ?? $request->destination;

            if ($save = Beneficiary::create($data)) {
                if (isApi())
                    return get_success_response($save);

                return $save;
            }
            return get_error_response(['error' => 'Currently unable to add new beneficiaries']);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $beneficiary = Beneficiary::whereUserId(active_user())->where('id', $id)->first();
            if($beneficiary) {
                return get_success_response($beneficiary);
            }
            return get_error_response(['error', "Beneficiary with the provided data not found"]);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validate = $request->validate([
                "mode" => "required",
                "address" => "required",
                "nickname" => "required",
                "currency" => "required",
                "beneficiary" => "required",
                "destination" => "sometimes",
                "payment_object" => "sometimes",
            ]);
            $data = Beneficiary::find($id);
            $data->user_id        = active_user();
            $data->nickname       = $request->nickname;
            $data->mode           = $request->mode;
            $data->currency       = $request->currency;
            $data->address        = $request->address;
            $data->beneficiary    = $request->beneficiary;
            $data->payment_object = $request->payment_object ?? $request->destination;

            if ($data->save()) {
                if (isApi())
                    return get_success_response($data);

                return $data;
            }
            return get_error_response(['error' => 'Currently unable to update beneficiary']);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $beneficiary = Beneficiary::whereUserId(active_user())->where('id', $id)->first();
            if ($beneficiary->delete()) {
                return get_success_response(['msg' => 'Beneficiary deleted successfully']);
            }
            return get_error_response(['error', "Beneficiary with the provided data not found"]);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }
}
