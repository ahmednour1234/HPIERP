<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Runs the demo dataset plus the API test fixtures, which together give a
     * usable system: settings, a catalogue, sellers with stock and routes,
     * customers, and a few months of orders, payments and visits.
     *
     * ProductTableSeeder and CustomerTableSeeder are deliberately not run here:
     * they generate tens of thousands of rows of random strings, which makes
     * every list page unreadable. Call them explicitly if you need bulk data:
     *
     *     php artisan db:seed --class=ProductTableSeeder
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DemoDataSeeder::class,
            ApiTestingSeeder::class,
        ]);
    }
}
