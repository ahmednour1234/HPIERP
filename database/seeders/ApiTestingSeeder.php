<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Fixtures for local API testing.
 *
 * The production dump carries real rows but its passwords are bcrypt hashes we
 * cannot reverse, so `POST /login` could never be exercised against it. This
 * seeder adds a seller with a known credential plus the minimum related rows
 * (storage, region, product, stock, customer) that the seller-scoped endpoints
 * read, and is safe to run repeatedly.
 *
 * Credentials: mandob_code TESTSELLER / password "password"
 */
class ApiTestingSeeder extends Seeder
{
    public const SELLER_ID   = 900001;
    public const SELLER_CODE = 'TESTSELLER';
    public const PASSWORD    = 'password';

    /** For the web admin panel, which signs in by email on the `admin` guard. */
    public const ADMIN_ID    = 900000;
    public const ADMIN_EMAIL = 'admin@example.test';

    public function run()
    {
        $now = now();

        DB::table('storages')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'name' => 'Test Storage', 'created_at' => $now, 'updated_at' => $now]
        );

        // The web panel (/admin/auth/login) authenticates by email against the
        // `admin` guard and requires role=admin.
        DB::table('admins')->updateOrInsert(
            ['id' => self::ADMIN_ID],
            array_merge([
                'local_id'   => self::ADMIN_ID,
                'f_name'     => 'Local',
                'l_name'     => 'Admin',
                'name_en'    => 'Local Admin',
                'email'      => self::ADMIN_EMAIL,
                'password'   => Hash::make(self::PASSWORD),
                'role'       => 'admin',
                'company_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ], $this->allPermissions())
        );

        // The API login requires role=seller and matches on mandob_code.
        DB::table('admins')->updateOrInsert(
            ['id' => self::SELLER_ID],
            [
                'local_id'     => self::SELLER_ID,
                'f_name'       => 'Test',
                'l_name'       => 'Seller',
                'name_en'      => 'Test Seller',
                'email'        => 'test.seller@example.test',
                'password'     => Hash::make(self::PASSWORD),
                'mandob_code'  => self::SELLER_CODE,
                'vehicle_code' => 'V-TEST',
                'type'         => 'mandob',
                'role'         => 'seller',
                'company_id'   => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]
        );

        // Every list page in the panel narrows to the sellers linked here, so
        // an admin with no rows in admin_sellers sees empty tables everywhere.
        // This is a local convenience account, so it gets all of them.
        foreach (DB::table('admins')->where('role', 'seller')->pluck('id') as $i => $sellerId) {
            DB::table('admin_sellers')->updateOrInsert(
                ['admin_id' => self::ADMIN_ID, 'seller_id' => $sellerId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
        DB::table('storage_sellers')->updateOrInsert(
            ['id' => 90001],
            ['storage_id' => 90001, 'seller_id' => self::SELLER_ID, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('regions')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'name' => 'Test Region', 'name_en' => 'Test Region',
             'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('seller_regions')->updateOrInsert(
            ['id' => 90001],
            ['seller_id' => self::SELLER_ID, 'region_id' => 90001]
        );

        DB::table('categories')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'name' => 'Test Category', 'parent_id' => 0, 'position' => 1,
             'status' => 1, 'image' => 'def.png', 'created_at' => $now, 'updated_at' => $now]
        );

        // The sellable catalogue is driven by the categories assigned to the
        // seller, so without this row their product list comes back empty.
        DB::table('seller_categories')->updateOrInsert(
            ['id' => 90001],
            ['cat_id' => 90001, 'seller_id' => self::SELLER_ID]
        );

        DB::table('units')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'unit_type' => 'Box', 'symbol' => 'BX', 'is_base' => 1,
             'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('taxes')->updateOrInsert(
            ['id' => 90001],
            ['name' => 'VAT', 'amount' => '14', 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('products')->updateOrInsert(
            ['id' => 90001],
            [
                'local_id' => 90001, 'name' => 'Test Product', 'name_en' => 'Test Product',
                'product_code' => 'TP-0001', 'unit_type' => 90001, 'unit_value' => 1,
                'category_id' => '90001', 'purchase_price' => 50, 'selling_price' => 75,
                'purchase_price1' => 50, 'purchase_price2' => 50, 'purchase_price3' => 50, 'purchase_price4' => 50,
                'selling_price1' => 75, 'selling_price2' => 75, 'selling_price3' => 75, 'selling_price4' => 75,
                'tax' => 14, 'quantity' => 500, 'type' => 'product',
                'created_at' => $now, 'updated_at' => $now,
            ]
        );

        DB::table('stocks')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'seller_id' => self::SELLER_ID, 'product_id' => 90001,
             'main_stock' => 500, 'stock' => 500, 'active' => 1,
             'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('customers')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'name' => 'Test Customer', 'name_en' => 'Test Customer',
             'mobile' => '01000000000', 'email' => 'test.customer@example.test',
             'region_id' => 90001, 'active' => 1, 'balance' => 0, 'credit' => 0,
             'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('seller_customers')->updateOrInsert(
            ['id' => 90001],
            ['local_id' => 90001, 'customer_id' => 90001, 'seller_id' => self::SELLER_ID,
             'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('accounts')->updateOrInsert(
            ['id' => 90001],
            ['storage_id' => 90001, 'account' => 'Test Cash', 'account_number' => 'TEST-001',
             'balance' => 10000, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('shifts')->updateOrInsert(
            ['id' => 90001],
            ['name' => 'Test Shift', 'start' => '09:00:00', 'end' => '17:00:00',
             'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('business_settings')->updateOrInsert(
            ['key' => 'kilometer'],
            ['value' => '5', 'created_at' => $now, 'updated_at' => $now]
        );

        $this->command->info('API seller : ' . self::SELLER_CODE . ' / ' . self::PASSWORD);
        $this->command->info('Web admin  : ' . self::ADMIN_EMAIL . ' / ' . self::PASSWORD);
    }

    /**
     * Every section flag switched on.
     *
     * These columns live on `admins` and each Check*Access middleware reads
     * one of them. Without them the admin logs in fine but is bounced back to
     * the dashboard from every page.
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
}
