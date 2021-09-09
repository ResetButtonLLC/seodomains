<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Update;

class RenameCollaboratorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Update::where('name', 'collaborators')->update(['name' => 'collaborator']);
        Schema::rename('collaborators', 'collaborator');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Update::where('name', 'collaborator')->update(['name' => 'collaborators']);
        Schema::rename('collaborator', 'collaborators');
    }
}
