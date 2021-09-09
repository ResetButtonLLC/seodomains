<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DeleteSoftDeletesColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Delete all old domains
        DB::table('gogetlinks')->whereNotNull('deleted_at')->delete();
        DB::table('miralinks')->whereNotNull('deleted_at')->delete();
        DB::table('sape')->whereNotNull('deleted_at')->delete();
        DB::table('domains')->whereNotNull('deleted_at')->delete();

        Schema::table('gogetlinks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('miralinks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sape', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropSoftDeletes();
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
            $table->softDeletes();
        });

        Schema::table('miralinks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('sape', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
}
