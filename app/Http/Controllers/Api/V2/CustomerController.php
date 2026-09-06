<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddBalanceRequest;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Services\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private CustomerService $customers)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $customers = $this->customers->listForSeller(
            (int) $request->user()->id,
            $request->only(['search', 'limit', 'offset', 'category_id', 'region_ids'])
        );

        return $this->ok(
            CustomerResource::collection($customers),
            'Customers retrieved'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customer = $this->customers->findForSeller($id, (int) $request->user()->id);

        return $this->ok(new CustomerResource($customer), 'Customer retrieved');
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customers->createForSeller(
            $request->modelData(),
            (int) $request->user()->id,
            $request->file('image')
        );

        return $this->created(new CustomerResource($customer), 'Customer created');
    }

    public function update(UpdateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customers->updateForSeller(
            (int) $request->input('id'),
            $request->modelData(),
            (int) $request->user()->id,
            $request->file('image')
        );

        return $this->ok(new CustomerResource($customer), 'Customer updated');
    }

    /** Record a payment against the customer's balance. */
    public function addBalance(AddBalanceRequest $request): JsonResponse
    {
        return $this->ok(
            $this->customers->addBalance(
                (int) $request->input('customer_id'),
                (int) $request->user()->id,
                $request->validated()
            ),
            'Balance updated'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->customers->deleteForSeller($id, (int) $request->user()->id);

        return $this->noContent('Customer deleted');
    }
}
