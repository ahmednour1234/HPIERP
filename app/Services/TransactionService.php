<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transection;
use App\Repositories\TransactionRepository;
use App\Services\Exceptions\InsufficientBalanceException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(private TransactionRepository $transactions)
    {
    }

    /** Money in / out / net for the same filters the listing used. */
    public function totals(array $filters): array
    {
        return $this->transactions->totals($filters);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->transactions->listing(
            $filters,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function types(): array
    {
        return $this->transactions->types();
    }

    /**
     * Move money between two accounts.
     *
     * Both legs and both balance updates share one database transaction. The
     * v1 version wrote them unwrapped, so a failure after the first leg
     * removed money from one account without crediting the other. It also
     * reported insufficient funds as HTTP 203, which most clients treat as
     * success.
     */
    public function transfer(int $fromId, int $toId, float $amount, string $description, string $date): array
    {
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Choose two different accounts.');
        }

        return DB::transaction(function () use ($fromId, $toId, $amount, $description, $date) {
            // Lock both rows so two concurrent transfers cannot both pass the
            // balance check and overdraw the account.
            $from = Account::lockForUpdate()->findOrFail($fromId);
            $to   = Account::lockForUpdate()->findOrFail($toId);

            if ($from->balance < $amount) {
                throw new InsufficientBalanceException('The source account does not have enough balance.');
            }

            $out = $this->record('Transfer', $from->id, $amount, $description, $date, 1, 0, $from->balance - $amount);
            $from->balance   = $from->balance - $amount;
            $from->total_out = $from->total_out + $amount;
            $from->save();

            $in = $this->record('Transfer', $to->id, $amount, $description, $date, 0, 1, $to->balance + $amount);
            $to->balance  = $to->balance + $amount;
            $to->total_in = $to->total_in + $amount;
            $to->save();

            return [
                'from' => ['account_id' => $from->id, 'balance' => (float) $from->balance, 'transaction_id' => $out->id],
                'to'   => ['account_id' => $to->id,   'balance' => (float) $to->balance,   'transaction_id' => $in->id],
                'amount' => $amount,
            ];
        });
    }

    /** Record an expense against an account. */
    public function expense(int $accountId, float $amount, string $description, string $date): array
    {
        return DB::transaction(function () use ($accountId, $amount, $description, $date) {
            $account = Account::lockForUpdate()->findOrFail($accountId);

            if ($account->balance < $amount) {
                throw new InsufficientBalanceException('The account does not have enough balance.');
            }

            $transaction = $this->record(
                'Expense', $account->id, $amount, $description, $date, 1, 0, $account->balance - $amount
            );

            $account->balance   = $account->balance - $amount;
            $account->total_out = $account->total_out + $amount;
            $account->save();

            return [
                'transaction_id'  => $transaction->id,
                'account_id'      => $account->id,
                'account_balance' => (float) $account->balance,
                'amount'          => $amount,
            ];
        });
    }

    /** Record income against an account. */
    public function income(int $accountId, float $amount, string $description, string $date): array
    {
        return DB::transaction(function () use ($accountId, $amount, $description, $date) {
            $account = Account::lockForUpdate()->findOrFail($accountId);

            $transaction = $this->record(
                'Income', $account->id, $amount, $description, $date, 0, 1, $account->balance + $amount
            );

            $account->balance  = $account->balance + $amount;
            $account->total_in = $account->total_in + $amount;
            $account->save();

            return [
                'transaction_id'  => $transaction->id,
                'account_id'      => $account->id,
                'account_balance' => (float) $account->balance,
                'amount'          => $amount,
            ];
        });
    }

    private function record(
        string $type, int $accountId, float $amount, ?string $description,
        string $date, int $debit, int $credit, float $balance
    ): Transection {
        $transaction = new Transection();
        $transaction->tran_type   = $type;
        $transaction->account_id  = $accountId;
        $transaction->amount      = $amount;
        $transaction->description = $description;
        $transaction->debit       = $debit;
        $transaction->credit      = $credit;
        $transaction->balance     = $balance;
        $transaction->date        = $date;
        $transaction->save();

        return $transaction;
    }
}
