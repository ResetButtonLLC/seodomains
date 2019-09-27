<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMiralinksTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('miralinks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('site_id')->nullable();
            $table->text('desc')->nullable();
            $table->float('placement_price', 10, 2)->nullable();
            $table->float('writing_price', 10, 2)->nullable();
            $table->float('placement_price_rur', 10, 2)->nullable();
            $table->float('writing_price_rur', 10, 2)->nullable();
            $table->string('region')->nullable();
            $table->string('theme')->nullable();
            $table->integer('google_index')->nullable();
            $table->integer('links')->nullable();
            $table->string('language')->nullable();
            $table->integer('traffic')->nullable();
            $table->string('lang')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('domain_id')->unsigned();
            $table->foreign('domain_id')
                    ->references('id')->on('domains')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('miralinks');
    }

}
