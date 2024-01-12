<?php

namespace Modules\ShuftiPro\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ShuftiPro\app\Services\ShuftiProServices;

class ShuftiProController extends Controller
{
    public function shuftiPro(Request $request)
    {
        try {
            $shufti = new ShuftiProServices();
            $process = $shufti->init(request());
            return $process;
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $shufti = new ShuftiProServices();
            $process = $shufti->callback(request());
            return $process;
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    
}
