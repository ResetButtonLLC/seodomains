<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prnews_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('domain',255);
            $table->unsignedInteger('domain_id');
            $table->decimal('price',14,2);
            $table->string('country',255);
            $table->text('theme');
            $table->unsignedBigInteger('traffic')->nullable();
            $table->tinyInteger('dr')->nullable();
            $table->tinyInteger('cf')->nullable();
            $table->tinyInteger('tf')->nullable();
            $table->timestamps();
        });

        Schema::table('prnews_domains', function (Blueprint $table) {
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnUpdate()->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prnews_domains');
    }
};
