<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGogetlinksTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('gogetlinks', function (Blueprint $table) {
            $table->increments('id');
            $table->float('placement_price', 10, 2)->nullable();
            $table->integer('traffic')->nullable();
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
        Schema::dropIfExists('gogetlinks');
    }

}
