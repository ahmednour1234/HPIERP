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
        Schema::create('companies', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('local_id')->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('sub_domain_prefix', 255)->nullable();
            $table->tinyInteger('insert_flag')->nullable()->default(1);
            $table->timestamps();
            $table->tinyInteger('update_flag')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
