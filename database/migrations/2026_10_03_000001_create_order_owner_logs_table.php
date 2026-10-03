<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سجلّ نقل ملكية الفواتير بين المناديب.
     *
     * الترحيل يغيّر orders.owner_id فعلًا: المندوب الجديد يصير صاحب
     * الفاتورة في كل شاشة واستعلام، فيحصّلها من التطبيق دون تعديل فيه.
     *
     * ولأن العمود يُكتب فوقه، يصبح هذا الجدول هو الموضع الوحيد الذي
     * يُعرف منه أصل الفاتورة: من كان صاحبها قبل الترحيل، ومتى نُقلت،
     * وبأمر من. بدونه يضيع الأصل بلا رجعة.
     *
     * صفٌّ لكل فاتورة مرحَّلة، لا صفٌّ لكل عملية ترحيل، كي يُقرأ تاريخ
     * الفاتورة الواحدة باستعلام واحد.
     */
    public function up()
    {
        if (Schema::hasTable('order_owner_logs')) {
            return;
        }

        Schema::create('order_owner_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');

            // الأصل: صاحب الفاتورة قبل هذا الترحيل.
            $table->unsignedBigInteger('from_seller_id')->nullable();

            // من آلت إليه.
            $table->unsignedBigInteger('to_seller_id');

            // التسليم الذي تمّ الترحيل في إطاره.
            $table->unsignedBigInteger('handover_id')->nullable();

            // من نفّذ الترحيل من اللوحة.
            $table->unsignedBigInteger('moved_by')->nullable();

            $table->text('reason')->nullable();

            $table->timestamps();

            // تاريخ الفاتورة الواحدة: السؤال الأكثر ورودًا.
            $table->index(['order_id'], 'order_owner_logs_order_index');

            // كل ما رُحِّل عن مندوبٍ أو إليه.
            $table->index(['from_seller_id'], 'order_owner_logs_from_index');
            $table->index(['to_seller_id'], 'order_owner_logs_to_index');
            $table->index(['handover_id'], 'order_owner_logs_handover_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_owner_logs');
    }
};
