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
        if (!Schema::hasTable('document_attachments')) {
            Schema::create('document_attachments', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('document_id')->comment('معرّف المستند');
                $table->enum('type', ['pdf', 'image', 'link'])->comment('نوع المرفق');
                $table->string('url', 2048)->comment('مسار الملف أو الرابط');
                $table->timestamps();
                $table->index(['document_id'], 'document_attachments_idx_doc_attach_document_index');
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
        Schema::dropIfExists('document_attachments');
    }
};
