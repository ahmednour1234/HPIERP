<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->id,
            'type'                   => (int) $this->type,
            'customer_id'            => $this->user_id,
            'seller_id'              => $this->owner_id,
            'order_amount'           => (float) $this->order_amount,
            'total_tax'              => (float) $this->total_tax,
            'extra_discount'         => (float) $this->extra_discount,
            'coupon_code'            => $this->coupon_code,
            'coupon_discount_amount' => (float) $this->coupon_discount_amount,
            'collected_cash'         => (float) $this->collected_cash,

            // حالة التحصيل والمرتجع محسوبة هنا، فلا يعيد كل عميل اشتقاقها
            // بنفسه ويختلف عن الفلترة في GET /orders.
            'remaining'              => $this->remaining(),
            'payment_status'         => $this->paymentStatus(),
            'payment_status_text'    => $this->paymentStatusText(),

            'returned_amount'        => $this->returnedAmount(),
            'has_returns'            => $this->returnedAmount() > 0,

            'cash'                   => (int) $this->cash,
            'payment_id'             => $this->payment_id,
            'img'                    => $this->img,
            'customer'               => $this->whenLoaded('customer', fn () => [
                'id'     => $this->customer->id,
                'name'   => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'details'                => OrderDetailResource::collection($this->whenLoaded('details')),
            'created_at'             => optional($this->created_at)->toIso8601String(),
        ];
    }

    /** ما زال على الفاتورة، ولا ينزل تحت الصفر عند التحصيل الزائد. */
    private function remaining(): float
    {
        return round(max(0, (float) $this->order_amount - (float) $this->collected_cash), 2);
    }

    /**
     * نفس قاعدة فلتر payment_status في GET /orders، فقائمة أُعيد تحميلها
     * بعد التحصيل توافق ما تعرضه الصفوف.
     */
    private function paymentStatus(): string
    {
        $collected = (float) $this->collected_cash;

        if ($collected <= 0) {
            return 'unpaid';
        }

        return $collected >= (float) $this->order_amount ? 'paid' : 'partial';
    }

    private function paymentStatusText(): string
    {
        return [
            'paid'    => 'محصلة بالكامل',
            'partial' => 'محصلة جزئيًا',
            'unpaid'  => 'غير محصلة',
        ][$this->paymentStatus()];
    }

    /**
     * قيمة ما ارتُجع من هذه الفاتورة.
     *
     * القائمة تحسبه في الاستعلام نفسه؛ وحين يغيب — كما في عرض فاتورة
     * واحدة — يُحسب من العلاقة بدل أن يعود صفرًا كاذبًا.
     */
    private function returnedAmount(): float
    {
        if (isset($this->returned_amount)) {
            return round((float) $this->returned_amount, 2);
        }

        return round((float) $this->returnedOrders()->where('type', 7)->sum('order_amount'), 2);
    }
}
