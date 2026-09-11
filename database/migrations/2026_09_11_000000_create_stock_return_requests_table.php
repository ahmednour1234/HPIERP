<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * طلبات إرجاع البضاعة من عربية المندوب إلى المخزن.
     *
     * كانت التسوية تنفَّذ فورًا ولا رجعة فيها: السيرفر يقرر المتبقّي
     * ويعيده للمخزن دون أن يحدد المندوب ما يرجعه ودون موافقة أحد. صار
     * الطلب يُسجَّل معلّقًا هنا، ولا يتحرك المخزون إلا حين يعتمده الأدمن.
     */
    public function up()
    {
        if (!Schema::hasTable('stock_return_requests')) {
            Schema::create('stock_return_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id');

                // pending | approved | rejected
                $table->string('status', 20)->default('pending');

                $table->text('note')->nullable();
                $table->text('admin_note')->nullable();

                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();

                // البحث الدائم هو عن طلب المندوب المعلّق وعن آخر طلباته.
                $table->index(['seller_id', 'status'], 'stock_return_requests_seller_status_index');
            });
        }

        if (!Schema::hasTable('stock_return_request_items')) {
            Schema::create('stock_return_request_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id');
                $table->unsignedBigInteger('product_id');

                // الكميات في هذا المخطط أعداد صحيحة على جدول stocks.
                $table->integer('quantity');

                $table->timestamps();

                $table->index(['request_id'], 'stock_return_request_items_request_id_index');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stock_return_request_items');
        Schema::dropIfExists('stock_return_requests');
    }
};
