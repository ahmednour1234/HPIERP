<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تسليم عهدة مندوب إلى مندوب آخر.
     *
     * حين تنتهي خدمة مندوب تبقى فواتيره مفتوحة على عملائه، ويستلم
     * غيره تحصيلها. التسليم علاقة مستقلة هنا، ولا يُكتب شيء على
     * orders: ملكية الفاتورة (owner_id) تُكتب مرة عند الإنشاء ولا
     * تتغير، فيبقى أصل كل فاتورة معروفًا ولا تتبدّل أرقام أي تقرير
     * قديم بأثر رجعي.
     *
     * المحصِّل يُسجَّل في transections.seller_id، وهو موجود أصلًا،
     * فيظل كشف كلٍّ من البائع والمحصِّل صحيحًا دون عمود جديد.
     *
     * ended_at فارغًا يعني تسليمًا قائمًا؛ وملؤه ينهي الصلاحية دون
     * محو تاريخ من استلم ومتى، وهو ما تحتاجه المحاسبة لاحقًا.
     */
    public function up()
    {
        if (Schema::hasTable('seller_handovers')) {
            return;
        }

        Schema::create('seller_handovers', function (Blueprint $table) {
            $table->id();

            // صاحب العهدة: المندوب الذي انتهت خدمته.
            $table->unsignedBigInteger('from_seller_id');

            // المستلم: يحصّل فواتير الأول، وتدخل في عهدته هو.
            $table->unsignedBigInteger('to_seller_id');

            $table->timestamp('started_at')->nullable();

            // فارغ = التسليم قائم.
            $table->timestamp('ended_at')->nullable();

            $table->text('note')->nullable();

            // من أنشأ التسليم من اللوحة.
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // السؤال الدائم: هل لهذا المندوب تسليم قائم الآن؟
            $table->index(['to_seller_id', 'ended_at'], 'seller_handovers_to_active_index');
            $table->index(['from_seller_id', 'ended_at'], 'seller_handovers_from_active_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_handovers');
    }
};
