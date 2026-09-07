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
        if (!Schema::hasTable('material_batches')) {
            Schema::create('material_batches', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('material_id');
                $table->bigInteger('unit_id')->default(1);
                $table->decimal('quantity', 15, 3);
                $table->decimal('price', 15, 2)->default(0.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('total', 15, 2)->default(0.00);
                $table->date('expiration_date')->nullable();
                $table->string('unique_code', 100)->nullable();
                $table->timestamps();
                $table->unique(['unique_code'], 'unique_code');
                $table->index(['material_id'], 'material_batches_material_id_index');
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
        Schema::dropIfExists('material_batches');
    }
};
