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
        Schema::create('production_order_additional_costs', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('production_order_id');
            $table->string('description', 255)->comment('وصف التكلفة الإضافية');
            $table->decimal('amount', 15, 2)->comment('قيمة التكلفة');
            $table->date('cost_date')->comment('تاريخ التكلفة');
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
        Schema::dropIfExists('production_order_additional_costs');
    }
};
