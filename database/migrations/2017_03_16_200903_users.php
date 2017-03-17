<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('users', function (Blueprint $table) {
          $table->increments('userid');
          $table->integer('server_id');
          $table->integer('org_id');
          $table->string('username');
          $table->boolean('mfa_enabled');
          $table->string('mfa_secret')->nullable();
          $table->boolean('riskengine_enabled');
          $table->integer('riskengine_level')->nullable();
          $table->string('riskengine_lastip')->nullable();
          $table->dateTime('riskengine_lastlogin')->nullable();
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
        Schema::drop('users');
    }
}
