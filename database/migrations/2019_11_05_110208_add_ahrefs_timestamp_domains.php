<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Domains;

class AddAhrefsTimestampDomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Domains::whereNotNull('ahrefs_dr')->update(['ahrefs_dr' => null]);
        Domains::whereNotNull('ahrefs_inlinks')->update(['ahrefs_inlinks' => null]);

        Schema::table('domains', function (Blueprint $table) {
            $table->timestamp('ahrefs_updated_at')->after('ahrefs_outlinks')->nullable();
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
            $table->dropColumn('ahrefs_updated_at');
        });
    }
}
