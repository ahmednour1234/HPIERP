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
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('admin_id');
                $table->date('date');
                $table->time('check_in');
                $table->time('check_out')->nullable();
                $table->integer('status')->default(1);
                $table->string('time_late', 255)->default('0');
                $table->string('expected_hours', 255)->default('0');
                $table->string('worked_hours', 255)->default('0');
                $table->timestamps();
                $table->double('lang')->nullable();
                $table->double('late')->nullable();
                $table->text('note')->nullable();
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
        Schema::dropIfExists('attendances');
    }
};
