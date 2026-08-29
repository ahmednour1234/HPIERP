<?php

namespace App\Services;

use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Seller;
use App\Models\TransactionSeller;
use App\Models\Transection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * A seller handing in the cash they have collected.
 *
 * This is not an account-to-account transfer: the money is physically with the
 * seller and is being deposited into one of the company's accounts. The seller
 * files the deposit; an admin approves it, and only then does the money move.
 *
 * Status values on `transaction_sellers.active`:
 *   0  pending   1  approved   2  rejected
 */
class SellerDepositService
{
    public const PENDING  = 0;
    public const APPROVED = 1;
    public const REJECTED = 2;

    /** `transections.tran_type` used for an approved seller deposit. */
    public const TRAN_TYPE = '26';

    /** The seller's own deposits, newest first. */
    public function listForSeller(int $sellerId, array $filters): LengthAwarePaginator
    {
        return TransactionSeller::where('seller_id', $sellerId)
            ->with('accounts:id,account,account_number')
            ->when(isset($filters['status']) && $filters['status'] !== '',
                fn ($q) => $q->where('active', (int) $filters['status']))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate((int) ($filters['limit'] ?? 25), ['*'], 'page', (int) ($filters['offset'] ?? 1));
    }

    public function findForSeller(int $id, int $sellerId): TransactionSeller
    {
        $deposit = TransactionSeller::with('accounts:id,account,account_number')->findOrFail($id);

        if ((int) $deposit->seller_id !== $sellerId) {
            throw new AuthorizationException('This deposit was not filed by you');
        }

        return $deposit;
    }

    /**
     * File a deposit. It is recorded as pending — no balance moves until an
     * admin approves it, which is what keeps a seller from crediting the
     * company's accounts on their own say-so.
     */
    public function file(int $sellerId, array $data, ?UploadedFile $image = null): TransactionSeller
    {
        // Fails loudly here rather than at insert time.
        $account = Account::findOrFail($data['account_id']);

        return DB::transaction(function () use ($sellerId, $data, $image, $account) {
            $deposit = new TransactionSeller();
            $deposit->seller_id  = $sellerId;
            $deposit->account_id = $account->id;
            $deposit->amount     = (string) $data['amount'];
            $deposit->note       = $data['note'] ?? null;
            // The column is NOT NULL with no default; '' means "no slip yet".
            $deposit->img        = $image ? Helpers::upload('transaction/', 'png', $image) : '';
            $deposit->active     = self::PENDING;
            $deposit->save();

            return $deposit->load('accounts:id,account,account_number');
        });
    }

    /**
     * Approve a pending deposit: credit the account, record the ledger entry
     * and clear the amount off what the seller is holding.
     *
     * All four writes share one transaction, and the account row is locked, so
     * two admins approving at once cannot both read the same starting balance.
     */
    public function approve(int $depositId, ?int $approvedBy = null): TransactionSeller
    {
        return DB::transaction(function () use ($depositId, $approvedBy) {
            $deposit = TransactionSeller::lockForUpdate()->findOrFail($depositId);

            // Approving twice would credit the account twice.
            if ((int) $deposit->active !== self::PENDING) {
                throw new \InvalidArgumentException(
                    'This deposit has already been ' .
                    ((int) $deposit->active === self::APPROVED ? 'approved' : 'rejected') . '.'
                );
            }

            $account = Account::lockForUpdate()->findOrFail($deposit->account_id);
            $amount  = (float) $deposit->amount;

            $transaction = new Transection();
            $transaction->tran_type   = self::TRAN_TYPE;
            $transaction->account_id  = $account->id;
            $transaction->seller_id   = $deposit->seller_id;
            $transaction->amount      = $amount;
            $transaction->description = $deposit->note ?: 'توريد مندوب';
            $transaction->debit       = 1;
            $transaction->credit      = 0;
            $transaction->balance     = $account->balance + $amount;
            $transaction->date        = now()->toDateString();
            $transaction->img         = $deposit->img;
            $transaction->save();

            $account->balance  = $account->balance + $amount;
            $account->total_in = $account->total_in + $amount;
            $account->save();

            // What the seller is still holding comes down by what they handed in.
            $seller = Seller::find($deposit->seller_id);
            if ($seller) {
                // credit is a varchar and may be null; normalise before arithmetic.
                $seller->credit = (string) ((float) $seller->credit - $amount);
                $seller->save();
            }

            $deposit->active = self::APPROVED;
            $deposit->save();

            return $deposit->load('accounts:id,account,account_number');
        });
    }

    /** Reject a pending deposit. Nothing moves. */
    public function reject(int $depositId): TransactionSeller
    {
        $deposit = TransactionSeller::findOrFail($depositId);

        if ((int) $deposit->active !== self::PENDING) {
            throw new \InvalidArgumentException('This deposit is no longer pending.');
        }

        $deposit->active = self::REJECTED;
        $deposit->save();

        return $deposit;
    }

    /** What the seller has filed, grouped by status. */
    public function summaryForSeller(int $sellerId): array
    {
        $rows = TransactionSeller::where('seller_id', $sellerId)
            ->selectRaw('active, COUNT(*) as count, SUM(CAST(amount AS DECIMAL(15,2))) as total')
            ->groupBy('active')
            ->get()
            ->keyBy('active');

        $bucket = fn (int $status) => [
            'count' => (int) ($rows[$status]->count ?? 0),
            'total' => (float) ($rows[$status]->total ?? 0),
        ];

        return [
            'pending'  => $bucket(self::PENDING),
            'approved' => $bucket(self::APPROVED),
            'rejected' => $bucket(self::REJECTED),
        ];
    }
}
