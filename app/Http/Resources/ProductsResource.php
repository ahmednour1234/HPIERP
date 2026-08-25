<?php

namespace App\Http\Resources;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $price = \App\Models\SellerPrice::where(['seller_id' => $this->seller_id, 'product_id' => $this->product_id])->first();
        return [
            'id' => $this->id,
            'title' => $this->name,
            'title_en' => $this->name_en,
            'product_code' => $this->product_code,
            'unit_type' => $this->unit_type,
            'unit_value' => (int) $this->unit_value,
            'brand' => Brand::find($this->brand),
            'category_id' => $this->category_id,
            'purchase_price' => $price ? $price->price : $this->purchase_price,
            'selling_price' => $price ? $price->price : $this->selling_price,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'quantity' => $this->quantity,
            'image' => $this->image,
            'supplier' => Supplier::find($this->supplier_id),
          ];
    }
}
