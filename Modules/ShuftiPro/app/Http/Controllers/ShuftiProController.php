<?php

namespace Modules\ShuftiPro\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShuftiProController extends Controller
{
    public function shuftiPro(Request $request)
    {
        try {
            $request->validate([
                //
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
