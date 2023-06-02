<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModEpinkasaLogs extends Migration
{
    public function up()
    {
        Schema::table('epinkasa_logs', function (Blueprint $table) {
            $table->string('method')->nullable();
            $table->float('amount')->default(0.0);
            $table->float('net_amount')->default(0.0);
        });
    }

    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('method');
            $table->dropColumn('amount');
            $table->dropColumn('net_amount');
        });
    }
}
