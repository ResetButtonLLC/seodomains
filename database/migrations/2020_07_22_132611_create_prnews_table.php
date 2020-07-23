<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrnewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prnews', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->nullable();
            $table->string('price')->nullable();
            $table->string('audience')->nullable();
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
    public function down()
    {
        Schema::dropIfExists('prnews');
    }
}
