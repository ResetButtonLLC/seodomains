<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameMiralinksCells extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('miralinks', function (Blueprint $table) {
            $table->renameColumn('placement_price', 'placement_price_usd');
            $table->renameColumn('writing_price', 'writing_price_usd');
            $table->renameColumn('placement_price_rur', 'placement_price');
            $table->renameColumn('writing_price_rur', 'writing_price');
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
            $table->renameColumn('placement_price', 'placement_price_rur');
            $table->renameColumn('writing_price', 'writing_price_rur');
            $table->renameColumn('placement_price_usd', 'placement_price');
            $table->renameColumn('writing_price_usd', 'writing_price');
        });
    }
}
