<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function getShipping(){
        $shipping = ShippingCharge::first();
        return response()->json([
            'status' => 200,
            'data' => $shipping
        ], 200);
    }

    public function updateShipping(Request $request){
        $validator = Validator::make($request->all(),[
            'shipping_charge' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()
            ], 404);
        }

        ShippingCharge::updateOrInsert([
            'id' => 1
        ], [
            'shipping_charge' => $request->shipping_charge
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Shipping saved successfully"
        ], 200);
    }
}
