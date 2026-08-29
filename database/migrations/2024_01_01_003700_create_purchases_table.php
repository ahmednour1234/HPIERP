<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('admin_id');
            $table->enum('status', ['draft', 'executed'])->default('draft');
            $table->string('image_path', 255)->nullable();
            $table->decimal('sub_total', 15, 2)->default(0.00);
            $table->decimal('total_discount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->timestamps();
            $table->enum('payment_type', ['cash', 'credit'])->default('cash');
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->index(['supplier_id'], 'purchases_supplier_id_index');
            $table->index(['admin_id'], 'purchases_admin_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};
