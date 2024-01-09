<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserMetaRequest;
use App\Http\Requests\UpdateUserMetaRequest;
use App\Models\UserMeta;
use Illuminate\Http\Request;

class UserMetaController extends Controller
{
    public function index()
    {
        try {
            $userMeta = UserMeta::all();
            return get_success_response($userMeta);
        } catch (\Exception $e) {
            return get_error_response(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $userMeta = UserMeta::findOrFail($id);
            return get_success_response($userMeta);
        } catch (\Exception $e) {
            return get_error_response(['error' => $e->getMessage()], 404);
        }
    }

    public function store(StoreUserMetaRequest $request)
    {
        try {
            $userMeta = UserMeta::create($request->all());
            return get_success_response($userMeta, 201);
        } catch (\Exception $e) {
            return get_error_response(['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateUserMetaRequest $request, $id)
    {
        try {
            $userMeta = UserMeta::findOrFail($id);
            $userMeta->update($request->all());
            return get_success_response($userMeta);
        } catch (\Exception $e) {
            return get_error_response(['error' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $userMeta = UserMeta::findOrFail($id);
            $userMeta->delete();
            return get_success_response(['msg' => "Data deleted successfully"], 204);
        } catch (\Exception $e) {
            return get_error_response(['error' => $e->getMessage()], 404);
        }
    }
}
