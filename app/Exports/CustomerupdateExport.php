<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerupdateExport
{
    public function collection(): Collection
    {
        return Customer::with('category:id,name')->get()->map(function ($customer) {
            return [
                'ID'    => $customer->id,
                'Name'  => $customer->name,
                'address'=>$customer->address,
                'Phone' => $customer->mobile,
                'Email' => $customer->email,
                // التخصص باسمه كما في قائمة التخصصات، والتصنيف حرفًا A–D؛
                // يقرأهما CustomerImport بنفس الصيغة.
                'Specialty' => optional($customer->category)->name ?? '',
                'Class'     => CustomerClass::label($customer->type),
            ];
        });
    }
}
