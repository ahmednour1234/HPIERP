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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('seller_id');
            $table->string('salary', 20)->default('0');
            $table->string('commission', 20)->default('0');
            $table->string('number_of_visitors', 20)->default('0');
            $table->string('result_of_visitors', 20)->default('0');
            $table->string('salary_of_visitors', 20)->default('0');
            $table->string('transport_amount', 20)->default('0');
            $table->string('score', 20)->default('0');
            $table->mediumText('month');
            $table->text('note')->default('لاتوجد ملاحظات');
            $table->text('notemanager')->default('لاتوجد ملاحظات');
            $table->integer('number_of_days')->default(0);
            $table->string('discount', 255)->default('0');
            $table->string('total', 20)->default('0');
            $table->string('other', 255)->default('0');
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
        Schema::dropIfExists('salaries');
    }
};
