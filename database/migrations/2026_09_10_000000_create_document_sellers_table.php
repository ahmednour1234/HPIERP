<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إسناد الوثائق لمناديب بعينهم.
     *
     * وثيقة بلا صفوف هنا تبقى عامة يراها كل المناديب، فالوثائق الموجودة
     * قبل هذه الهجرة تفضل ظاهرة كما هي.
     */
    public function up()
    {
        if (!Schema::hasTable('document_sellers')) {
            Schema::create('document_sellers', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('document_id');
                $table->unsignedBigInteger('seller_id');

                // المندوب لا يُسند لنفس الوثيقة مرتين.
                $table->unique(['document_id', 'seller_id'], 'document_sellers_unique');
                $table->index(['seller_id'], 'document_sellers_seller_id_index');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('document_sellers');
    }
};
