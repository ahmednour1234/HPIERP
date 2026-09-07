<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of a seller's van stock.
 *
 * Unlike the legacy StocksResource this issues no queries of its own — the
 * seller price is already applied by StockService and relations are eager
 * loaded, so rendering a page of rows costs no extra round trips.
 */
class StockResource extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->product;
        $isRefund = (int) $request->input('type') === 7;

        return [
            'stock_id'   => $this->id,
            'refund'     => $isRefund,
            'quantity'   => $isRefund ? 100000 : $this->stock,
            'main_stock' => $this->main_stock,
            'product'    => $product ? [
                'id'            => $product->id,
                'name'          => $product->name,
                'name_en'       => $product->name_en,
                'product_code'  => $product->product_code,
                'unit_type'     => $product->unit_type,
                'unit_value'    => (int) $product->unit_value,
                'category_id'   => $product->category_id,
                'purchase_price'=> (float) $product->purchase_price,
                'selling_price' => (float) $product->selling_price,
                // يظهر فقط حين يُمرَّر customer_id في الطلب.
                'customer_price' => isset($product->customer_price)
                    ? (float) $product->customer_price
                    : null,
                'discount_type' => $product->discount_type,
                'discount'      => (float) $product->discount,
                'tax'           => (float) $product->tax,
                'image'         => $product->image,
            ] : null,
        ];
    }
}
