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
        if (!Schema::hasTable('course_sellers')) {
            Schema::create('course_sellers', function (Blueprint $table) {
                // الإنتاج يحمل AUTO_INCREMENT على هذا العمود (يضيفه الـ dump
                // بـ ALTER منفصل)، فبدون increments هنا يفشل الإدراج محليًا
                // بينما ينجح على الخادم.
                $table->increments('id');
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('seller_id');
                $table->string('name', 500)->default('غير معروف');
                $table->mediumText('link')->nullable();
                $table->json('img')->nullable();
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
        Schema::dropIfExists('course_sellers');
    }
};
