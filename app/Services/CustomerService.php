<?php

namespace App\Services;

use App\CPU\Helpers;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(private CustomerRepository $customers)
    {
    }

    public function listForSeller(int $sellerId, array $filters): LengthAwarePaginator
    {
        return $this->customers->forSeller(
            $sellerId,
            $filters['search'] ?? null,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /**
     * Create a customer and attach them to the seller who added them.
     * Both writes share a transaction so a customer can never be created
     * without its seller link.
     */
    public function createForSeller(array $data, int $sellerId, ?UploadedFile $image = null): Customer
    {
        return DB::transaction(function () use ($data, $sellerId, $image) {
            if ($image) {
                $data['image'] = Helpers::upload('customer/', 'png', $image);
            }

            // local_id is the offline-sync identifier and the column is NOT NULL
            // with no default; rows created server-side carry 0.
            $data['local_id'] = $data['local_id'] ?? 0;

            $customer = $this->customers->create($data);
            $this->customers->assignToSeller($customer->id, $sellerId);

            // refresh(): columns filled by database defaults (active, balance,
            // credit) are not on the in-memory model, so the response reported
            // active=false for a customer the database had stored as active.
            // Loading the region lets the resource return its name as well.
            return $customer->refresh()->load('regions');
        });
    }

    public function updateForSeller(int $customerId, array $data, int $sellerId, ?UploadedFile $image = null): Customer
    {
        $this->assertOwnedBySeller($customerId, $sellerId);

        if ($image) {
            $data['image'] = Helpers::upload('customer/', 'png', $image);
        }

        // Same as create: reload so database-filled columns and the region
        // name come back on the updated record.
        return $this->customers->update($customerId, $data)->refresh()->load('regions');
    }

    public function findForSeller(int $customerId, int $sellerId): Customer
    {
        $this->assertOwnedBySeller($customerId, $sellerId);

        return $this->customers->findOrFail($customerId, ['regions']);
    }

    public function deleteForSeller(int $customerId, int $sellerId): void
    {
        $this->assertOwnedBySeller($customerId, $sellerId);

        $this->customers->delete($customerId);
    }

    /**
     * Record a payment against a customer's balance.
     *
     * v1 routed POST /customer/add-balance at a CustomerController::addBalance
     * that was never written, so the endpoint always 500'd. The customer's
     * balance and the account movement are written together in one
     * transaction, which the v1 update_balance did not do.
     */
    public function addBalance(int $customerId, int $sellerId, array $data): array
    {
        $this->assertOwnedBySeller($customerId, $sellerId);

        return DB::transaction(function () use ($customerId, $sellerId, $data) {
            $customer = $this->customers->findOrFail($customerId);
            $account  = \App\Models\Account::findOrFail($data['account_id']);
            $amount   = (float) $data['amount'];

            $transaction = new \App\Models\Transection();
            $transaction->tran_type    = 'Receivable';
            $transaction->account_id   = $account->id;
            $transaction->seller_id    = $sellerId;
            $transaction->customer_id  = $customerId;
            $transaction->amount       = $amount;
            $transaction->description  = $data['description'] ?? null;
            $transaction->debit        = 1;
            $transaction->credit       = 0;
            $transaction->balance      = $account->balance + $amount;
            $transaction->date         = $data['date'];
            $transaction->save();

            $account->balance  = $account->balance + $amount;
            $account->total_in = $account->total_in + $amount;
            $account->save();

            $customer->balance = $customer->balance - $amount;
            $customer->save();

            return [
                'customer_id'     => $customer->id,
                'customer_balance'=> (float) $customer->balance,
                'account_id'      => $account->id,
                'account_balance' => (float) $account->balance,
                'amount'          => $amount,
                'transaction_id'  => $transaction->id,
            ];
        });
    }

    /**
     * A seller may only act on their own customers. The legacy endpoints did
     * not check this, so any authenticated seller could read or modify any
     * customer by guessing an id.
     */
    private function assertOwnedBySeller(int $customerId, int $sellerId): void
    {
        if (!$this->customers->exists($customerId)) {
            throw (new ModelNotFoundException())->setModel(Customer::class, $customerId);
        }

        if (!$this->customers->belongsToSeller($customerId, $sellerId)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'This customer is not assigned to you'
            );
        }
    }
}
