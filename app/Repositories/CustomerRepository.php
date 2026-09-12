<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\SellerCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Customers assigned to a seller, with per-customer counts computed in
     * SQL. Paginated — the legacy endpoint fetched every row.
     */
    /**
     * @param array $filters category_id (تخصص طبي) و region_ids (مصفوفة)
     */
    public function forSeller(
        int $sellerId,
        ?string $search = null,
        int $perPage = 25,
        int $page = 1,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->query()
            ->join('seller_customers', 'customers.id', '=', 'seller_customers.customer_id')
            ->where('seller_customers.seller_id', $sellerId)
            ->with('regions:id,name')
            ->select('customers.*')
            ->selectSub(
                fn ($q) => $q->from('orders')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('orders.user_id', 'customers.id')
                    ->where('orders.type', 4),
                'order_count'
            )
            ->selectSub(
                fn ($q) => $q->from('result_visitors')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('result_visitors.customer_id', 'customers.id'),
                'executed_visits'
            );

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('customers.name', 'LIKE', "%{$search}%")
                  ->orWhere('customers.mobile', 'LIKE', "%{$search}%")
                  ->orWhere('customers.pharmacy_name', 'LIKE', "%{$search}%");
            });
        }

        // التخصص الطبي مخزَّن في category_id (categories.type = 0).
        // specialist عمود مختلف يحمل نوع الجهة (صيدلية/مركز/مستشفى/طبيب).
        if (!empty($filters['category_id'])) {
            $query->whereIn('customers.category_id', (array) $filters['category_id']);
        }

        // المنطقة تقبل أكثر من قيمة، فتُمرَّر مصفوفة دائمًا.
        if (!empty($filters['region_ids'])) {
            $query->whereIn('customers.region_id', (array) $filters['region_ids']);
        }

        // نوع الجهة: 1 صيدلية، 2 مركز، 3 مستشفى، 4 طبيب. يعمل مع
        // category_id بـ AND كبقية الفلاتر، فيمكن طلب أطباء تخصص بعينه.
        if (!empty($filters['specialist'])) {
            $query->whereIn('customers.specialist', (array) $filters['specialist']);
        }

        return $query->orderByDesc('customers.id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function assignToSeller(int $customerId, int $sellerId): void
    {
        SellerCustomer::firstOrCreate(
            ['customer_id' => $customerId, 'seller_id' => $sellerId],
            ['local_id' => 0] // NOT NULL with no default; server-side rows use 0
        );
    }

    public function belongsToSeller(int $customerId, int $sellerId): bool
    {
        return SellerCustomer::where('customer_id', $customerId)
            ->where('seller_id', $sellerId)
            ->exists();
    }
}
