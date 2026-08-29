<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
        protected $fillable = [
        'parent_id',
        'insert_flag',
        'update_flag',
        'coupon_code',
        'coupon_discount_title',

        'user_id',
        'owner_id',
        'total_tax', // Add total_tax to fillable properties
        'order_amount',
        'extra_discount',
        'coupon_discount_amount',
        'collected_cash',
        'type',
        'cash',
        'payment_id',
        'notification',
        'transaction_reference',
        'active',
        'archived_at',
        'archived_by',
        'archive_reason',
        'img'
    ];


    /**
     * `collected_cash` and `transaction_reference` both hold what has been
     * collected against the invoice — the app writes the first, the admin
     * panel's collection screen writes the second, and the invoice list reads
     * both. Keeping them equal on save is what stops an invoice settled in one
     * place reading as unsettled in the other.
     *
     * Whichever value changed in this save wins; when both changed, the
     * explicit `collected_cash` does.
     */
    protected static function booted()
    {
        static::saving(function (self $order) {
            $cashDirty = $order->isDirty('collected_cash');
            $refDirty  = $order->isDirty('transaction_reference');

            if ($cashDirty) {
                $order->transaction_reference = $order->collected_cash;
            } elseif ($refDirty) {
                $order->collected_cash = $order->transaction_reference;
            }
        });
    }
    /** archived_at وقت لا نص، وإلا فشل ->format() عند العرض. */
    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * الفواتير غير المؤرشفة — الوضع الافتراضي لكل قوائم العمل اليومية.
     * الأرشفة وسم لا حذف، فالفاتورة تبقى في التقارير والأرصدة.
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function archivedBy()
    {
        return $this->belongsTo(Seller::class, 'archived_by');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function parentOrder()
{
    return $this->belongsTo(Order::class, 'parent_id');
}
// app/Models/Order.php
public function returnedOrders()
{
    return $this->hasMany(Order::class, 'parent_id');
}


    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
    
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'owner_id');
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class, 'payment_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }


}
