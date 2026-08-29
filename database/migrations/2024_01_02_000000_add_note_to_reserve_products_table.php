<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The note a seller types when placing a reservation from the app.
 *
 * Added to both tables: `current_reserve_products` is the working copy the
 * settlement screen reads, and it is written alongside `reserve_products` on
 * every reservation.
 */
return new class extends Migration
{
    public function up()
    {
        foreach (['reserve_products', 'current_reserve_products'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'note')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->text('note')->nullable()->after('data');
                });
            }
        }
    }

    public function down()
    {
        foreach (['reserve_products', 'current_reserve_products'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'note')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('note');
                });
            }
        }
    }
};
