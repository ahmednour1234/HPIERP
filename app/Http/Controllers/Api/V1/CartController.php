<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    /**
     * @param $id
     * @return JsonResponse
     */
    public function addToCart($id): JsonResponse
    {
        $product = DB::table('products')->where('id', $id)->first();
        $order_details = DB::table('order_details')->where('product_id', $id)->first();
        return response()->json([
            'success' => true, 'message' => "You Product", 'product' => $product, 'order_details' => $order_details
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function removeCart(Request $request, $id = null): JsonResponse
    {
        // The route is declared without an {id} segment, so the parameter was
        // never supplied and every call died with "Too few arguments". Fall
        // back to the request body, which is how clients actually send it.
        $id = $id ?? $request->input('id');

        if (!$id) {
            return response()->json([
                'errors' => [['code' => 'id', 'message' => 'A cart item id is required.']],
            ], 403);
        }

        DB::table('poss')->where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully',
        ], 200);
    }
}
