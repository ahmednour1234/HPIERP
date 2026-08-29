<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حافز التحصيل يُدخل يدويًا عند دفع المرتب.
 *
 * باقي مبالغ المرتب مخزَّنة varchar في هذا الجدول، فنلتزم بنفس النوع حتى
 * لا يختلف تعامل الصفوف القديمة عن الجديدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (!Schema::hasColumn('salaries', 'collection_incentive')) {
                $table->string('collection_incentive', 20)
                      ->default('0')
                      ->after('transport_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (Schema::hasColumn('salaries', 'collection_incentive')) {
                $table->dropColumn('collection_incentive');
            }
        });
    }
};
