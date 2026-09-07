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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('local_id');
                $table->string('name', 255);
                $table->string('name_en', 255)->nullable()->default('Default Customer');
                $table->string('mobile', 255);
                $table->string('email', 255)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('state', 255)->nullable();
                $table->string('city', 255)->nullable();
                $table->string('zip_code', 255)->nullable();
                $table->string('address', 255)->nullable();
                $table->double('balance')->nullable();
                $table->double('credit')->default(0);
                $table->integer('type')->default(0);
                $table->string('latitude', 2000)->nullable();
                $table->string('longitude', 2000)->nullable();
                $table->integer('active')->default(1);
                $table->double('limit')->default(0);
                $table->tinyInteger('insert_flag')->nullable()->default(1);
                $table->timestamps();
                $table->tinyInteger('update_flag')->nullable()->default(0);
                $table->bigInteger('company_id')->nullable()->default(1);
                $table->unsignedBigInteger('category_id')->nullable();
                $table->integer('specialist')->default(1);
                $table->bigInteger('region_id')->default(1);
                $table->string('pharmacy_name', 5000)->nullable();
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
        Schema::dropIfExists('customers');
    }
};
