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
        if (!Schema::hasTable('factories')) {
            Schema::create('factories', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->string('phone', 20)->nullable();
                $table->string('email', 255)->nullable();
                $table->text('address')->nullable();
                $table->string('lang', 50)->nullable();
                $table->string('late', 50)->nullable();
                $table->boolean('active')->nullable()->default(1);
                $table->timestamps();
                $table->decimal('daen', 15, 2)->default(0.00);
                $table->decimal('maden', 15, 2)->default(0.00);
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
        Schema::dropIfExists('factories');
    }
};
