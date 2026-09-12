<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlaceReservationRequest;
use App\Http\Resources\Api\V1\ReservationResource;
use App\Services\ReservationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock requests a seller files from the app — asking the warehouse for goods,
 * or asking to send some back.
 */
class ReservationController extends Controller
{
    use ApiResponse;

    public function __construct(private ReservationService $reservations)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type'        => ['nullable', 'in:4,7'],
            'active'      => ['nullable', 'integer', 'in:0,1'],
            'customer_id' => ['nullable', 'integer'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
            'search'      => ['nullable', 'string', 'max:255'],
        ]);

        return $this->ok(
            ReservationResource::collection(
                $this->reservations->listForSeller((int) $request->user()->id, $request->all())
            ),
            'Reservations retrieved'
        );
    }

    /**
     * أوامر الصرف التي نُفِّذت لهذا المندوب.
     *
     * ما صرفه الأدمن فعلًا إلى العربية، لا ما طلبه المندوب — تلك في
     * GET /reservations.
     */
    public function issued(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['nullable', 'integer'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
            'search'     => ['nullable', 'string', 'max:255'],
        ]);

        return $this->ok(
            ReservationResource::collection(
                $this->reservations->issuedToSeller((int) $request->user()->id, $request->all())
            ),
            'Issued stock orders retrieved'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            new ReservationResource($this->reservations->findForSeller($id, (int) $request->user()->id)),
            'Reservation retrieved'
        );
    }

    /**
     * File a reservation. Prices are resolved server-side, so the client
     * cannot set its own.
     */
    public function store(PlaceReservationRequest $request): JsonResponse
    {
        return $this->created(
            new ReservationResource(
                $this->reservations->place((int) $request->user()->id, $request->validated())
            ),
            'Reservation submitted'
        );
    }
}
