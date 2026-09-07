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
        if (!Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id('store_id');
                $table->bigInteger('local_id');
                $table->unsignedBigInteger('seller_id')->nullable();
                $table->char('store_code', 30);
                $table->string('store_name1', 100)->nullable();
                $table->tinyInteger('store_type')->nullable();
                $table->tinyInteger('insert_flag')->nullable()->default(1);
                $table->timestamps();
                $table->tinyInteger('update_flag')->nullable()->default(0);
                $table->bigInteger('company_id')->default(1);
                $table->index(['seller_id'], 'stores_seller_id_index');
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
        Schema::dropIfExists('stores');
    }
};
