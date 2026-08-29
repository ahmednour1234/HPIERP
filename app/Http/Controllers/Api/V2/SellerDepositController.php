<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SellerDepositRequest;
use App\Http\Resources\Api\V1\SellerDepositResource;
use App\Services\SellerDepositService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cash the seller has collected and is handing in to a company account.
 *
 * Distinct from /transactions/transfer, which moves money between two accounts
 * that both already belong to the company.
 */
class SellerDepositController extends Controller
{
    use ApiResponse;

    public function __construct(private SellerDepositService $deposits)
    {
    }

    /** The seller's own deposits. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $this->ok(
            SellerDepositResource::collection(
                $this->deposits->listForSeller((int) $request->user()->id, $request->all())
            ),
            'Deposits retrieved'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            new SellerDepositResource($this->deposits->findForSeller($id, (int) $request->user()->id)),
            'Deposit retrieved'
        );
    }

    /** Totals by status, for the seller's summary screen. */
    public function summary(Request $request): JsonResponse
    {
        return $this->ok(
            $this->deposits->summaryForSeller((int) $request->user()->id),
            'Deposit summary retrieved'
        );
    }

    /**
     * File a deposit. It lands as pending; an admin approves it before any
     * balance moves.
     */
    public function store(SellerDepositRequest $request): JsonResponse
    {
        return $this->created(
            new SellerDepositResource(
                $this->deposits->file(
                    (int) $request->user()->id,
                    $request->validated(),
                    $request->file('img')
                )
            ),
            'Deposit submitted for approval'
        );
    }
}
