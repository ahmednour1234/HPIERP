<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\Transection;
use App\Repositories\SupplierRepository;
use App\Services\Exceptions\InsufficientBalanceException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierService extends CrudService
{
    protected ?string $imageFolder = 'supplier';

    public function __construct(SupplierRepository $repository)
    {
        $this->repository = $repository;
    }

    public function inCity(string $city, array $filters): LengthAwarePaginator
    {
        return $this->repository->inCity(
            $city,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function transactions(int $supplierId, array $filters): LengthAwarePaginator
    {
        // Fails loudly if the supplier does not exist, rather than returning
        // an empty ledger that looks like a supplier with no history.
        $this->repository->findOrFail($supplierId);

        return $this->repository->transactions(
            $supplierId,
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /**
     * Pay down a supplier's due amount from an account.
     *
     * The v1 version wrote two transactions and touched two accounts with no
     * database transaction, and answered `success: true` when the account had
     * insufficient funds - so a failed payment read as a successful one.
     */
    public function pay(int $supplierId, int $accountId, float $amount, ?string $description = null): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->repository->findOrFail($supplierId);
        $account  = Account::findOrFail($accountId);

        if ($account->balance < $amount) {
            throw new InsufficientBalanceException(
                'The account balance is not enough for this payment.'
            );
        }

        return DB::transaction(function () use ($supplier, $account, $amount, $description) {
            $transaction = new Transection();
            $transaction->tran_type   = 'Expense';
            $transaction->account_id  = $account->id;
            $transaction->supplier_id = $supplier->id;
            $transaction->amount      = $amount;
            $transaction->description = $description ?: 'Supplier due payment';
            $transaction->debit       = 1;
            $transaction->credit      = 0;
            $transaction->balance     = $account->balance - $amount;
            $transaction->date        = now()->toDateString();
            $transaction->save();

            $account->balance   = $account->balance - $amount;
            $account->total_out = $account->total_out + $amount;
            $account->save();

            $supplier->due_amount = max(0, (float) $supplier->due_amount - $amount);
            $supplier->save();

            return [
                'supplier_id'     => $supplier->id,
                'due_amount'      => (float) $supplier->due_amount,
                'account_id'      => $account->id,
                'account_balance' => (float) $account->balance,
                'paid'            => $amount,
                'transaction_id'  => $transaction->id,
            ];
        });
    }
}
