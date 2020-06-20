<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultHarianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_harian', function (Blueprint $table) {
            $table->id();
            $table->string('tanggal');
            $table->string('max');
            $table->string('min');
            $table->string('data_success');
            $table->string('success_rate');
            $table->string('banjir');
            $table->string('awas');
            $table->string('waspada');
            $table->string('data_failed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('result_harian');
    }
}
