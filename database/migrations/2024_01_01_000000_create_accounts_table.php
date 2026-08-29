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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('account', 255);
            $table->string('description', 255)->nullable();
            $table->double('balance')->nullable()->default(0);
            $table->string('account_number', 255);
            $table->double('total_in')->nullable();
            $table->double('total_out')->nullable();
            $table->timestamps();
            $table->bigInteger('company_id')->nullable()->default(1);
            $table->index(['storage_id'], 'accounts_storage_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
