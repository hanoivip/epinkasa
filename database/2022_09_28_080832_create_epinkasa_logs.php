<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEpinkasaLogs extends Migration
{
    public function up()
    {
        Schema::create('epinkasa_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->string('server');
            $table->string('role_id');
            $table->string('mapping');
            $table->string('status');
            $table->string('recharge_status');
            $table->string('package');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('epinkasa_logs');
    }
}
