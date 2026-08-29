<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile invoices whose two collection columns disagree.
 *
 * `collected_cash` (written by the app and the v2 API) and
 * `transaction_reference` (written by the admin collection screen) both hold
 * what was collected. Rows written before they were kept in step can differ,
 * which is why an invoice settled in the app showed as unsettled in the panel.
 *
 * The larger of the two wins: a collection recorded in one place is real, and
 * the other column simply never heard about it.
 */
class SyncOrderCollections extends Command
{
    protected $signature = 'orders:sync-collections {--dry-run : List the rows without changing them}';

    protected $description = 'Reconcile collected_cash and transaction_reference on orders';

    public function handle(): int
    {
        $mismatched = Order::whereColumn('collected_cash', '!=', 'transaction_reference')
            ->orWhere(function ($q) {
                $q->whereNull('collected_cash')->whereNotNull('transaction_reference');
            })
            ->orWhere(function ($q) {
                $q->whereNull('transaction_reference')->whereNotNull('collected_cash');
            })
            ->get(['id', 'order_amount', 'collected_cash', 'transaction_reference']);

        if ($mismatched->isEmpty()) {
            $this->info('Nothing to reconcile — every invoice already agrees.');
            return self::SUCCESS;
        }

        $this->warn($mismatched->count() . ' invoice(s) disagree:');

        $rows = $mismatched->map(fn ($o) => [
            $o->id,
            (float) $o->order_amount,
            (float) $o->collected_cash,
            (float) $o->transaction_reference,
            max((float) $o->collected_cash, (float) $o->transaction_reference),
        ]);

        $this->table(
            ['id', 'total', 'collected_cash', 'transaction_reference', 'will become'],
            $rows->take(20)
        );

        if ($mismatched->count() > 20) {
            $this->line('... and ' . ($mismatched->count() - 20) . ' more');
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing was changed.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($mismatched) {
            foreach ($mismatched as $order) {
                $settled = max((float) $order->collected_cash, (float) $order->transaction_reference);

                // Update directly: going through the model would fire the
                // saving hook and copy one column onto the other before the
                // larger value has been chosen.
                Order::where('id', $order->id)->update([
                    'collected_cash'        => $settled,
                    'transaction_reference' => $settled,
                ]);
            }
        });

        $this->info('Reconciled ' . $mismatched->count() . ' invoice(s).');

        return self::SUCCESS;
    }
}
