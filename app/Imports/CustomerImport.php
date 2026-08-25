<?php

namespace App\Imports;

use App\Models\Customer;

class CustomerImport
{
    public function __invoke($row)
    {
        // نفترض أن المفتاح للتحديث هو ID
        $customer = Customer::find($row['ID']);

        if ($customer) {
            $customer->name  = $row['Name'];
            $customer->address  = $row['address'];
            $customer->mobile = $row['Phone'];
            $customer->email = $row['Email'];
            $customer->save();
        }
    }
}
