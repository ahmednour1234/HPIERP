<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CollectPaymentRequest;
use App\Http\Requests\Api\V1\PlaceOrderRequest;
use App\Http\Requests\Api\V1\ReturnOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point-of-sale orders. Placing an order writes the order, its lines, the
 * stock movements and the ledger entry in a single database transaction.
 */
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderService $orders)
    {
    }

    /** Validation shared by the listing and its totals. */
    private const FILTER_RULES = [
        'type'           => ['nullable', 'integer', 'in:4,7,12,24'],
        'customer_id'    => ['nullable', 'integer'],
        'product_id'     => ['nullable', 'integer'],
        'account_id'     => ['nullable', 'integer'],
        'cash'           => ['nullable', 'integer', 'in:1,2'],
        'payment_status' => ['nullable', 'string', 'in:paid,partial,unpaid'],
        'min_amount'     => ['nullable', 'numeric', 'min:0'],
        'max_amount'     => ['nullable', 'numeric', 'min:0', 'gte:min_amount'],
        'from'           => ['nullable', 'date'],
        'to'             => ['nullable', 'date', 'after_or_equal:from'],
        'sort'           => ['nullable', 'string', 'in:newest,oldest,amount_asc,amount_desc'],
        'search'         => ['nullable', 'string', 'max:255'],
    ];

    /**
     * The seller's orders. Sales, returns and installments all live here —
     * filter by `type` for one kind.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(self::FILTER_RULES);

        return $this->ok(
            OrderResource::collection($this->orders->list((int) $request->user()->id, $request->all())),
            'Orders retrieved'
        );
    }

    /**
     * Totals for the current filter — what the app shows above the list.
     * Computed over the whole result set, not just the visible page.
     */
    public function totals(Request $request): JsonResponse
    {
        $request->validate(self::FILTER_RULES);

        return $this->ok(
            $this->orders->totals((int) $request->user()->id, $request->all()),
            'Order totals retrieved'
        );
    }

    /** One order with its lines - the invoice payload. */
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            new OrderResource($this->orders->invoice($id, (int) $request->user()->id)),
            'Order retrieved'
        );
    }

    /** A customer's order history. */
    public function forCustomer(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            OrderResource::collection(
                $this->orders->forCustomer($id, (int) $request->user()->id, $request->all())
            ),
            'Customer orders retrieved'
        );
    }

    /**
     * The lines of an invoice with how much of each can still be returned.
     *
     * Call this before filing a return: it is what tells the app which
     * products the customer actually bought and how many are left to give
     * back.
     */
    public function returnable(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            $this->orders->returnableLines($id, (int) $request->user()->id),
            'Returnable lines retrieved'
        );
    }

    /**
     * Record a payment against an invoice.
     *
     * Unlike `customers/add-balance`, this is tied to a document: the
     * invoice's collected_cash moves, so its payment_status follows.
     */
    public function collect(CollectPaymentRequest $request, int $id): JsonResponse
    {
        return $this->ok(
            $this->orders->collectPayment(
                $id,
                (int) $request->user()->id,
                $request->validated(),
                $request->file('img')
            ),
            'Payment collected'
        );
    }

    /** File a return against an invoice. */
    public function storeReturn(ReturnOrderRequest $request): JsonResponse
    {
        return $this->created(
            new OrderResource(
                $this->orders->returnAgainstInvoice((int) $request->user()->id, $request->validated())
            ),
            'Return recorded'
        );
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        return $this->created(
            new OrderResource($this->orders->place(
                (int) $request->user()->id,
                $request->validated(),
                $request->file('img')
            )),
            'Order placed'
        );
    }
}
