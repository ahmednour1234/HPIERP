<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FundTransferRequest;
use App\Http\Requests\Api\V1\MoneyMovementRequest;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(private TransactionService $transactions)
    {
    }

    /** The ledger, filterable by type, account, party and date range. */
    /** Validation shared by the listing and its totals. */
    private const FILTER_RULES = [
        'type'        => ['nullable', 'string', 'max:255'],
        'account_id'  => ['nullable', 'integer'],
        'seller_id'   => ['nullable', 'integer'],
        'customer_id' => ['nullable', 'integer'],
        'supplier_id' => ['nullable', 'integer'],
        'order_id'    => ['nullable', 'integer'],
        'direction'   => ['nullable', 'string', 'in:in,out'],
        'cash'        => ['nullable', 'integer', 'in:1,2'],
        'min_amount'  => ['nullable', 'numeric', 'min:0'],
        'max_amount'  => ['nullable', 'numeric', 'min:0', 'gte:min_amount'],
        'from'        => ['nullable', 'date'],
        'to'          => ['nullable', 'date', 'after_or_equal:from'],
        'search'      => ['nullable', 'string', 'max:255'],
    ];

    public function index(Request $request): JsonResponse
    {
        $request->validate(self::FILTER_RULES);

        return $this->ok(
            TransactionResource::collection($this->transactions->list($request->all())),
            'Transactions retrieved'
        );
    }

    /** Money in, money out and the net for the current filter. */
    public function totals(Request $request): JsonResponse
    {
        $request->validate(self::FILTER_RULES);

        return $this->ok(
            $this->transactions->totals($request->all()),
            'Transaction totals retrieved'
        );
    }

    /** The distinct transaction types in use. */
    public function types(): JsonResponse
    {
        return $this->ok($this->transactions->types(), 'Transaction types retrieved');
    }

    /** Move money between two accounts. */
    public function transfer(FundTransferRequest $request): JsonResponse
    {
        return $this->ok(
            $this->transactions->transfer(
                (int) $request->input('account_from_id'),
                (int) $request->input('account_to_id'),
                (float) $request->input('amount'),
                $request->input('description'),
                $request->input('date')
            ),
            'Transfer completed'
        );
    }

    public function expense(MoneyMovementRequest $request): JsonResponse
    {
        return $this->created(
            $this->transactions->expense(
                (int) $request->input('account_id'),
                (float) $request->input('amount'),
                $request->input('description'),
                $request->input('date')
            ),
            'Expense recorded'
        );
    }

    public function income(MoneyMovementRequest $request): JsonResponse
    {
        return $this->created(
            $this->transactions->income(
                (int) $request->input('account_id'),
                (float) $request->input('amount'),
                $request->input('description'),
                $request->input('date')
            ),
            'Income recorded'
        );
    }
}
