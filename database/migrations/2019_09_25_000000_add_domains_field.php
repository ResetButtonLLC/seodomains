<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDomainsField extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('domains', function (Blueprint $table) {
            if (!Schema::hasColumn('domains', 'ahrefs_dr')) {
                $table->integer('ahrefs_dr')->nullable();
            }
            if (!Schema::hasColumn('domains', 'ahrefs_inlinks')) {
                $table->integer('ahrefs_inlinks')->nullable();
            }
            if (!Schema::hasColumn('domains', 'ahrefs_outlinks')) {
                $table->integer('ahrefs_outlinks')->nullable();
            }
            if (!Schema::hasColumn('domains', 'serpstat_traffic')) {
                $table->integer('serpstat_traffic')->nullable();
            }
            if (Schema::hasColumn('domains', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('domains', 'site_id')) {
                $table->dropColumn('site_id');
            }
            if (Schema::hasColumn('domains', 'placement_price')) {
                $table->dropColumn('placement_price');
            }
            if (Schema::hasColumn('domains', 'writing_price')) {
                $table->dropColumn('writing_price');
            }
            if (Schema::hasColumn('domains', 'region')) {
                $table->dropColumn('region');
            }
            if (Schema::hasColumn('domains', 'theme')) {
                $table->dropColumn('theme');
            }
            if (Schema::hasColumn('domains', 'google_index')) {
                $table->dropColumn('google_index');
            }
            if (Schema::hasColumn('domains', 'links')) {
                $table->dropColumn('links');
            }
            if (Schema::hasColumn('domains', 'language')) {
                $table->dropColumn('language');
            }
            if (Schema::hasColumn('domains', 'traffic')) {
                $table->dropColumn('traffic');
            }
            if (Schema::hasColumn('domains', 'source')) {
                $table->dropColumn('source');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('domains', function (Blueprint $table) {
            if (Schema::hasColumn('domains', 'ahrefs_dr')) {
                $table->dropColumn('ahrefs_dr');
            }
            if (Schema::hasColumn('domains', 'ahrefs_inlinks')) {
                $table->dropColumn('ahrefs_inlinks');
            }
            if (Schema::hasColumn('domains', 'ahrefs_outlinks')) {
                $table->dropColumn('ahrefs_outlinks');
            }
            if (Schema::hasColumn('domains', 'serpstat_traffic')) {
                $table->dropColumn('serpstat_traffic');
            }
            if (!Schema::hasColumn('domains', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('domains', 'site_id')) {
                $table->integer('site_id')->nullable();
            }
            if (!Schema::hasColumn('domains', 'placement_price')) {
                $table->float('placement_price', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('domains', 'writing_price')) {
                $table->float('writing_price', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('domains', 'region')) {
                $table->string('region')->nullable();
            }
            if (!Schema::hasColumn('domains', 'theme')) {
                $table->string('theme')->nullable();
            }
            if (!Schema::hasColumn('domains', 'google_index')) {
                $table->integer('google_index')->nullable();
            }
            if (!Schema::hasColumn('domains', 'links')) {
                $table->integer('links')->nullable();
            }
            if (!Schema::hasColumn('domains', 'language')) {
                $table->string('language')->nullable();
            }
            if (!Schema::hasColumn('domains', 'traffic')) {
                $table->integer('traffic')->nullable();
            }
            if (!Schema::hasColumn('domains', 'source')) {
                $table->enum('source', ['miralinks', 'gogetlinks', 'rotapost', 'sape']);
            }
        });
    }

}
