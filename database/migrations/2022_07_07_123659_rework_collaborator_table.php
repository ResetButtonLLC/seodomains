<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collaborator', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->change();
        });

        DB::statement("UPDATE collaborator SET id = site_id");

        Schema::table('collaborator', function (Blueprint $table) {
            $table->dropColumn('site_id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collaborator', function (Blueprint $table) {
            $table->id('id')->change();
        });

        Schema::table('collaborator', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id');
        });

        DB::statement("UPDATE collaborator SET site_id = id");

    }
};
