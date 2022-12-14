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
        Schema::table('collaborator', function (Blueprint $table) {
            $table->renameColumn('url','domain');
            $table->decimal('price', 14, 2)->unsigned()->change();
        });

        Schema::rename('collaborator', 'collaborator_domains');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('collaborator_domains', 'collaborator');

        Schema::table('collaborator', function (Blueprint $table) {
            $table->renameColumn('domain','url');
            $table->integer('price')->unsigned()->change();
        });
    }
};
