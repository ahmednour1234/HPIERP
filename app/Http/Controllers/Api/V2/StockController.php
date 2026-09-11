<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StockReturnRequest as StockReturnFormRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\StockResource;
use App\Http\Resources\Api\V1\StockReturnRequestResource;
use App\Services\StockReturnService;
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

    public function __construct(
        private StockService $stocks,
        private StockReturnService $returns
    ) {
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

    /**
     * طلب إرجاع ما تبقّى في العربية إلى المخزن.
     *
     * كان ينفّذ التسوية فورًا ولا رجعة فيه؛ صار يسجّل طلبًا معلّقًا
     * بالأصناف التي حددها المندوب، ولا يتحرك المخزون إلا باعتماد الأدمن.
     */
    public function confirm(StockReturnFormRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->created(
            new StockReturnRequestResource(
                $this->returns->request(
                    (int) $request->user()->id,
                    $data['items'],
                    $data['note'] ?? null
                )
            ),
            'Stock return request submitted for approval'
        );
    }

    /**
     * آخر طلب إرجاع للمندوب: المعلّق إن وُجد، وإلا آخر ما رُوجع.
     *
     * يرد data = null حين لا يوجد أي طلب، فالتطبيق يعرض فورم الاختيار.
     */
    public function currentReturn(Request $request): JsonResponse
    {
        $current = $this->returns->current((int) $request->user()->id);

        if (!$current) {
            // ok(null) يحذف المفتاح كليًا، والتطبيق يتوقع data موجودة
            // بقيمة null ليعرف أنه لا يوجد طلب.
            return response()->json([
                'success' => true,
                'message' => 'No stock return request',
                'data'    => null,
            ]);
        }

        return $this->ok(new StockReturnRequestResource($current), 'Stock return request retrieved');
    }

    /** سحب طلب معلّق قبل أن يراجعه الأدمن. */
    public function cancelReturn(Request $request, int $id): JsonResponse
    {
        $this->returns->cancel((int) $request->user()->id, $id);

        return $this->ok(['id' => $id], 'Stock return request cancelled');
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
