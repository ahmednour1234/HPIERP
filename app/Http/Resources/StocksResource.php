<?php

namespace App\Http\Resources;
use App\Models\Brand;
use App\Models\SellerPrice;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\JsonResource;

class StocksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $price = SellerPrice::where(['seller_id' => $this->seller_id, 'product_id' => $this->product_id])->first();
        $refund = $request->type == 7;
        return [
            'stock_id' => $this->stock_id ?? $this->id,
            'refund' => $refund,
            'id' => $this->product->id,
            'title' => $this->product->name,
            'title_en' => $this->product->name_en,
            'product_code' => $this->product->product_code,
            'unit_type' => $this->product->unit_type,
            'unit_value' => (int) $this->product->unit_value,
            'brand' => Brand::find($this->product->brand),
            'category_id' => $this->product->category_id,
            'purchase_price' => $price ? $price->price : $this->product->purchase_price,
            'selling_price' => $price ? $price->price : $this->product->selling_price,
            'discount_type' => $this->product->discount_type,
            'discount' => $this->product->discount,
            'tax' => $this->product->tax,
            'quantity' => !$refund ? $this->stock : 100000,
            'image' => $this->product->image,
            'supplier' => Supplier::find($this->product->supplier_id),
        ];
    }
}
