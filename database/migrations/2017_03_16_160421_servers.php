<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Servers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('servers', function (Blueprint $table) {
          $table->increments('server_id');
          $table->integer('serial');
          $table->integer('org_id');
          $table->string('hostname');
          $table->string('ipaddress');
          $table->string('apikey');
          $table->boolean('production');
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
        Schema::drop('servers');
    }
}
