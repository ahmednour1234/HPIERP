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
        if (!Schema::hasTable('result_visitors')) {
            Schema::create('result_visitors', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('customer_id');
                $table->mediumText('note');
                $table->mediumText('lat');
                $table->mediumText('lang');
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
        Schema::dropIfExists('result_visitors');
    }
};
