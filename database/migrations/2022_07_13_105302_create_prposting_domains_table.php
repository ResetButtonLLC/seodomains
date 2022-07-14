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
        Schema::create('prposting_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->unsignedInteger('domain_id')->unique();
            $table->string('name',255);
            $table->decimal('price', 14, 2)->unsigned();
            $table->text('theme');
            $table->unsignedBigInteger('traffic')->nullable();
            $table->timestamps();
        });

        Schema::table('prposting_domains', function (Blueprint $table) {
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
        Schema::dropIfExists('prposting_domains');
    }
};
