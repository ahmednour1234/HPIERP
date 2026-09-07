<?php

namespace App\Services;

use App\CPU\Helpers;

use App\Models\ResultVisitor;
use App\Models\Visitor;
use App\Repositories\CustomerRepository;
use App\Repositories\VisitRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;

class VisitService
{
    public function __construct(
        private VisitRepository $visits,
        private CustomerRepository $customers
    ) {
    }

    public function planned(int $sellerId, array $filters): LengthAwarePaginator
    {
        return $this->visits->planned(
            $sellerId, $filters,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function results(int $sellerId, array $filters): LengthAwarePaginator
    {
        return $this->visits->results(
            $sellerId, $filters,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function resultsForCustomer(int $customerId, int $sellerId, array $filters): LengthAwarePaginator
    {
        $this->assertCustomerBelongsToSeller($customerId, $sellerId);

        return $this->visits->resultsForCustomer(
            $customerId,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /** Plan a visit to one of the seller's own customers. */
    public function plan(int $sellerId, array $data): Visitor
    {
        $this->assertCustomerBelongsToSeller((int) $data['customer_id'], $sellerId);

        // load(): the resource exposes the customer's name through the
        // relation, which is not populated on a freshly created model.
        return $this->visits->create([
            'seller_id'   => $sellerId,
            'customer_id' => $data['customer_id'],
            'note'        => $data['note'] ?? null,
            'date'        => $data['date'],
        ])->load('customer');
    }

    /** Record what happened on a visit, with the location it was logged from. */
    public function recordResult(
        int $sellerId,
        array $data,
        ?\Illuminate\Http\UploadedFile $image = null
    ): ResultVisitor {
        $this->assertCustomerBelongsToSeller((int) $data['customer_id'], $sellerId);

        return $this->visits->recordResult([
            'admin_id'    => $sellerId,
            'customer_id' => $data['customer_id'],
            'note'        => $data['note'],
            // The columns are named lat/lang (not lng) in this schema.
            'lat'         => (string) ($data['lat'] ?? ''),
            'lang'        => (string) ($data['lang'] ?? ''),
            // الصورة تُحفظ في نفس مجلد صور الزيارات المستخدم في اللوحة.
            // Helpers::upload تُرجع اسم الملف فقط بينما تحفظه داخل visit/،
            // فنضيف المجلد ليكون المسار المخزَّن صالحًا للعرض مباشرة.
            'img'         => $image ? 'visit/' . Helpers::upload('visit/', 'png', $image) : null,
        ])->load('customer');
    }

    /**
     * A seller may only plan or log visits against their own customers. The
     * v1 endpoints checked the role but never the assignment, so any seller
     * could file a visit against any customer.
     */
    private function assertCustomerBelongsToSeller(int $customerId, int $sellerId): void
    {
        if (!$this->customers->exists($customerId)) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(\App\Models\Customer::class, $customerId);
        }

        if (!$this->customers->belongsToSeller($customerId, $sellerId)) {
            throw new AuthorizationException('This customer is not assigned to you');
        }
    }
}
