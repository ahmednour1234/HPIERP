<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Region;
use App\Models\Storage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only reference data the mobile app needs to populate its pickers:
 * regions, storages, documents and the sellers assigned to this admin.
 *
 * These are small, stable lists, so they are grouped in one controller rather
 * than given a module each.
 */
class ReferenceController extends Controller
{
    use ApiResponse;

    public function regions(): JsonResponse
    {
        return $this->ok(
            Region::orderBy('name')->get(['id', 'name', 'name_en']),
            'Regions retrieved'
        );
    }

    public function storages(): JsonResponse
    {
        return $this->ok(
            Storage::orderBy('name')->get(['id', 'name']),
            'Storages retrieved'
        );
    }

    public function documents(): JsonResponse
    {
        return $this->ok(
            Document::with('attachments:id,document_id,type,url')
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
            'Documents retrieved'
        );
    }

    /** The regions this seller covers. */
    public function myRegions(Request $request): JsonResponse
    {
        $ids = \App\Models\SellerRegion::where('seller_id', $request->user()->id)->pluck('region_id');

        return $this->ok(
            Region::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'name_en']),
            'Regions retrieved'
        );
    }
}
