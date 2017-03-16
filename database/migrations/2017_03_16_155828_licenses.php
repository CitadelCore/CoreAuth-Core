<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Licenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('licenses', function (Blueprint $table) {
          $table->increments('serial');
          $table->string('key');
          $table->string('comment')->nullable();
          $table->dateTime('expiry');
          $table->integer('org_id');
          $table->boolean('allow_2fa');
          $table->boolean('allow_extsso');
          $table->boolean('allow_riskengine');
          $table->boolean('allow_api');
          $table->boolean('disabled');
          $table->integer('num_servers');
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
        //
        Schema::drop('licenses');
    }
}
