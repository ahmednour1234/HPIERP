<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private DashboardService $dashboard)
    {
    }

    /** Headline figures for the signed-in seller. */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $this->ok(
            $this->dashboard->summary(
                (int) $request->user()->id,
                $request->input('from'),
                $request->input('to')
            ),
            'Dashboard summary retrieved'
        );
    }

    /** Sales per month, for a chart. */
    public function monthlyRevenue(Request $request): JsonResponse
    {
        $request->validate(['months' => ['nullable', 'integer', 'between:1,36']]);

        return $this->ok(
            $this->dashboard->monthlyRevenue(
                (int) $request->user()->id,
                (int) $request->input('months', 12)
            ),
            'Monthly revenue retrieved'
        );
    }

    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'between:1,50'],
            'from'  => ['nullable', 'date'],
            'to'    => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $this->ok(
            $this->dashboard->topProducts(
                (int) $request->user()->id,
                (int) $request->input('limit', 10),
                $request->input('from'),
                $request->input('to')
            ),
            'Top products retrieved'
        );
    }

    public function lowStock(Request $request): JsonResponse
    {
        return $this->ok(
            $this->dashboard->lowStock((int) $request->user()->id),
            'Low stock retrieved'
        );
    }
}
