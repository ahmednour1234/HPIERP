<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\StockResource;
use App\Services\StockService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock endpoints, refactored onto the service/repository split and the
 * standard response envelope. The v1 controller is left in place so existing
 * clients keep working.
 */
class StockController extends Controller
{
    use ApiResponse;

    public function __construct(private StockService $stocks)
    {
    }

    /** The seller's van stock (type=4) or their sellable catalogue. */
    public function index(Request $request): JsonResponse
    {
        $result = $this->stocks->listing(
            (int) $request->user()->id,
            $request->only(['limit', 'offset', 'category_id', 'type', 'search', 'customer_id'])
        );

        $paginator = $result['paginator'];
        $collection = $result['type'] === 'stocks'
            ? StockResource::collection($paginator)
            : ProductResource::collection($paginator);

        return $this->ok($collection, 'Stock list retrieved');
    }

    /** Close out the day and return the settlement summary. */
    public function confirm(Request $request): JsonResponse
    {
        $seller = $request->user();

        $summary = $this->stocks->confirm((int) $seller->id, $seller->vehicle_code);

        return $this->ok($summary, 'Stocks confirmed successfully');
    }

    /** Past settlements for this seller. */
    public function history(Request $request): JsonResponse
    {
        return $this->ok(
            $this->stocks->history((int) $request->user()->id),
            'Stock history retrieved'
        );
    }
}
