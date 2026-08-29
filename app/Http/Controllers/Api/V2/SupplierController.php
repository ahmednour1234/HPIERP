<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\SupplierPaymentRequest;
use App\Http\Requests\Api\V1\SupplierRequest;
use App\Http\Resources\Api\V1\SupplierResource;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends CrudController
{
    protected string $resource = SupplierResource::class;
    protected string $request  = SupplierRequest::class;
    protected string $label    = 'Supplier';

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    /** Suppliers in a city. */
    public function byCity(Request $request): JsonResponse
    {
        $request->validate(['city' => ['required', 'string']]);

        return $this->ok(
            SupplierResource::collection(
                $this->service->inCity($request->input('city'), $request->only(['limit', 'offset']))
            ),
            'Suppliers retrieved'
        );
    }

    /** A supplier's ledger, optionally bounded by date. */
    public function transactions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $this->ok(
            TransactionResource::collection(
                $this->service->transactions($id, $request->only(['from', 'to', 'limit', 'offset']))
            ),
            'Transactions retrieved'
        );
    }

    /** Pay down a supplier's due amount. */
    public function pay(SupplierPaymentRequest $request): JsonResponse
    {
        return $this->ok(
            $this->service->pay(
                (int) $request->input('supplier_id'),
                (int) $request->input('account_id'),
                (float) $request->input('amount'),
                $request->input('description')
            ),
            'Payment recorded'
        );
    }
}
