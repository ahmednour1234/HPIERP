<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SalaryResource;
use App\Services\SalaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in seller's own payslips.
 */
class SalaryController extends Controller
{
    use ApiResponse;

    public function __construct(private SalaryService $salaries)
    {
    }

    /**
     * One month's payslip.
     *
     * Accepts `?month=2026-08`, or `?month=8&year=2026`. With neither it
     * answers for the month just gone.
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'string', 'max:7'],
            'year'  => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $month = $this->salaries->normaliseMonth($request->input('month'), $request->input('year'));

        $salary = $this->salaries->forMonth((int) $request->user()->id, $month);

        if (!$salary) {
            // Not an error: the admin has simply not entered this month yet.
            // A 404 would read as "no such endpoint" to a client.
            return $this->ok(
                ['month' => $month, 'salary' => null],
                'No salary recorded for this month'
            );
        }

        return $this->ok(new SalaryResource($salary), 'Salary retrieved');
    }

    /** Recent payslips, newest first. */
    public function history(Request $request): JsonResponse
    {
        $request->validate(['limit' => ['nullable', 'integer', 'between:1,36']]);

        return $this->ok(
            SalaryResource::collection(
                $this->salaries->history((int) $request->user()->id, (int) $request->input('limit', 12))
            ),
            'Salary history retrieved'
        );
    }
}
