<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerupdateExport
{
    public function collection(): Collection
    {
        return Customer::all()->map(function ($customer) {
            return [
                'ID'    => $customer->id,
                'Name'  => $customer->name,
                'address'=>$customer->address,
                'Phone' => $customer->mobile,
                'Email' => $customer->email,
            ];
        });
    }
}
