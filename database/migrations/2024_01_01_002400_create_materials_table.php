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
        if (!Schema::hasTable('materials')) {
            Schema::create('materials', function (Blueprint $table) {
                $table->id('id');
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('pdf_file', 255)->nullable();
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->unsignedBigInteger('tax_id')->nullable();
                $table->enum('material_type', ['raw', 'primary_packaging', 'secondary_packaging']);
                $table->timestamps();
                $table->index(['unit_id'], 'materials_unit_id_index');
                $table->index(['tax_id'], 'materials_tax_id_index');
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
        Schema::dropIfExists('materials');
    }
};
