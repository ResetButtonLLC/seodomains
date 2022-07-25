<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('prposting_domains', function (Blueprint $table) {
            $table->tinyInteger('cf')->unsigned()->nullable()->after('traffic');
            $table->tinyInteger('tf')->unsigned()->nullable()->after('traffic');
            $table->tinyInteger('dr')->unsigned()->nullable()->after('traffic');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prposting_domains', function (Blueprint $table) {
            $table->dropColumn(['cf','tf','dr']);
        });
    }
};
