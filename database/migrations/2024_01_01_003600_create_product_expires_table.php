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
        if (!Schema::hasTable('product_expires')) {
            Schema::create('product_expires', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('product_id');
                $table->bigInteger('unit_id')->nullable();
                $table->bigInteger('material_id')->nullable();
                $table->string('quantity', 20);
                $table->timestamps();
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
        Schema::dropIfExists('product_expires');
    }
};
