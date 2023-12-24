<?php

namespace Modules\Beneficiary\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Beneficiary\app\Models\Beneficiary;

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
            $query = Beneficiary::whereUserId(auth()->user()->currentTeam->id)->paginate($per_page);
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
            $data['user_id'] = auth()->user()->currentTeam->id;
            $data['beneficiary'] = $request->beneficiary;

            if($data) {
                return get_success_response($data);
            }
            return get_error_response(['error' => 'Currently unable to add new beneficiaries']);
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }

        return response()->json($this->data);
    }

    /**
     * Show the specified resource.
     */
    public function show($id): JsonResponse
    {
        //

        return response()->json($this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        //

        return response()->json($this->data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        //

        return response()->json($this->data);
    }
}
