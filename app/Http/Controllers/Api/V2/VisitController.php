<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlanVisitRequest;
use App\Http\Requests\Api\V1\RecordVisitResultRequest;
use App\Http\Resources\Api\V1\VisitResource;
use App\Http\Resources\Api\V1\VisitResultResource;
use App\Services\VisitService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    use ApiResponse;

    public function __construct(private VisitService $visits)
    {
    }

    /** Visits planned by the signed-in seller. */
    public function index(Request $request): JsonResponse
    {
        return $this->ok(
            VisitResource::collection(
                $this->visits->planned((int) $request->user()->id, $request->all())
            ),
            'Visits retrieved'
        );
    }

    /** Outcomes this seller has recorded. */
    public function results(Request $request): JsonResponse
    {
        return $this->ok(
            VisitResultResource::collection(
                $this->visits->results((int) $request->user()->id, $request->all())
            ),
            'Visit results retrieved'
        );
    }

    /** Outcomes recorded against one customer. */
    public function customerResults(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            VisitResultResource::collection(
                $this->visits->resultsForCustomer($id, (int) $request->user()->id, $request->all())
            ),
            'Visit results retrieved'
        );
    }

    public function store(PlanVisitRequest $request): JsonResponse
    {
        return $this->created(
            new VisitResource($this->visits->plan((int) $request->user()->id, $request->validated())),
            'Visit planned'
        );
    }

    public function storeResult(RecordVisitResultRequest $request): JsonResponse
    {
        return $this->created(
            new VisitResultResource(
                $this->visits->recordResult((int) $request->user()->id, $request->validated())
            ),
            'Visit result recorded'
        );
    }
}
