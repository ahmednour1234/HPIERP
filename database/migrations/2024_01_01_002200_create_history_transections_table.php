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
        if (!Schema::hasTable('history_transections')) {
            Schema::create('history_transections', function (Blueprint $table) {
                $table->id('id');
                $table->string('tran_type', 255)->nullable();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->unsignedBigInteger('seller_id')->nullable();
                $table->double('amount')->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('debit')->nullable();
                $table->boolean('credit')->nullable();
                $table->double('balance')->nullable()->default(0);
                $table->date('date')->nullable();
                $table->unsignedInteger('customer_id')->nullable();
                $table->unsignedInteger('supplier_id')->nullable();
                $table->unsignedInteger('order_id')->nullable();
                $table->timestamps();
                $table->bigInteger('company_id')->nullable();
                $table->mediumText('img')->nullable();
                $table->index(['seller_id'], 'history_transections_seller_id_index');
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
        Schema::dropIfExists('history_transections');
    }
};
