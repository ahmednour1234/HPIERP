<?php
namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    protected $search;

    // Constructor to accept the search term
    public function __construct($search)
    {
        $this->search = $search;
    }

    public function collection()
    {
        // If there's a search term, apply the search filters
        $query = Customer::query();

        if ($this->search) {
            $key = explode(' ', $this->search);
            $query = $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%");
                }
            });
        }

        // Return the customer data to export
        return $query->get([
            'name', 'mobile', 'email', 'address', 'pharmacy_name', 'state', 'city', 'zip_code', 'balance','credit'
        ]);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Mobile',
            'Email',
            'Address',
            'Pharmacy Name',
            'State',
            'City',
            'Zip Code',
            'Balance',
            'credit'
        ];
    }
}
