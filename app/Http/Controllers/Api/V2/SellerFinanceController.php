<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\SellerFinanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الوضع المالي للمندوب كما يراه من التطبيق.
 *
 * يجيب عن أربعة أسئلة يخلط بينها كثيرًا: كم باع، وكم حصّل، وكم بقي
 * على عملائه، وكم في يده لم يورّده بعد.
 */
class SellerFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(private SellerFinanceService $finance)
    {
    }

    /** ملخّص المندوب نفسه؛ لا يقبل معرِّف غيره. */
    public function summary(Request $request): JsonResponse
    {
        return $this->ok(
            $this->finance->forSeller((int) $request->user()->id),
            'الوضع المالي'
        );
    }
}
