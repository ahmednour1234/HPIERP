<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\SellerCategory;
use App\Models\SellerPrice;
use App\Models\Stock;
use App\Models\StockOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StockRepository extends BaseRepository
{
    public function __construct(Stock $model)
    {
        parent::__construct($model);
    }

    /** A seller's own stock rows, optionally narrowed to one category. */
    public function forSeller(int $sellerId, int $categoryId = 0, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('seller_id', $sellerId)
            ->with(['product' => fn ($q) => $q->orderBy('name', 'asc')]);

        if ($categoryId !== 0) {
            $query->whereIn('product_id', Product::where('category_id', $categoryId)->pluck('id'));
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Catalogue products the seller is allowed to sell, i.e. those in the
     * categories assigned to them.
     */
    public function catalogueForSeller(
        int $sellerId,
        int $categoryId = 0,
        ?string $search = null,
        int $perPage = 10,
        int $page = 1
    ): LengthAwarePaginator {
        $categoryIds = $categoryId !== 0
            ? [$categoryId]
            : SellerCategory::where('seller_id', $sellerId)->pluck('cat_id');

        $query = Product::whereIn('category_id', $categoryIds);

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('product_code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage, ['*'], 'page', $page);
    }

    /** Seller-specific price overrides, keyed by product id. */
    public function priceOverrides(int $sellerId, array $productIds): array
    {
        return SellerPrice::where('seller_id', $sellerId)
            ->whereIn('product_id', $productIds)
            ->pluck('price', 'product_id')
            ->all();
    }

    /** Rows where the seller has sold something (main_stock differs from stock). */
    public function movedForSeller(int $sellerId): Collection
    {
        return $this->query()->where('seller_id', $sellerId)->whereColumn('main_stock', '!=', 'stock')->get();
    }

    /** Rows the seller has not touched. */
    public function untouchedForSeller(int $sellerId): Collection
    {
        return $this->query()->where('seller_id', $sellerId)->whereColumn('main_stock', '=', 'stock')->get();
    }

    public function clearForSeller(int $sellerId): void
    {
        $this->query()->where('seller_id', $sellerId)->delete();
    }

    /** Settlement history, newest first. */
    public function settlements(int $sellerId): Collection
    {
        StockOrder::whereNull('statistcs')->delete();

        return StockOrder::where('seller_id', $sellerId)->latest()->get();
    }

    /** Next settlement id, following the legacy 30000000-based sequence. */
    public function nextSettlementId(): int
    {
        $candidate = 30000000 + StockOrder::count() + 1;

        if (StockOrder::find($candidate)) {
            $candidate = (int) StockOrder::orderBy('id', 'DESC')->first()->id + 1;
        }

        return $candidate;
    }
}
