<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A realistic demo dataset for local work.
 *
 * The older seeders (ProductTableSeeder, CustomerTableSeeder) generate tens of
 * thousands of rows of random strings, which makes every list page unreadable
 * and tells you nothing about whether a screen works. This builds a small,
 * coherent pharmacy-distribution dataset instead: settings, a catalogue,
 * sellers with their own routes and stock, customers, and a few months of
 * orders, payments and visits so the reports and dashboards have real shapes.
 *
 * Safe to re-run: every insert is keyed on a fixed id.
 */
class DemoDataSeeder extends Seeder
{
    /** Demo ids live in their own range so they never collide with real data. */
    private const BASE = 800000;

    private const SELLERS = [
        ['code' => 'MND-01', 'first' => 'Ahmed',  'last' => 'Nour',   'region' => 'Cairo'],
        ['code' => 'MND-02', 'first' => 'Mostafa','last' => 'Kamel',  'region' => 'Giza'],
        ['code' => 'MND-03', 'first' => 'Sara',   'last' => 'Ibrahim','region' => 'Alexandria'],
    ];

    private const REGIONS = ['Cairo', 'Giza', 'Alexandria', 'Mansoura'];

    private const CATEGORIES = [
        'Analgesics', 'Antibiotics', 'Vitamins', 'Dermatology', 'Baby Care',
    ];

    private const BRANDS = ['Pharco', 'EIPICO', 'Amoun', 'Hikma', 'Sedico'];

    /** name, code, category index, purchase, selling */
    private const PRODUCTS = [
        ['Paracetamol 500mg',      'PRC-500',  0, 12.00,  20.00],
        ['Ibuprofen 400mg',        'IBU-400',  0, 18.50,  28.00],
        ['Diclofenac 50mg',        'DIC-050',  0, 15.00,  24.00],
        ['Amoxicillin 500mg',      'AMX-500',  1, 32.00,  48.00],
        ['Azithromycin 250mg',     'AZI-250',  1, 55.00,  79.00],
        ['Cefixime 400mg',         'CFX-400',  1, 61.00,  88.00],
        ['Vitamin C 1000mg',       'VTC-1000', 2, 25.00,  40.00],
        ['Vitamin D3 5000IU',      'VTD-5000', 2, 45.00,  68.00],
        ['Zinc 50mg',              'ZNC-050',  2, 22.00,  35.00],
        ['Hydrocortisone Cream',   'HYD-CRM',  3, 30.00,  46.00],
        ['Ketoconazole Shampoo',   'KTZ-SHM',  3, 52.00,  75.00],
        ['Baby Diaper Rash Cream', 'BBY-RSH',  4, 38.00,  57.00],
        ['Infant Multivitamin',    'BBY-MVT',  4, 41.00,  62.00],
        ['Oral Rehydration Salts', 'ORS-001',  4,  6.50,  11.00],
    ];

    private const PHARMACIES = [
        ['El Ezaby Pharmacy',   '01001234501', 'Nasr City'],
        ['Seif Pharmacy',       '01001234502', 'Heliopolis'],
        ['Roshdy Pharmacy',     '01001234503', 'Dokki'],
        ['El Tayseer Pharmacy', '01001234504', 'Mohandessin'],
        ['Al Amal Pharmacy',    '01001234505', 'Maadi'],
        ['El Nahda Pharmacy',   '01001234506', 'Faisal'],
        ['Al Shifa Pharmacy',   '01001234507', 'Smouha'],
        ['Ramses Pharmacy',     '01001234508', 'Sidi Gaber'],
        ['El Salam Pharmacy',   '01001234509', 'Mansoura'],
        ['Misr Pharmacy',       '01001234510', 'Talkha'],
    ];

    public function run()
    {
        $now = now();

        $this->command->info('Seeding demo data...');

        $this->settings($now);
        $this->reference($now);
        $this->catalogue($now);
        $this->people($now);
        $this->stockAndTrade($now);
        $this->payslips($now);

        $this->command->info('');
        $this->command->info('  Web admin : demo.admin@example.test / password');
        $this->command->info('  API seller: MND-01 / password');
        $this->command->info('');
    }

