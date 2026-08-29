<?php

namespace App\Services;

use App\CPU\Helpers;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->products->search(
            $filters['search'] ?? null,
            isset($filters['category_id']) ? (int) $filters['category_id'] : null,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function find(int $id): Product
    {
        return $this->products->findOrFail($id);
    }

    public function findByCode(string $code): ?Product
    {
        return $this->products->findByCode($code);
    }

    public function lowStock(array $filters): LengthAwarePaginator
    {
        return $this->products->belowStockLimit(
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /**
     * Create a product. This is the endpoint v1 advertised as
     * POST /product/store but never implemented - the route pointed at a
     * PosController::storeProduct that does not exist.
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            if ($image) {
                $data['image'] = Helpers::upload('product/', 'png', $image);
            }

            $data['local_id'] = $data['local_id'] ?? 0;
            $data['type']     = $data['type'] ?? 'product';

            // The tiered price columns are NOT NULL; default them to the base
            // price rather than letting the insert fail.
            foreach (['purchase_price', 'selling_price'] as $base) {
                for ($i = 1; $i <= 4; $i++) {
                    $data[$base . $i] = $data[$base . $i] ?? ($data[$base] ?? 0);
                }
            }

            // refresh(): columns filled by database defaults (limit_stock,
            // limit_web) are absent from the in-memory model, so the response
            // reported 0 for values the database had stored as 10.
            return $this->products->create($data)->refresh();
        });
    }

    public function update(int $id, array $data, ?UploadedFile $image = null): Product
    {
        if ($image) {
            $data['image'] = Helpers::upload('product/', 'png', $image);
        }

        return $this->products->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->products->delete($id);
    }

    /**
     * Prices for a customer across a cart of products, falling back to the
     * catalogue price where none is negotiated.
     *
     * The v1 version looped over $request['cart'] unchecked and fataled with
     * "foreach() argument must be of type array|object, null given" whenever
     * the field was absent.
     */
    public function pricesForCustomer(int $customerId, array $productIds): array
    {
        $overrides = $this->products->customerPrices($customerId, $productIds);
        $catalogue = Product::whereIn('id', $productIds)->pluck('selling_price', 'id')->all();

        $out = [];
        foreach ($productIds as $id) {
            $out[] = [
                'id'    => (int) $id,
                'price' => (float) ($overrides[$id] ?? $catalogue[$id] ?? 0),
                'custom' => isset($overrides[$id]),
            ];
        }

        return $out;
    }

    public function setCustomerPrice(int $customerId, int $productId, float $price): array
    {
        $record = $this->products->setCustomerPrice($customerId, $productId, $price);

        return [
            'customer_id' => (int) $record->customer_id,
            'product_id'  => (int) $record->product_id,
            'price'       => (float) $record->price,
        ];
    }
}
