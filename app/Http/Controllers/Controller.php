<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function vehicleCode($id)
    {
        $vehicle = \App\Models\Store::where('store_id', $id)->first();
        $code = $vehicle->store_code;
        // dd($code);
        return $code;
    }
}
