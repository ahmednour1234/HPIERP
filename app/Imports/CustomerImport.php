<?php

namespace App\Imports;

use App\Exports\CustomerClass;
use App\Models\Category;
use App\Models\Customer;

class CustomerImport
{
    /** أسماء التخصصات (categories.type = 0) إلى معرّفاتها، تُقرأ مرة واحدة للملف. */
    private ?array $specialties = null;

    public function __invoke($row)
    {
        // نفترض أن المفتاح للتحديث هو ID
        $customer = Customer::find($row['ID']);

        if ($customer) {
            $customer->name  = $row['Name'];
            $customer->address  = $row['address'];
            $customer->mobile = $row['Phone'];
            $customer->email = $row['Email'];

            // التخصص والتصنيف: الخلية الفارغة أو الغائبة (ملف قديم بلا
            // العمودين) تُبقي القيمة الحالية، والقيمة غير المعروفة تُتجاهل
            // بدل أن تمسح ما هو مسجَّل.
            $specialty = trim((string) ($row['Specialty'] ?? ''));
            if ($specialty !== '' && ($id = $this->specialtyId($specialty))) {
                $customer->category_id = $id;
            }

            $class = CustomerClass::value($row['Class'] ?? '');
            if ($class !== null) {
                $customer->type = $class;
            }

            $customer->save();
        }
    }

    private function specialtyId(string $name): ?int
    {
        if ($this->specialties === null) {
            $this->specialties = Category::where('type', 0)
                ->pluck('id', 'name')
                ->mapWithKeys(fn ($id, $n) => [mb_strtolower(trim($n)) => (int) $id])
                ->all();
        }

        return $this->specialties[mb_strtolower($name)] ?? null;
    }
}
