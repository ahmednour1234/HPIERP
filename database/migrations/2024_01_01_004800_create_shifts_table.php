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
        // قاعدة الإنتاج تحمل الجداول بينما سجل migrations لا يطابقها،
        // فبدون هذا الفحص يفشل الأمر على أول جدول موجود.
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 2555);
                $table->time('start')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->time('end')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->integer('breake')->default(1);
                $table->timestamps();
                $table->string('kilometer', 2550)->default('0.1');
                $table->integer('active')->default(1);
                $table->integer('max')->default(0);
                $table->integer('number_shifts')->default(0);
                $table->double('hours_of_each_shift')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
