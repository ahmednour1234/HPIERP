<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payslip.
 *
 * Every money column on `salaries` is a varchar, so each is cast to a number
 * here — clients should never have to parse strings to add them up.
 */
class SalaryResource extends JsonResource
{
    public function toArray($request)
    {
        $basic     = (float) $this->salary;
        $transport = (float) $this->transport_amount;
        $visits    = (float) $this->salary_of_visitors;
        $other     = (float) $this->other;
        $discount  = (float) $this->discount;
        // حافز التحصيل: عمود أُضيف لاحقًا، فالصفوف القديمة تحمل 0 أو null.
        $collection = (float) $this->collection_incentive;

        // The admin form's formula. `total` is stored, so it is the authority;
        // this only stands in when an older row was saved without one.
        $net = $this->total !== null && $this->total !== ''
            ? (float) $this->total
            : $basic + $transport + $visits + $collection + $other - $discount;

        return [
            'id'    => $this->id,
            'month' => $this->month,

            'basic'      => $basic,
            'transport'  => $transport,
            'visits_pay' => $visits,
            'collection_incentive' => $collection,
            'other'      => $other,
            'deductions' => $discount,
            'net'        => round($net, 2),

            // Recorded per month but not part of `net` — the admin form
            // excludes it from the total.
            'commission' => (float) $this->commission,

            'visits' => [
                'target'   => (int) $this->number_of_visitors,
                'achieved' => (int) $this->result_of_visitors,
            ],
            'working_days' => (int) $this->number_of_days,
            'score'        => (float) $this->score,

            'note'         => $this->note,
            'manager_note' => $this->notemanager,

            // A payslip exists only once the admin has entered it, so any row
            // returned here has been approved.
            'status'      => 'approved',
            'status_text' => 'معتمد',

            // The same figures as a labelled breakdown, for a payslip screen.
            'details' => array_values(array_filter([
                ['label' => 'الراتب الأساسي', 'amount' => $basic,     'type' => 'add'],
                $transport ? ['label' => 'بدل انتقال',   'amount' => $transport, 'type' => 'add'] : null,
                $visits    ? ['label' => 'حافز الزيارات', 'amount' => $visits,    'type' => 'add'] : null,
                $collection ? ['label' => 'حافز التحصيل', 'amount' => $collection, 'type' => 'add'] : null,
                $other     ? ['label' => 'أخرى',          'amount' => $other,     'type' => 'add'] : null,
                $discount  ? ['label' => 'خصومات',        'amount' => $discount,  'type' => 'deduct'] : null,
            ])),

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
