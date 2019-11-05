<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDeleteFiels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gogetlinks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('miralinks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('sape', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gogetlinks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('miralinks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sape', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
