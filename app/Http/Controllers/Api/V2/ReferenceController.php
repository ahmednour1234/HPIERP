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

    /**
     * التخصصات الطبية (روماتيزم، جلدية، صدر أطفال ...).
     *
     * مخزَّنة في جدول categories وتُميَّز بـ type = 0. لا يوجد جدول
     * specialists؛ وعمود customers.specialist شيء آخر تمامًا يحمل نوع
     * الجهة (صيدلية / مركز طبي / مستشفى / طبيب) كرقم ثابت من 1 إلى 4.
     */
    public function specialties(): JsonResponse
    {
        return $this->ok(
            \App\Models\Category::where('type', 0)
                ->orderBy('name')
                ->get(['id', 'name']),
            'Specialties retrieved'
        );
    }

    /** فئات المنتجات (مكملات غذائية، مستلزمات طبية ...): categories.type = 1. */
    public function productCategories(): JsonResponse
    {
        return $this->ok(
            \App\Models\Category::where('type', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            'Product categories retrieved'
        );
    }

    public function storages(): JsonResponse
    {
        return $this->ok(
            Storage::orderBy('name')->get(['id', 'name']),
            'Storages retrieved'
        );
    }

    /**
     * وثائق المندوب الحالي: المسندة له والعامة.
     *
     * كانت ترجع كل وثائق النظام لأي مندوب، فيرى وثائق لا تخصه.
     */
    public function documents(Request $request): JsonResponse
    {
        return $this->ok(
            Document::visibleTo((int) $request->user()->id)
                ->with('attachments:id,document_id,type,url')
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
            'Documents retrieved'
        );
    }

    /**
     * وثيقة واحدة بمرفقاتها.
     *
     * تمر بنفس فحص الرؤية، فطلب وثيقة غير مسندة للمندوب يرد 404 لا 403 —
     * حتى لا يكشف الرد وجود وثيقة لا يملك رؤيتها أصلًا.
     */
    public function document(Request $request, int $id): JsonResponse
    {
        $document = Document::visibleTo((int) $request->user()->id)
            ->with('attachments:id,document_id,type,url')
            ->find($id, ['id', 'name', 'description']);

        if (!$document) {
            return $this->fail('Document not found', 404);
        }

        return $this->ok($document, 'Document retrieved');
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
