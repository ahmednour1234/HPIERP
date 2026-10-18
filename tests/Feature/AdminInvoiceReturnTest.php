<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\InvoiceReturnController;
use App\Models\{Admin, Order};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminInvoiceReturnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('customers', function (Blueprint $t) { $t->id(); $t->double('balance')->default(0); $t->timestamps(); });
        Schema::create('accounts', function (Blueprint $t) { $t->id(); $t->double('balance')->default(500); $t->double('total_out')->default(0); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->integer('quantity')->default(0); $t->timestamps(); });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); foreach (['parent_id','user_id','owner_id','type','cash','payment_id','update_flag'] as $c) $t->integer($c)->nullable();
            foreach (['total_tax','order_amount','collected_cash','transaction_reference'] as $c) $t->double($c)->default(0);
            $t->timestamps();
        });
        Schema::create('order_details', function (Blueprint $t) {
            $t->id(); foreach (['order_id','product_id','quantity','quantity_returned','update_flag'] as $c) $t->integer($c)->default(0);
            foreach (['price','tax_amount','discount_on_product'] as $c) $t->double($c)->default(0);
            $t->text('product_details')->nullable(); $t->string('discount_type')->nullable(); $t->timestamps();
        });
        Schema::create('transections', function (Blueprint $t) {
            $t->id(); foreach (['tran_type','seller_id','account_id','customer_id','order_id','cash'] as $c) $t->integer($c)->nullable();
            foreach (['amount','debit','credit','balance'] as $c) $t->double($c)->default(0);
            $t->string('description'); $t->string('date'); $t->timestamps();
        });
        DB::table('customers')->insert(['id'=>1]); DB::table('accounts')->insert(['id'=>1]);
        DB::table('products')->insert(['id'=>1]);
        DB::table('orders')->insert(['id'=>10,'user_id'=>1,'type'=>4,'payment_id'=>1,'order_amount'=>180]);
        DB::table('order_details')->insert(['id'=>20,'order_id'=>10,'product_id'=>1,'quantity'=>2,'price'=>100,'product_details'=>'{"name":"Test"}']);
        $admin = new Admin(); $admin->id = 1; Auth::guard('admin')->setUser($admin);
    }

    private function execute(array $overrides = []): void
    {
        $request = Request::create('/', 'POST', array_replace([
            'customer_id'=>1,'invoice_id'=>10,'method'=>'cash','account_id'=>1,'quantities'=>[20=>1],
        ], $overrides));
        app(InvoiceReturnController::class)->store($request);
    }

    public function test_cash_return_uses_original_discount_and_restocks_only_once(): void
    {
        $this->execute();
        $this->assertDatabaseHas('orders', ['parent_id'=>10,'type'=>7,'order_amount'=>90]);
        $this->assertDatabaseHas('accounts', ['id'=>1,'balance'=>410,'total_out'=>90]);
        $this->assertDatabaseHas('products', ['id'=>1,'quantity'=>1]);
        $this->assertDatabaseHas('order_details', ['id'=>20,'quantity_returned'=>1]);
        try { $this->execute(['quantities'=>[20=>2]]); $this->fail('Over-return accepted'); }
        catch (ValidationException $e) { $this->assertDatabaseHas('products', ['id'=>1,'quantity'=>1]); }
        $this->assertSame(1, Order::where('type',7)->count());
    }

    public function test_credit_return_updates_customer_without_paying_cash(): void
    {
        $this->execute(['method'=>'credit','account_id'=>null]);
        $this->assertDatabaseHas('customers', ['id'=>1,'balance'=>90]);
        $this->assertDatabaseHas('accounts', ['id'=>1,'balance'=>500,'total_out'=>0]);
        $this->assertDatabaseHas('orders', ['parent_id'=>10,'cash'=>2,'collected_cash'=>0]);
    }

    public function test_foreign_invoice_line_is_rejected_without_mutation(): void
    {
        $this->expectException(ValidationException::class);
        try { $this->execute(['quantities'=>[999=>1]]); }
        finally { $this->assertDatabaseHas('products', ['id'=>1,'quantity'=>0]); }
    }

    public function test_invoice_must_belong_to_selected_customer(): void
    {
        DB::table('customers')->insert(['id'=>2]);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        try { $this->execute(['customer_id'=>2]); }
        finally { $this->assertDatabaseHas('products', ['id'=>1,'quantity'=>0]); }
    }
}
