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
        Schema::create('supply_item_batches', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('material_id');
            $table->bigInteger('supply_order_item_id')->comment('FK إلى supply_order_items.id');
            $table->bigInteger('material_batch_id')->comment('الدفعة المستخدمة من material_batches');
            $table->bigInteger('unit_id')->comment('وحدة القياس المستخدمة في هذا السحب');
            $table->decimal('quantity', 15, 3)->comment('كمية الخام المسحوبة من الدفعة');
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
        Schema::dropIfExists('supply_item_batches');
    }
};
