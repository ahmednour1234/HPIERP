<?php

namespace App\Repositories;

use App\Models\CustomerPrice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /** Catalogue search across name, English name and product code. */
    public function search(?string $term = null, ?int $categoryId = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = $this->query();

        if ($term !== null && $term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('name_en', 'LIKE', "%{$term}%")
                  ->orWhere('product_code', 'LIKE', "%{$term}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByCode(string $code): ?Product
    {
        return $this->query()->where('product_code', $code)->first();
    }

    /** Products below their configured stock threshold. */
    public function belowStockLimit(int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        // whereColumn, not a raw string: the legacy version passed the column
        // name as a bound value and threw "Illegal operator and value combination".
        return $this->query()
            ->whereColumn('quantity', '<=', 'limit_stock')
            ->orderBy('quantity')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** Negotiated prices for one customer, keyed by product id. */
    public function customerPrices(int $customerId, array $productIds): array
    {
        return CustomerPrice::where('customer_id', $customerId)
            ->whereIn('product_id', $productIds)
            ->pluck('price', 'product_id')
            ->all();
    }

    public function setCustomerPrice(int $customerId, int $productId, float $price): CustomerPrice
    {
        return CustomerPrice::updateOrCreate(
            ['customer_id' => $customerId, 'product_id' => $productId],
            // local_id is NOT NULL with no default; server-side rows carry 0.
            ['price' => $price, 'local_id' => 0]
        );
    }
}
