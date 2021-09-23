<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatePlacementToMiralinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('miralinks', function (Blueprint $table) {
            $table->string('last_placement')->nullable();
            $table->string('placement_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('miralinks', function (Blueprint $table) {
            $table->dropColumn('last_placement');
            $table->dropColumn('placement_time');
        });
    }
}
