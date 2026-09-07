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
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('local_id');
                $table->string('f_name', 255);
                $table->string('l_name', 255);
                $table->string('name_en', 255)->default('Default Name');
                $table->string('email', 255);
                $table->string('phone', 255)->nullable();
                $table->string('password', 255);
                $table->string('remember_token', 100)->nullable();
                $table->tinyInteger('insert_flag')->nullable()->default(1);
                $table->timestamps();
                $table->tinyInteger('update_flag')->nullable()->default(0);
                $table->string('image', 255)->nullable();
                $table->bigInteger('company_id')->nullable()->default(1);
                $table->string('mandob_code', 255)->nullable();
                $table->string('vehicle_code', 255)->nullable();
                $table->string('type', 20)->nullable();
                $table->string('role', 6)->default('admin');
                $table->integer('dashboard')->default(0);
                $table->integer('pos')->default(0);
                $table->integer('stock')->default(0);
                $table->integer('store')->default(0);
                $table->integer('cat')->default(0);
                $table->integer('unit')->default(0);
                $table->integer('product')->default(0);
                $table->integer('stock_limit')->default(0);
                $table->integer('coupons')->default(0);
                $table->integer('customer')->default(0);
                $table->integer('seller')->default(0);
                $table->integer('admin')->default(0);
                $table->integer('supplier')->default(0);
                $table->integer('setting')->default(0);
                $table->integer('requests')->default(0);
                $table->integer('storage')->default(0);
                $table->integer('notification')->default(0);
                $table->integer('tracking')->default(0);
                $table->integer('vehicle_stock')->default(0);
                $table->integer('reports')->default(0);
                $table->integer('regions')->default(0);
                $table->integer('sales')->default(1);
                $table->integer('accounts')->default(1);
                $table->integer('rating')->default(1);
                $table->integer('visit')->default(1);
                $table->integer('sectionsalary')->default(1);
                $table->string('latitude', 2000)->nullable()->default('1');
                $table->string('longitude', 2000)->nullable()->default('1');
                $table->integer('visitors')->default(0);
                $table->integer('result_visitors')->default(0);
                $table->string('salary', 255)->default('0');
                $table->string('precent_of_sales', 255)->default('0');
                $table->string('commission', 5000)->default('0');
                $table->string('balance', 255)->default('0');
                $table->string('credit', 255)->default('0');
                $table->string('score', 20)->default('0');
                $table->mediumText('note')->nullable()->default('\'\'');
                $table->integer('holidays')->default(0);
                $table->integer('number_of_days')->default(0);
                $table->longText('shift_id')->nullable();
                $table->tinyInteger('production')->default(1);
                $table->tinyInteger('hr')->default(1);
                $table->tinyInteger('attendance')->default(1);
                $table->tinyInteger('install')->default(0);
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
        Schema::dropIfExists('admins');
    }
};
