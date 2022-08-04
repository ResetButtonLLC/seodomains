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
        Schema::table('domains', function (Blueprint $table) {
            $table->unsignedBigInteger('traffic')->after('ahrefs_updated_at')->nullable();
            $table->tinyText('language')->after('country')->nullable();
            $table->text('theme')->after('country')->nullable();
            $table->renameColumn('majestic_updated','majestic_updated_at');
            $table->dropColumn('serpstat_traffic');
            $table->softDeletes();
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
            $table->renameColumn('majestic_updated_at','majestic_updated');
            $table->integer('serpstat_traffic')->after('ahrefs_updated_at')->nullable();
            $table->dropColumn(['traffic','language','theme']);
            $table->dropSoftDeletes();
        });
    }
};