    /* ------------------------------------------------------------------ */

    private function settings(Carbon $now): void
    {
        // Several screens read these directly; without them pages render blank
        // or fall back to defaults.
        $settings = [
            'shop_name'        => 'HPI Pharma Distribution',
            'shop_address'     => '12 El Nasr Road, Nasr City, Cairo',
            'shop_phone'       => '+20 2 1234 5678',
            'shop_email'       => 'info@hpi-demo.test',
            'shop_logo'        => 'def.png',
            'currency'         => 'EGP',
            'country'          => 'EG',
            'time_zone'        => 'Africa/Cairo',
            'pagination_limit' => '25',
            'stock_limit'      => '20',
            'vat_reg_no'       => '100-200-300',
            'footer_text'      => 'HPI Pharma Distribution',
            'kilometer'        => '5',
            'number_tax'       => '14',
        ];

        foreach ($settings as $key => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('currencies')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['country' => 'Egypt', 'currency_code' => 'EGP', 'currency_symbol' => 'E£',
             'exchange_rate' => 1, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function reference(Carbon $now): void
    {
        foreach (self::REGIONS as $i => $name) {
            DB::table('regions')->updateOrInsert(
                ['id' => self::BASE + 10 + $i],
                ['local_id' => 0, 'name' => $name, 'name_en' => $name,
                 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('storages')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['local_id' => 0, 'name' => 'Main Warehouse', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('units')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['local_id' => 0, 'unit_type' => 'Box', 'symbol' => 'BX', 'is_base' => 1,
             'conversion_rate' => 1, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('units')->updateOrInsert(
            ['id' => self::BASE + 2],
            ['local_id' => 0, 'unit_type' => 'Strip', 'symbol' => 'ST', 'is_base' => 0,
             'conversion_rate' => 10, 'base_unit_id' => self::BASE + 1,
             'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('taxes')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['name' => 'VAT 14%', 'amount' => '14', 'active' => 1,
             'created_at' => $now, 'updated_at' => $now]
        );

        // Cash and bank, so transfers between accounts have somewhere to go.
        DB::table('accounts')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['storage_id' => self::BASE + 1, 'account' => 'Main Cash', 'account_number' => 'CASH-001',
             'balance' => 250000, 'total_in' => 250000, 'total_out' => 0,
             'description' => 'Daily cash box', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('accounts')->updateOrInsert(
            ['id' => self::BASE + 2],
            ['storage_id' => self::BASE + 1, 'account' => 'Bank - CIB', 'account_number' => 'BANK-001',
             'balance' => 750000, 'total_in' => 750000, 'total_out' => 0,
             'description' => 'Company bank account', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('shifts')->updateOrInsert(
            ['id' => self::BASE + 1],
            ['name' => 'Morning', 'start' => '09:00:00', 'end' => '17:00:00',
             'breake' => 1, 'kilometer' => '0.1', 'active' => 1,
             'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function catalogue(Carbon $now): void
    {
        foreach (self::BRANDS as $i => $name) {
            DB::table('brands')->updateOrInsert(
                ['id' => self::BASE + 20 + $i],
                ['local_id' => 0, 'name' => $name, 'image' => 'def.png',
                 'created_at' => $now, 'updated_at' => $now]
            );
        }

        foreach (self::CATEGORIES as $i => $name) {
            DB::table('categories')->updateOrInsert(
                ['id' => self::BASE + 30 + $i],
                ['local_id' => 0, 'name' => $name, 'parent_id' => 0, 'position' => 0,
                 'status' => 1, 'image' => 'def.png', 'type' => 1,
                 'created_at' => $now, 'updated_at' => $now]
            );
        }

        foreach (self::PRODUCTS as $i => [$name, $code, $cat, $purchase, $selling]) {
            DB::table('products')->updateOrInsert(
                ['id' => self::BASE + 100 + $i],
                [
                    'local_id'      => 0,
                    'name'          => $name,
                    'name_en'       => $name,
                    'product_code'  => $code,
                    'unit_type'     => self::BASE + 1,
                    'unit_value'    => 1,
                    'brand'         => (string) (self::BASE + 20 + ($i % count(self::BRANDS))),
                    'category_id'   => (string) (self::BASE + 30 + $cat),
                    'purchase_price'  => $purchase,
                    'purchase_price1' => $purchase, 'purchase_price2' => $purchase,
                    'purchase_price3' => $purchase, 'purchase_price4' => $purchase,
                    'selling_price'   => $selling,
                    'selling_price1'  => $selling, 'selling_price2'  => $selling,
                    'selling_price3'  => $selling, 'selling_price4'  => $selling,
                    'tax'          => 14,
                    'tax_id'       => self::BASE + 1,
                    'discount'     => 0,
                    'discount_type'=> 'amount',
                    // A couple of products sit under the threshold on purpose so
                    // the low-stock report is not empty.
                    'quantity'     => $i < 2 ? 8 : 500,
                    'limit_stock'  => 20,
                    'type'         => 'product',
                    'image'        => 'def.png',
                    'created_at'   => $now, 'updated_at' => $now,
                ]
            );
        }

        foreach (['Global Pharma Supply', 'Delta Medical', 'Nile Distributors'] as $i => $name) {
            DB::table('suppliers')->updateOrInsert(
                ['id' => self::BASE + 40 + $i],
                ['local_id' => 0, 'name' => $name, 'mobile' => '0220000' . (10 + $i),
                 'email' => 'contact' . $i . '@supplier.test', 'city' => 'Cairo',
                 'address' => 'Industrial Zone, Cairo', 'due_amount' => [12000, 0, 4500][$i],
                 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function people(Carbon $now): void
    {
        // Web admin. Every section flag defaults to 0 and the Check*Access
        // middleware redirects away from anything not granted, so an admin
        // without these can only see the dashboard shell.
        DB::table('admins')->updateOrInsert(
            ['id' => self::BASE + 1],
            array_merge([
                'local_id' => 0, 'f_name' => 'Demo', 'l_name' => 'Admin', 'name_en' => 'Demo Admin',
                'email' => 'demo.admin@example.test', 'password' => Hash::make('password'),
                'role' => 'admin', 'company_id' => 1, 'phone' => '01000000000',
                'created_at' => $now, 'updated_at' => $now,
            ], $this->allPermissions())
        );

        foreach (self::SELLERS as $i => $seller) {
            $id = self::BASE + 50 + $i;

            DB::table('admins')->updateOrInsert(
                ['id' => $id],
                ['local_id' => 0, 'f_name' => $seller['first'], 'l_name' => $seller['last'],
                 'name_en' => $seller['first'] . ' ' . $seller['last'],
                 'email' => strtolower($seller['first']) . '.seller@example.test',
                 'password' => Hash::make('password'),
                 'mandob_code' => $seller['code'], 'vehicle_code' => (string) (self::BASE + 60 + $i),
                 'type' => 'mandob', 'role' => 'seller', 'company_id' => 1,
                 'salary' => '6000', 'shift_id' => json_encode([self::BASE + 1]),
                 'created_at' => $now, 'updated_at' => $now]
            );

            // Each seller gets a van (a store row) and the region they cover.
            DB::table('stores')->updateOrInsert(
                ['store_id' => self::BASE + 60 + $i],
                ['local_id' => 0, 'seller_id' => $id, 'store_code' => 'VAN-0' . ($i + 1),
                 'store_name1' => 'Van ' . ($i + 1) . ' - ' . $seller['region'],
                 'store_type' => 1, 'company_id' => 1, 'updated_at' => $now]
            );

            DB::table('storage_sellers')->updateOrInsert(
                ['id' => self::BASE + 50 + $i],
                ['storage_id' => self::BASE + 1, 'seller_id' => $id,
                 'created_at' => $now, 'updated_at' => $now]
            );

            $regionIndex = array_search($seller['region'], self::REGIONS, true) ?: 0;
            DB::table('seller_regions')->updateOrInsert(
                ['id' => self::BASE + 50 + $i],
                ['seller_id' => $id, 'region_id' => self::BASE + 10 + $regionIndex]
            );

            // Sellers carry the whole catalogue.
            foreach (array_keys(self::CATEGORIES) as $c) {
                DB::table('seller_categories')->updateOrInsert(
                    ['id' => self::BASE + 200 + ($i * 10) + $c],
                    ['seller_id' => $id, 'cat_id' => self::BASE + 30 + $c]
                );
            }
        }

        // Every list page in the panel narrows to the sellers linked here, so
        // an admin with no rows in admin_sellers sees empty tables everywhere.
        foreach (array_keys(self::SELLERS) as $i) {
            DB::table('admin_sellers')->updateOrInsert(
                ['id' => self::BASE + 70 + $i],
                [
                    'admin_id'   => self::BASE + 1,
                    'seller_id'  => self::BASE + 50 + $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        foreach (self::PHARMACIES as $i => [$name, $mobile, $area]) {
            $customerId = self::BASE + 300 + $i;
            $sellerId   = self::BASE + 50 + ($i % count(self::SELLERS));

            DB::table('customers')->updateOrInsert(
                ['id' => $customerId],
                ['local_id' => 0, 'name' => $name, 'name_en' => $name, 'mobile' => $mobile,
                 'email' => 'branch' . $i . '@pharmacy.test', 'pharmacy_name' => $name,
                 'address' => $area, 'city' => $area, 'state' => 'Egypt',
                 'region_id' => self::BASE + 10 + ($i % count(self::REGIONS)),
                 'balance' => [0, 1500, 0, 3200, 0, 780, 0, 0, 2400, 0][$i],
                 'credit' => 0, 'limit' => 20000, 'active' => 1, 'specialist' => 1,
                 'created_at' => $now, 'updated_at' => $now]
            );

            DB::table('seller_customers')->updateOrInsert(
                ['id' => self::BASE + 300 + $i],
                ['local_id' => 0, 'customer_id' => $customerId, 'seller_id' => $sellerId,
                 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    /**
     * Every section flag switched on.
     *
     * The columns exist on `admins` and each Check*Access middleware reads one
     * of them; without these a freshly seeded admin is bounced back to the
     * dashboard from every page.
     */
    private function allPermissions(): array
    {
        $flags = [
            'dashboard', 'pos', 'stock', 'store', 'cat', 'unit', 'product',
            'stock_limit', 'coupons', 'customer', 'seller', 'admin', 'supplier',
            'setting', 'requests', 'storage', 'notification', 'tracking',
            'vehicle_stock', 'reports', 'regions', 'sales', 'accounts', 'rating',
            'visit', 'sectionsalary', 'visitors', 'result_visitors',
        ];

        return array_fill_keys($flags, 1) + ['production' => 1, 'hr' => 1, 'attendance' => 1];
    }

    private function stockAndTrade(Carbon $now): void
    {
        $productIds = [];
        foreach (array_keys(self::PRODUCTS) as $i) {
            $productIds[] = self::BASE + 100 + $i;
        }

        // Van stock: each seller carries part of the catalogue.
        $stockRow = 0;
        foreach (self::SELLERS as $s => $seller) {
            $sellerId = self::BASE + 50 + $s;

            foreach ($productIds as $p => $productId) {
                if (($p + $s) % 2 === 0) continue;  // not everyone carries everything

                $carried = 40 + (($p * 7 + $s * 3) % 60);
                $sold    = (int) floor($carried * 0.3);

                DB::table('stocks')->updateOrInsert(
                    ['id' => self::BASE + 400 + $stockRow],
                    ['local_id' => 0, 'seller_id' => $sellerId, 'product_id' => $productId,
                     'store_id' => self::BASE + 60 + $s,
                     'main_stock' => $carried, 'stock' => $carried - $sold, 'active' => 1,
                     'created_at' => $now, 'updated_at' => $now]
                );
                $stockRow++;
            }
        }

        // Orders spread over the last three months so the monthly charts and
        // date filters have something to show.
        $orderRow = 0;
        $detailRow = 0;

        foreach (range(0, 17) as $n) {
            $sellerIdx  = $n % count(self::SELLERS);
            $sellerId   = self::BASE + 50 + $sellerIdx;
            $customerId = self::BASE + 300 + ($n % count(self::PHARMACIES));
            $placedAt   = $now->copy()->subDays($n * 5);

            // Every sixth order is a return, so the reports show both sides.
            $isReturn = $n % 6 === 5;
            $type     = $isReturn ? 7 : 4;

            $orderId = self::BASE + 500 + $orderRow;
            $lines   = [];
            $subTotal = 0.0;
            $taxTotal = 0.0;

            foreach ([0, 1, 2] as $k) {
                $p        = ($n + $k) % count(self::PRODUCTS);
                $product  = self::PRODUCTS[$p];
                $quantity = 2 + (($n + $k) % 8);
                $price    = $product[4];

                $lineTax   = round($price * 0.14, 2);
                $subTotal += $price * $quantity;
                $taxTotal += $lineTax * $quantity;

                $lines[] = [
                    'id'         => self::BASE + 900 + $detailRow,
                    'order_id'   => $orderId,
                    'product_id' => self::BASE + 100 + $p,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'tax_amount' => $lineTax,
                    'discount_on_product' => 0,
                    'discount_type' => 'amount',
                    'product_details' => json_encode(['name' => $product[0], 'product_code' => $product[1]]),
                    'update_flag' => 0,
                    'created_at' => $placedAt, 'updated_at' => $placedAt,
                ];
                $detailRow++;
            }

            $total = round($subTotal + $taxTotal, 2);
            // Two thirds of sales are settled in full; the rest stay part-paid
            // so the "collected" reports are not uniformly green.
            $collected = $isReturn ? $total : ($n % 3 === 0 ? round($total * 0.4, 2) : $total);

            DB::table('orders')->updateOrInsert(
                ['id' => $orderId],
                ['owner_id' => $sellerId, 'user_id' => $customerId, 'owner_role' => 'admin',
                 'type' => (string) $type, 'cash' => 1, 'payment_id' => self::BASE + 1,
                 'order_amount' => $total, 'total_tax' => $taxTotal,
                 'collected_cash' => $collected, 'transaction_reference' => (string) $collected,
                 'extra_discount' => 0, 'coupon_discount_amount' => 0,
                 'insert_flag' => 1, 'update_flag' => 0, 'active' => 1, 'company_id' => 1,
                 'created_at' => $placedAt, 'updated_at' => $placedAt]
            );

            foreach ($lines as $line) {
                DB::table('order_details')->updateOrInsert(['id' => $line['id']], $line);
            }

            DB::table('transections')->updateOrInsert(
                ['id' => self::BASE + 500 + $orderRow],
                ['tran_type' => (string) $type, 'account_id' => self::BASE + 1,
                 'seller_id' => $sellerId, 'customer_id' => $customerId, 'order_id' => $orderId,
                 'amount' => $collected, 'balance' => $collected, 'cash' => 1,
                 'description' => $isReturn ? 'مرتجع مبيعات' : 'مبيعات',
                 'debit' => $isReturn ? 0 : 1, 'credit' => $isReturn ? 1 : 0,
                 'date' => $placedAt->toDateString(), 'active' => 1, 'company_id' => 1,
                 'created_at' => $placedAt, 'updated_at' => $placedAt]
            );

            $orderRow++;
        }

        // Expenses, so the ledger is not only sales.
        foreach ([['Fuel', 850], ['Vehicle maintenance', 2300], ['Office supplies', 640]] as $i => [$what, $amount]) {
            $when = $now->copy()->subDays(10 * ($i + 1));
            DB::table('transections')->updateOrInsert(
                ['id' => self::BASE + 700 + $i],
                ['tran_type' => 'Expense', 'account_id' => self::BASE + 1,
                 'amount' => $amount, 'balance' => 250000 - $amount, 'description' => $what,
                 'debit' => 1, 'credit' => 0, 'date' => $when->toDateString(),
                 'active' => 1, 'company_id' => 1, 'created_at' => $when, 'updated_at' => $when]
            );
        }

        // Visits: some planned, some already carried out.
        $visitRow = 0;
        foreach (self::SELLERS as $s => $seller) {
            $sellerId = self::BASE + 50 + $s;

            foreach (range(0, 3) as $k) {
                $customerId = self::BASE + 300 + (($s * 3 + $k) % count(self::PHARMACIES));
                $when = $now->copy()->subDays($k * 3);

                DB::table('visitors')->updateOrInsert(
                    ['id' => self::BASE + 600 + $visitRow],
                    ['seller_id' => $sellerId, 'customer_id' => $customerId,
                     'note' => 'Routine stock check', 'date' => $when->toDateString(),
                     'created_at' => $when, 'updated_at' => $when]
                );

                if ($k < 2) {
                    DB::table('result_visitors')->updateOrInsert(
                        ['id' => self::BASE + 600 + $visitRow],
                        ['admin_id' => $sellerId, 'customer_id' => $customerId,
                         'note' => $k === 0 ? 'Order placed, restock in two weeks'
                                            : 'No order this visit, revisit next month',
                         'lat' => '30.0444', 'lang' => '31.2357',
                         'created_at' => $when, 'updated_at' => $when]
                    );
                }

                $visitRow++;
            }
        }
    }

    /**
     * A payslip per seller for the last three months.
     *
     * `month` is stored as YYYY-MM, and every money column is a varchar — both
     * quirks of the existing schema, matched here so the API reads real data.
     *
     * `total` is what the admin form computes and stores:
     *   salary + transport_amount + salary_of_visitors + other - discount
     * Commission is recorded but deliberately not part of it.
     */
    private function payslips(Carbon $now): void
    {
        $row = 0;

        foreach (self::SELLERS as $s => $seller) {
            $sellerId = self::BASE + 50 + $s;

            foreach ([1, 2, 3] as $back) {
                $month = $now->copy()->subMonthsNoOverflow($back)->format('Y-m');

                $basic     = 6000;
                $transport = 750 + ($s * 50);
                $visitsPay = 400 + ($back * 25);
                $other     = $back === 1 ? 300 : 0;
                $discount  = $back === 2 ? 250 : 0;
                $total     = $basic + $transport + $visitsPay + $other - $discount;

                DB::table('salaries')->updateOrInsert(
                    ['id' => self::BASE + 800 + $row],
                    [
                        'seller_id'          => $sellerId,
                        'month'              => $month,
                        'salary'             => (string) $basic,
                        'commission'         => (string) (900 + ($s * 120) + ($back * 40)),
                        'transport_amount'   => (string) $transport,
                        'salary_of_visitors' => (string) $visitsPay,
                        'other'              => (string) $other,
                        'discount'           => (string) $discount,
                        'total'              => (string) $total,
                        'number_of_visitors' => (string) 40,
                        'result_of_visitors' => (string) (30 + $back),
                        'number_of_days'     => 26,
                        'score'              => (string) (85 + $back),
                        'note'               => 'لاتوجد ملاحظات',
                        'notemanager'        => $back === 1 ? 'أداء جيد هذا الشهر' : 'لاتوجد ملاحظات',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]
                );

                $row++;
            }
        }
    }
}
