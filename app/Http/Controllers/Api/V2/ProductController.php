<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerPriceRequest;
use App\Http\Requests\Api\V1\SetCustomerPriceRequest;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Products, including the create/update endpoints that v1 advertised but
 * never implemented.
 */
class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(private ProductService $products)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->ok(
            ProductResource::collection($this->products->list($request->only(['search', 'category_id', 'limit', 'offset']))),
            'Products retrieved'
        );
    }

    public function show(int $id): JsonResponse
    {
        return $this->ok(new ProductResource($this->products->find($id)), 'Product retrieved');
    }

    /** Look a product up by its printed/barcoded code. */
    public function byCode(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $product = $this->products->findByCode($request->input('code'));

        if (!$product) {
            return $this->notFound('No product with that code');
        }

        return $this->ok(new ProductResource($product), 'Product retrieved');
    }

    /** Products at or below their stock threshold. */
    public function lowStock(Request $request): JsonResponse
    {
        return $this->ok(
            ProductResource::collection($this->products->lowStock($request->only(['limit', 'offset']))),
            'Low stock products retrieved'
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->modelData(), $request->file('image'));

        return $this->created(new ProductResource($product), 'Product created');
    }

    public function update(UpdateProductRequest $request): JsonResponse
    {
        $product = $this->products->update(
            (int) $request->input('id'),
            $request->modelData(),
            $request->file('image')
        );

        return $this->ok(new ProductResource($product), 'Product updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->products->delete($id);

        return $this->noContent('Product deleted');
    }

    /** Prices for a cart of products for one customer. */
    public function customerPrices(CustomerPriceRequest $request): JsonResponse
    {
        return $this->ok(
            $this->products->pricesForCustomer(
                (int) $request->input('user_id'),
                $request->input('cart')
            ),
            'Prices retrieved'
        );
    }

    /** Set or change a customer's negotiated price for a product. */
    public function setCustomerPrice(SetCustomerPriceRequest $request): JsonResponse
    {
        return $this->ok(
            $this->products->setCustomerPrice(
                (int) $request->input('customer_id'),
                (int) $request->input('product_id'),
                (float) $request->input('price')
            ),
            'Customer price saved'
        );
    }
}
