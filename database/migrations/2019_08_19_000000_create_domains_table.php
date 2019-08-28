<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDomainsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->nullable();
            $table->string('name')->nullable();
            $table->float('placement_price', 8, 2)->nullable();
            $table->float('writing_price', 8, 2)->nullable();
            $table->string('region')->nullable();
            $table->string('theme')->nullable();
            $table->integer('google_index')->nullable();
            $table->integer('links')->nullable();
            $table->string('language')->nullable();
            $table->integer('traffic')->nullable();
            $table->enum('source', ['miralinks', 'gogetlinks', 'rotapost', 'sape']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }

}
