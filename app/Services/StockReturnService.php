<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockReturnRequest;
use App\Models\StockReturnRequestItem;
use App\Services\Exceptions\InsufficientStockException;
use App\Services\Exceptions\ReturnRequestException;
use Illuminate\Support\Facades\DB;

/**
 * إرجاع البضاعة من عربية المندوب إلى المخزن بموافقة الأدمن.
 *
 * كانت التسوية فورية ولا رجعة فيها، والسيرفر هو من يقرر المتبقّي. صار
 * المندوب يحدد ما يرجعه، ويُسجَّل الطلب معلّقًا، ولا يتحرك المخزون إلا
 * عند الاعتماد — فغلطة المندوب أو فرق الجرد يمكن رفضه قبل أن يؤثر.
 */
class StockReturnService
{
    /**
     * تسجيل طلب إرجاع معلّق. لا يمس المخزون.
     *
     * @param array<int, array{product_id: int|string, quantity: int|string}> $items
     */
    public function request(int $sellerId, array $items, ?string $note = null): StockReturnRequest
    {
        return DB::transaction(function () use ($sellerId, $items, $note) {
            // طلب معلّق واحد لكل مندوب: بدونه يرسل التطبيق الطلب مرتين
            // فتُخصم الكميات مرتين عند الاعتماد.
            if ($this->pendingFor($sellerId)) {
                throw ReturnRequestException::alreadyPending();
            }

            $merged = $this->mergeQuantities($items);

            // تُقفل صفوف العربية حتى لا يمر طلبان متزامنان بنفس الفحص.
            $rows = Stock::where('seller_id', $sellerId)
                ->whereIn('product_id', array_keys($merged))
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($merged as $productId => $quantity) {
                $row = $rows->get($productId);

                if (!$row) {
                    throw new InsufficientStockException(
                        'المنتج ' . $this->productName($productId) . ' غير موجود في عربيتك.'
                    );
                }

                if ($quantity > $row->stock) {
                    throw new InsufficientStockException(
                        'الكمية المطلوب إرجاعها من ' . $this->productName($productId)
                        . ' تتجاوز المتاح في العربية (المتاح ' . $row->stock . '، المطلوب ' . $quantity . ').'
                    );
                }
            }

            $request = StockReturnRequest::create([
                'seller_id' => $sellerId,
                'status'    => StockReturnRequest::STATUS_PENDING,
                'note'      => $note,
            ]);

            foreach ($merged as $productId => $quantity) {
                StockReturnRequestItem::create([
                    'request_id' => $request->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                ]);
            }

            return $request->load('items.product:id,name,product_code');
        });
    }

    /** آخر طلب للمندوب: المعلّق إن وُجد، وإلا آخر ما رُوجع. */
    public function current(int $sellerId): ?StockReturnRequest
    {
        return $this->pendingFor($sellerId)
            ?? StockReturnRequest::where('seller_id', $sellerId)
                ->with('items.product:id,name,product_code')
                ->latest('id')
                ->first();
    }

    /** سحب طلب معلّق قبل مراجعته. */
    public function cancel(int $sellerId, int $requestId): StockReturnRequest
    {
        return DB::transaction(function () use ($sellerId, $requestId) {
            $request = StockReturnRequest::where('id', $requestId)->lockForUpdate()->first();

            // طلب مندوب آخر يردّ 404 لا 403، فلا يكشف الرد وجوده.
            if (!$request || (int) $request->seller_id !== $sellerId) {
                throw ReturnRequestException::notFound();
            }

            if (!$request->isPending()) {
                throw ReturnRequestException::notPending();
            }

            // الحذف لا الأرشفة: الطلب لم يمس المخزون، فلا أثر يلزم حفظه.
            $request->items()->delete();
            $request->delete();

            return $request;
        });
    }

    /**
     * اعتماد الطلب: ينقل الكميات من العربية إلى المخزن في معاملة واحدة.
     */
    public function approve(int $requestId, int $adminId, ?string $adminNote = null): StockReturnRequest
    {
        return DB::transaction(function () use ($requestId, $adminId, $adminNote) {
            $request = $this->lockPending($requestId);

            foreach ($request->items as $item) {
                $row = Stock::where('seller_id', $request->seller_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                // العربية قد تكون تغيّرت بين تقديم الطلب ومراجعته.
                if (!$row || $item->quantity > $row->stock) {
                    throw new InsufficientStockException(
                        'الكمية المطلوب إرجاعها من ' . $this->productName((int) $item->product_id)
                        . ' لم تعد متاحة في عربية المندوب (المتاح ' . (int) ($row->stock ?? 0) . ').'
                    );
                }

                $row->stock -= $item->quantity;
                $row->save();

                if ($product = Product::whereKey($item->product_id)->lockForUpdate()->first()) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $request->update([
                'status'      => StockReturnRequest::STATUS_APPROVED,
                'admin_note'  => $adminNote,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            return $request->load('items.product:id,name,product_code');
        });
    }

    /** رفض الطلب: المخزون لا يتغير. */
    public function reject(int $requestId, int $adminId, ?string $adminNote = null): StockReturnRequest
    {
        return DB::transaction(function () use ($requestId, $adminId, $adminNote) {
            $request = $this->lockPending($requestId);

            $request->update([
                'status'      => StockReturnRequest::STATUS_REJECTED,
                'admin_note'  => $adminNote,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            return $request->load('items.product:id,name,product_code');
        });
    }

    /**
     * الطلب مقفلًا بعد التأكد أنه ما زال معلّقًا.
     *
     * الفحص داخل القفل: اعتمادان متزامنان لا يخصمان المخزون مرتين.
     */
    private function lockPending(int $requestId): StockReturnRequest
    {
        $request = StockReturnRequest::where('id', $requestId)->lockForUpdate()->first();

        if (!$request) {
            throw ReturnRequestException::notFound();
        }

        if (!$request->isPending()) {
            throw ReturnRequestException::notPending();
        }

        return $request;
    }

    private function pendingFor(int $sellerId): ?StockReturnRequest
    {
        return StockReturnRequest::where('seller_id', $sellerId)
            ->where('status', StockReturnRequest::STATUS_PENDING)
            ->with('items.product:id,name,product_code')
            ->latest('id')
            ->first();
    }

    /**
     * يجمع الكميات حين يرد المنتج نفسه في أكثر من سطر، وإلا مرّ كل سطر
     * الفحص وحده بينما مجموعها يتجاوز المتاح في العربية.
     *
     * @return array<int, int>
     */
    private function mergeQuantities(array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $merged[$productId] = ($merged[$productId] ?? 0) + (int) $item['quantity'];
        }

        return $merged;
    }

    private function productName(int $productId): string
    {
        return Product::whereKey($productId)->value('name') ?? ('#' . $productId);
    }
}
