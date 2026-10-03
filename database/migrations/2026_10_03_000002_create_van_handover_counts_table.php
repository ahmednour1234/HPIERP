<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جرد تسليم عربية مندوب.
     *
     * حين تنتهي خدمة مندوب تبقى بضاعته مسجَّلة على عربيته إلى الأبد:
     * طلب الإرجاع القائم (stock_return_requests) يقدّمه المندوب من
     * التطبيق، ومن انتهت خدمته لا يفعل. فيُجرد من اللوحة.
     *
     * الفرق بين المسجَّل والمسلَّم يُحفظ كميةً فقط ولا يُقوَّم بمال:
     * التسعير قرار إداري يُتخذ خارج النظام.
     *
     * البضاعة لا تتحرك إلا بالاعتماد، كطلب الإرجاع تمامًا، كي يُراجَع
     * الجرد قبل أن يمسّ المخزون.
     */
    public function up()
    {
        if (!Schema::hasTable('van_handover_counts')) {
            Schema::create('van_handover_counts', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('seller_id');

                // التسليم الذي جرى الجرد في إطاره، إن وُجد.
                $table->unsignedBigInteger('handover_id')->nullable();

                // pending | approved | rejected
                $table->string('status', 20)->default('pending');

                $table->text('note')->nullable();
                $table->text('admin_note')->nullable();

                $table->unsignedBigInteger('counted_by')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();

                $table->index(['seller_id', 'status'], 'van_handover_counts_seller_status_index');
                $table->index(['handover_id'], 'van_handover_counts_handover_index');
            });
        }

        if (!Schema::hasTable('van_handover_count_items')) {
            Schema::create('van_handover_count_items', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('count_id');
                $table->unsignedBigInteger('product_id');

                // ما يقوله النظام وقت الجرد، محفوظًا وقتها: العربية
                // تتغير لاحقًا، وبغير لقطةٍ محفوظة يُعاد حساب العجز
                // على رصيدٍ آخر فيختلف الرقم عمّا أقرّه الأدمن.
                $table->integer('expected');

                // ما سُلِّم فعلًا، يكتبه الأدمن.
                $table->integer('counted');

                // counted - expected: سالبٌ عجز وموجبٌ زيادة. عمودٌ
                // محسوب يُغني عن إعادة الطرح في كل استعلام وتقرير.
                $table->integer('difference');

                $table->timestamps();

                $table->index(['count_id'], 'van_handover_count_items_count_index');
                $table->index(['product_id'], 'van_handover_count_items_product_index');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('van_handover_count_items');
        Schema::dropIfExists('van_handover_counts');
    }
};
