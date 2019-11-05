<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Domains;

class AddTrafficUpdatedAtToDomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Domains::whereNotNull('serpstat_traffic')->update(['serpstat_traffic' => null]);

        Schema::table('domains', function (Blueprint $table) {
            $table->timestamp('traffic_updated_at')->after('serpstat_traffic')->nullable(); //
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
            $table->dropColumn('traffic_updated_at');
        });
    }
}
