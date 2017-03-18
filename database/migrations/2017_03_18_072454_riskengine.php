<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Riskengine extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('riskengine', function (Blueprint $table) {
          $table->increments('event_id');
          $table->string('username');
          $table->integer('org_id');
          $table->integer('server_id');
          $table->string('event_type');
          $table->string('event_data');
          $table->integer('event_risk');
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
        Schema::drop('riskengine');
    }
}
