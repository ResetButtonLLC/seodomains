<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAhrefsTrafficPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->integer('ahrefs_positions_top3')->after('ahrefs_outlinks')->unsigned()->nullable();
            $table->integer('ahrefs_positions_top10')->after('ahrefs_positions_top3')->unsigned()->nullable();
            $table->integer('ahrefs_positions_top100')->after('ahrefs_positions_top10')->unsigned()->nullable();
            $table->integer('ahrefs_traffic_top3')->after('ahrefs_positions_top100')->unsigned()->nullable();
            $table->integer('ahrefs_traffic_top10')->after('ahrefs_traffic_top3')->unsigned()->nullable();
            $table->integer('ahrefs_traffic_top100')->after('ahrefs_traffic_top10')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('ahrefs_positions_top3');
            $table->dropColumn('ahrefs_positions_top10');
            $table->dropColumn('ahrefs_positions_top100');
            $table->dropColumn('ahrefs_traffic_top3');
            $table->dropColumn('ahrefs_traffic_top10');
            $table->dropColumn('ahrefs_traffic_top100');
        });
    }
}
