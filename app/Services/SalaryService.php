<?php

namespace App\Services;

use App\Models\Salary;
use Illuminate\Support\Carbon;

/**
 * A seller's own payslips.
 *
 * The `salaries` table stores `month` as `YYYY-MM` and every money column as a
 * varchar, so everything is normalised here rather than in the resource.
 *
 * `total` is written by the admin form; it is not derived from the other
 * columns at read time, so a payslip always reports the figure the admin
 * actually approved. The admin form computes it as:
 *
 *     salary + transport_amount + salary_of_visitors + other - discount
 *
 * Commission is recorded but deliberately excluded from that sum, so it is
 * exposed as its own field rather than folded into the net.
 */
class SalaryService
{
    /** One month's payslip, or null when nothing has been entered yet. */
    public function forMonth(int $sellerId, ?string $month = null, ?string $year = null): ?Salary
    {
        return Salary::where('seller_id', $sellerId)
            ->where('month', $this->normaliseMonth($month, $year))
            ->latest('id')
            ->first();
    }

    /** The seller's payslips, newest first. */
    public function history(int $sellerId, int $limit = 12): array
    {
        return Salary::where('seller_id', $sellerId)
            ->orderByDesc('month')
            ->limit(max(1, min($limit, 36)))
            ->get()
            ->all();
    }

    /**
     * Accept `2026-08`, or a separate `month=8&year=2026`, or nothing.
     *
     * v1 matched the raw input against the column, so `month=8` silently
     * returned an empty list forever — the column holds `2026-08`.
     */
    public function normaliseMonth(?string $month, ?string $year = null): string
    {
        $month = $month !== null ? trim($month) : null;

        if ($month !== null && $month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        if ($month !== null && $month !== '' && ctype_digit($month)) {
            $y = ($year !== null && ctype_digit(trim($year)))
                ? (int) $year
                : (int) Carbon::now()->format('Y');

            return sprintf('%04d-%02d', $y, (int) $month);
        }

        // No usable input: the month just gone, which is the one a seller
        // is normally asking about.
        return Carbon::now()->subMonthNoOverflow()->format('Y-m');
    }
}
