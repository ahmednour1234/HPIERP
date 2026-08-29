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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('admin_id');
            $table->bigInteger('supply_order_id');
            $table->bigInteger('factory_id');
            $table->decimal('total_cash', 15, 2)->default(0.00)->comment('إجمالي التكلفة النقدية');
            $table->decimal('paid', 15, 2)->default(0.00)->comment('المبلغ المدفوع');
            $table->enum('status', ['cash', 'agel'])->default('cash')->comment('نقدي أو آجل');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_orders');
    }
};
