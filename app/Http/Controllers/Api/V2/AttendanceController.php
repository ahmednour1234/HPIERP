<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Models\Attendance;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read access to the signed-in seller's own attendance.
 *
 * Check in/out stays on the v1 endpoint: its shift matching (midnight-crossing
 * shifts, grace windows, lateness) drives payroll and already validates its
 * input, so there is nothing to gain from re-implementing it here.
 */
class AttendanceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $records = Attendance::where('admin_id', $request->user()->id)
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('id')
            ->paginate(
                (int) $request->input('limit', 25),
                ['*'],
                'page',
                (int) $request->input('offset', 1)
            );

        return $this->ok(AttendanceResource::collection($records), 'Attendance retrieved');
    }
}
